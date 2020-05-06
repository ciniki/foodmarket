<?php
//
// Description
// -----------
// This function will process a web request for the food market module.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get events for.
//
// args:            The possible arguments for products
//
//
// Returns
// -------
//
function ciniki_foodmarket_web_processRequestProducts(&$ciniki, $settings, $tnid, $args) {

    //
    // Check if request is for a slideshow
    //
    if( isset($args['uri_split'][1]) && $args['uri_split'][0] == 'slideshow' && $args['uri_split'][1] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'web', 'processRequestSlideshow');
        return ciniki_foodmarket_web_processRequestSlideshow($ciniki, $settings, $tnid, $args);
    }

    $page = array(
        'title'=>$args['page_title'],
        'breadcrumbs'=>$args['breadcrumbs'],
        'blocks'=>array(),
        'submenu'=>array(
            'categories'=>array('name'=>'Categories', 'url'=>$args['base_url']),
            'catalog'=>array('name'=>'Catalog', 'url'=>$args['base_url'] . '/catalog'),
            ),
        );

    //
    // Check for image formats
    //
    $category_thumbnail_format = 'square-cropped';
    $category_thumbnail_padding_color = '#ffffff';
    if( isset($settings['page-foodmarket-category-thumbnail-format']) && $settings['page-foodmarket-category-thumbnail-format'] == 'square-padded' ) {
        $category_thumbnail_format = $settings['page-foodmarket-category-thumbnail-format'];
        if( isset($settings['page-foodmarket-category-thumbnail-padding-color']) && $settings['page-foodmarket-category-thumbnail-padding-color'] != '' ) {
            $category_thumbnail_padding_color = $settings['page-foodmarket-category-thumbnail-padding-color'];
        } 
    }
    
    $product_thumbnail_format = 'square-cropped';
    $product_thumbnail_padding_color = '#ffffff';
    if( isset($settings['page-foodmarket-products-thumbnail-format']) && $settings['page-foodmarket-products-thumbnail-format'] == 'square-padded' ) {
        $product_thumbnail_format = $settings['page-foodmarket-products-thumbnail-format'];
        if( isset($settings['page-foodmarket-products-thumbnail-padding-color']) && $settings['page-foodmarket-products-thumbnail-padding-color'] != '' ) {
            $product_thumbnail_padding_color = $settings['page-foodmarket-products-thumbnail-padding-color'];
        } 
    }
    
    //
    // Setup api calls back to poma module
    //
    $api_fav_on = 'ciniki/poma/favItemAdd/ciniki.foodmarket.output/';
    $api_fav_off = 'ciniki/poma/favItemDelete/ciniki.foodmarket.output/';
    $api_order_update = 'ciniki/poma/orderObjectUpdate/ciniki.foodmarket.output/';
    $api_repeat_update = 'ciniki/poma/repeatObjectUpdate/ciniki.foodmarket.output/';
    $api_queue_update = 'ciniki/poma/queueObjectUpdate/ciniki.foodmarket.output/';

    $display = 'categories';

    //
    // Check if prices should be hidden
    //
    $hide_prices = 'no';
    if( isset($settings['page-foodmarket-public-prices']) && $settings['page-foodmarket-public-prices'] == 'no'
        && (!isset($ciniki['session']['customer']['id']) || $ciniki['session']['customer']['id'] < 1)
        ) {
        $hide_prices = 'yes';  
    }

    //
    // Check if member and show prices
    //
    $season_id = 0;
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.foodmarket', 0x01) ) {
        //
        // Check if customer signed in and a member
        //
        if( isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 ) {
            $dt = new DateTime('now', new DateTimezone('UTC'));
            $strsql = "SELECT seasons.id "
                . "FROM ciniki_foodmarket_season_customers AS customers "
                . "INNER JOIN ciniki_foodmarket_seasons AS seasons ON ("
                    . "customers.season_id = seasons.id "
                    . "AND seasons.start_date <= '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' "
                    . "AND seasons.end_date >= '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' "
                    . "AND seasons.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "' "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'season');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.124', 'msg'=>'Unable to check for season', 'err'=>$rc['err']));
            }
            if( isset($rc['season']) ) {
                $season_id = $rc['season']['id'];
                $ciniki['session']['customer']['foodmarket.member'] = 'yes';
            }
        }
    }

    //
    // Setup the "default" category if nothing else selected
    //
    $category = array(
        'id'=>0, 
        'name'=>'Products', 
        'ctype'=>0, 
        );

    //
    // Parse the categories, if enabled
    //
    $category_id = 0;
    $product_permalink = '';
    $base_url = isset($args['base_url']) ? $args['base_url'] : '';
    if( isset($args['uri_split'][0]) && $args['uri_split'][0] == 'catalog' ) {
        $display = 'catalog';
        $page['submenu']['catalog']['selected'] = 'yes';
        $page['breadcrumbs'][] = array('name'=>'Catalog', 'url'=>$base_url . '/catalog');
        if( isset($args['uri_split'][1]) && $args['uri_split'][1] != '' ) {
            $display = 'product';
            $product_permalink = $args['uri_split'][1];
        }
    } elseif( ciniki_core_checkModuleFlags($ciniki, 'ciniki.foodmarket', 0x20) ) {
        $page['submenu']['categories']['selected'] = 'yes';
        while( ($permalink = array_shift($args['uri_split'])) !== null ) {
            //
            // Check for the category
            //
            $strsql = "SELECT id, permalink, name, ctype, synopsis, description "
                . "FROM ciniki_foodmarket_categories "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND parent_id = '" . ciniki_core_dbQuote($ciniki, $category_id) . "' "
                . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'category');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['category']) ) {
                $display = 'category';
                $category = $rc['category'];
                $category_id = $rc['category']['id'];
                $base_url .= '/' . $permalink;
                $page['breadcrumbs'][] = array('name'=>$rc['category']['name'], 'url'=>$base_url);
            } else {
                $product_permalink = $permalink;
                $display = 'product';
                break;
            }
        }
    } elseif( isset($args['uri_split'][0]) && $args['uri_split'][0] != '' ) {
        $page['submenu']['categories']['selected'] = 'yes';
        $product_permalink = $args['uri_split'][0];
        $display = 'product';
    }

    //
    // Display the product
    //
    if( $display == 'product' ) {
        //
        // Load the product
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'web', 'productLoad');
        $rc = ciniki_foodmarket_web_productLoad($ciniki, $settings, $tnid, array('permalink'=>$product_permalink));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['product']) ) {  
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.foodmarket.35', 'msg'=>"I'm sorry, but we can find the product you requested."));
        }
        $product = $rc['product'];

        $page['breadcrumbs'][] = array('name'=>$product['name'], 'url'=>$args['base_url'] . '/' . $product['permalink']);

        //
        // Display the product
        //
        $size = '';
        if( isset($product['image_id']) && $product['image_id'] > 0 ) {
            // Check for the primary image in the product
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
            $rc = ciniki_web_getScaledImageURL($ciniki, $product['image_id'], 'original', '500', 0);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $ciniki['response']['head']['og']['image'] = $rc['domain_url'];
        } else {
            $size = 'wide';
        }

        if( isset($product['synopsis']) && $product['synopsis'] != '' ) {
            $ciniki['response']['head']['og']['description'] = strip_tags($product['synopsis']);
        } elseif( isset($product['description']) && $product['description'] != '' ) {
            $ciniki['response']['head']['og']['description'] = strip_tags($product['description']);
        }

        if( isset($product['image_id']) && $product['image_id'] > 0 ) {
            $page['blocks'][] = array('type'=>'asideimage', 'section'=>'primary-image', 'primary'=>'yes', 'image_id'=>$product['image_id'], 'title'=>$product['name']);
        }
        if( isset($product['description']) && $product['description'] != '' ) {
            $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'', 'content'=>$product['description']);
        }
        if( isset($product['ingredients']) && $product['ingredients'] != '' ) {
            $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'Ingredients', 'content'=>$product['ingredients']);
        }
        if( isset($product['available_months']) && $product['available_months'] > 0 ) {
            $page['blocks'][] = array('type'=>'monthlyavailability', 'section'=>'content', 'title'=>'Availability', 'months'=>$product['available_months']);
        }
        if( isset($product['storage']) && $product['storage'] != '' ) {
            $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'Storage Tips', 'content'=>$product['storage']);
        }
        if( isset($product['culinary']) && $product['culinary'] != '' ) {
            $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'Culinary Tips', 'content'=>$product['culinary']);
        }
        if( isset($product['subitems']) && count($product['subitems']) > 0 ) {
            $page['blocks'][] = array('type'=>'table', 'section'=>'items', 'title'=>'Items - ' . $product['subitems_date_text'], 'class'=>'order-subitems', 'size'=>$size,
                'columns'=>array(
                    array('label'=>'Item', 'field'=>'name', 'class'=>'alignleft'),
                    array('label'=>'Quantity', 'field'=>'quantity_text', 'class'=>'aligncenter'),
                    ),
                'rows'=>$product['subitems'],
                );
        }
        if( isset($product['outputs']) && count($product['outputs']) > 0 ) {
            $page['blocks'][] = array('type'=>'orderoptions', 'section'=>'prices', 'size'=>$size,
                'title'=>(count($product['outputs']) > 1 ? 'Options' : ''),
                'groupings'=>'tables',
                'api_fav_on'=>$api_fav_on,
                'api_fav_off'=>$api_fav_off,
                'api_order_update'=>$api_order_update,
                'api_repeat_update'=>$api_repeat_update,
                'api_queue_update'=>$api_queue_update,
                'options'=>$product['outputs'],
                );
//            $page['blocks'][] = array('type'=>'content', 'section'=>'prices', 'title'=>'Options', 'content'=>$content);
//            $page['blocks'][] = array('type'=>'content', 'section'=>'prices', 'title'=>'Options', 'content'=>"<pre>" . print_r($product['outputs'], true) . "</pre>");
//            $page['blocks'][] = array('type'=>'content', 'section'=>'prices', 'title'=>'Options', 'content'=>"<pre>" . print_r($ciniki['tenant'], true) . "</pre>");
        }
    }

    //
    // Display the list of available products
    //
    if( $display == 'category' && isset($category['ctype']) && $category['ctype'] == 90 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'web', 'productList');

        //
        // Load the list of items for a date
        //
        $date_items = array();
        $date_id = 0;
        if( isset($ciniki['session']['ciniki.poma']['date']['id']) && $ciniki['session']['ciniki.poma']['date']['id'] > 0 ) {
            $date_id = $ciniki['session']['ciniki.poma']['date']['id'];
        } else {
            $strsql = "SELECT id, order_date, display_name, status, flags "
                . "FROM ciniki_poma_order_dates "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND status < 50 "
                . "AND order_date >= DATE(UTC_TIMESTAMP()) "
                . "ORDER BY order_date ASC "
                . "LIMIT 1 "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.poma', 'date');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['date']) ) {
                $date_id = $rc['date']['id'];
            }
        }
        if( $date_id > 0 ) {
            $strsql = "SELECT output_id "
                . "FROM ciniki_foodmarket_date_items "
                . "WHERE date_id = '" . ciniki_core_dbQuote($ciniki, $date_id) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
            $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.foodmarket', 'outputs', 'output_id');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['outputs']) ) {
                $date_items = $rc['outputs'];
            } 
        }

        //
        // Add the category description or synopsis
        //
        if( isset($category['description']) && $category['description'] != '' ) {   
            $page['blocks'][] = array('type'=>'content', 'content'=>$category['description'], 'wide'=>'yes');
        } elseif( isset($category['synopsis']) && $category['synopsis'] != '' ) {   
            $page['blocks'][] = array('type'=>'content', 'content'=>$category['synopsis'], 'wide'=>'yes');
        }

        //
        // Show the products for this category
        //
        $rc = ciniki_foodmarket_web_productList($ciniki, $settings, $tnid, array('category_id'=>$category['id']));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['products']) && count($rc['products']) > 0 ) {
            //
            // Remove unavailable products
            //
            foreach($rc['products'] as $pid => $p) {
                $visible = 'no';
                if( isset($p['options']) ) {
                    foreach($p['options'] as $o) {
                        if( ($o['flags']&0x0100) > 0 || in_array($o['id'], $date_items) ) {
                            $visible = 'yes';
                        }
                    }
                }
                if( $visible == 'no' ) {
                    unset($rc['products'][$pid]);
                } 
//                elseif( isset($settings['page-foodmarket-public-prices']) && $settings['page-foodmarket-public-prices'] == 'no' ) {
//                    $rc['products'][$pid]['price_text'] = '';
//                } 
            }
            
            $page['blocks'][] = array('type'=>'productcards',
                'title'=>(isset($subcategories) || isset($specials) ? 'Products' : ''),  // No title if only block
                'base_url'=>$base_url, 'cards'=>$rc['products'],
                'hide_prices'=>$hide_prices,
                'hide_details'=>$hide_prices,
                'thumbnail_format'=>$product_thumbnail_format, 'thumbnail_padding_color'=>$product_thumbnail_padding_color,
                );
        }
    }

    //
    // Display the list of new products
    //
    elseif( $display == 'category' && isset($category['ctype']) && $category['ctype'] == 60 ) {
        //
        // FIXME: Check for items that are in the queue
        //
        //ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'web', 'productList');
        //$rc = ciniki_foodmarket_web_productList($ciniki, $settings, $tnid, array('type'=>'queued'));
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'web', 'queued');
        $rc = ciniki_foodmarket_web_queued($ciniki, $settings, $tnid, array());
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['products']) && count($rc['products']) > 0 ) {
            //
            // Add the category description or synopsis
            //
            if( isset($category['description']) && $category['description'] != '' ) {   
                $page['blocks'][] = array('type'=>'content', 'content'=>$category['description'], 'wide'=>'yes');
            } elseif( isset($category['synopsis']) && $category['synopsis'] != '' ) {   
                $page['blocks'][] = array('type'=>'content', 'content'=>$category['synopsis'], 'wide'=>'yes');
            }

            ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'web', 'prepareOutputs');
            $rc = ciniki_foodmarket_web_prepareOutputs($ciniki, $settings, $tnid, array('outputs'=>$rc['products']));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $outputs = isset($rc['outputs']) ? $rc['outputs'] : array();
            $page['blocks'][] = array('type'=>'orderoptions', 
                'clickable'=>'yes',
                'base_url'=>$base_url, 
                'size'=>'wide',
                'title'=>'',
                'api_fav_on'=>$api_fav_on,
                'api_fav_off'=>$api_fav_off,
                'api_order_update'=>$api_order_update,
                'api_repeat_update'=>$api_repeat_update,
                'api_queue_update'=>$api_queue_update,
                'options'=>$outputs,
                );
/*            $page['blocks'][] = array('type'=>'productcards', 'base_url'=>$base_url, 'cards'=>$rc['products'],
                'hide_prices'=>$hide_prices,
                'hide_details'=>$hide_prices,
                'thumbnail_format'=>$product_thumbnail_format, 'thumbnail_padding_color'=>$product_thumbnail_padding_color,
                ); */
        } else {
            $page['blocks'][] = array('type'=>'content', 'content'=>"I'm sorry, we don't currently have any products queued.");
        }
    }
    //
    // Display the list of new products
    //
    elseif( $display == 'category' && isset($category['ctype']) && $category['ctype'] == 50 ) {
        //
        // Check for any new products for all categories
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'web', 'productList');
        $rc = ciniki_foodmarket_web_productList($ciniki, $settings, $tnid, array('type'=>'newproducts'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['products']) && count($rc['products']) > 0 ) {
            //
            // Add the category description or synopsis
            //
            if( isset($category['description']) && $category['description'] != '' ) {   
                $page['blocks'][] = array('type'=>'content', 'content'=>$category['description'], 'wide'=>'yes');
            } elseif( isset($category['synopsis']) && $category['synopsis'] != '' ) {   
                $page['blocks'][] = array('type'=>'content', 'content'=>$category['synopsis'], 'wide'=>'yes');
            }

            $page['blocks'][] = array('type'=>'productcards', 'base_url'=>$base_url, 'cards'=>$rc['products'],
                'hide_prices'=>$hide_prices,
                'hide_details'=>$hide_prices,
                'thumbnail_format'=>$product_thumbnail_format, 'thumbnail_padding_color'=>$product_thumbnail_padding_color,
                );
        } else {
            $page['blocks'][] = array('type'=>'content', 'content'=>"I'm sorry, we don't currently have any products on special.");
        }
    }

    //
    // Display the list of products on special
    //
    elseif( $display == 'category' && isset($category['ctype']) && $category['ctype'] == 30 ) {
        //
        // Check for any specials for all categories
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'web', 'productList');
        $rc = ciniki_foodmarket_web_productList($ciniki, $settings, $tnid, array('type'=>'specials'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['products']) && count($rc['products']) > 0 ) {
            //
            // Add the category description or synopsis
            //
            if( isset($category['description']) && $category['description'] != '' ) {   
                $page['blocks'][] = array('type'=>'content', 'content'=>$category['description'], 'wide'=>'yes');
            } elseif( isset($category['synopsis']) && $category['synopsis'] != '' ) {   
                $page['blocks'][] = array('type'=>'content', 'content'=>$category['synopsis'], 'wide'=>'yes');
            }

            $page['blocks'][] = array('type'=>'productcards', 'base_url'=>$base_url, 'cards'=>$rc['products'],
                'hide_prices'=>$hide_prices,
                'hide_details'=>$hide_prices,
                'thumbnail_format'=>$product_thumbnail_format, 'thumbnail_padding_color'=>$product_thumbnail_padding_color,
                );
        } else {
            $page['blocks'][] = array('type'=>'content', 'content'=>"I'm sorry, we don't currently have any new products.");
        }
    } 

    //
    // Display the list of products on special
    //
    elseif( $display == 'category' && isset($category['ctype']) && $category['ctype'] == 10 ) {
        //
        // Check for any favourites
        //
        if( isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'web', 'favourites');
            $rc = ciniki_foodmarket_web_favourites($ciniki, $settings, $tnid, array());
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['options']) && count($rc['options']) > 0 ) {
                //
                // Add the category description or synopsis
                //
                if( isset($category['description']) && $category['description'] != '' ) {   
                    $page['blocks'][] = array('type'=>'content', 'content'=>$category['description'], 'wide'=>'yes');
                } elseif( isset($category['synopsis']) && $category['synopsis'] != '' ) {   
                    $page['blocks'][] = array('type'=>'content', 'content'=>$category['synopsis'], 'wide'=>'yes');
                }

                $page['blocks'][] = array('type'=>'orderoptions', 'base_url'=>$base_url, 'size'=>'wide', 
                    'api_fav_on'=>$api_fav_on,
                    'api_fav_off'=>$api_fav_off,
                    'api_order_update'=>$api_order_update,
                    'api_repeat_update'=>$api_repeat_update,
                    'api_queue_update'=>$api_queue_update,
                    'options'=>$rc['options']);
            } else {
                $page['blocks'][] = array('type'=>'content', 'size'=>'wide', 'content'=>"You don't currently have any favourites. "
                    . "You can browse the products and click on the heart to add it to your favourites.");
            }
        } else {
            $page['blocks'][] = array('type'=>'content', 'size'=>'wide', 'content'=>"You must be logged in to see your favourites.");
        }
    } 

    //
    // Display the list of subcategories, specials and products
    //
    elseif( $display == 'category' && isset($category['ctype']) && $category['ctype'] == 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'web', 'categoryList');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'web', 'productList');

        //
        // Add the category description or synopsis
        //
        if( isset($category['description']) && $category['description'] != '' ) {   
            $page['blocks'][] = array('type'=>'content', 'content'=>$category['description'], 'wide'=>'yes');
        } elseif( isset($category['synopsis']) && $category['synopsis'] != '' ) {   
            $page['blocks'][] = array('type'=>'content', 'content'=>$category['synopsis'], 'wide'=>'yes');
        }

        //
        // Show the subcategories for the category
        //
        $rc = ciniki_foodmarket_web_categoryList($ciniki, $settings, $tnid, array('parent_id'=>$category['id']));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['categories']) && count($rc['categories']) > 0 ) {
            $subcategories = $rc['categories'];
            $page['blocks'][] = array( 'type'=>'tagimages', 'base_url'=>$base_url, 'size'=>'small', 'tags'=>$rc['categories'],
                'thumbnail_format'=>$category_thumbnail_format, 'thumbnail_padding_color'=>$category_thumbnail_padding_color,
                );
        }

        //
        // Check for any specials for all categories
        //
/*        $rc = ciniki_foodmarket_web_productList($ciniki, $settings, $tnid, array('parent_id'=>$category['id'], 'flags'=>0x10, 'limit'=>20));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['products']) && count($rc['products']) > 0 ) {
            $specials = $rc['products'];
            $page['blocks'][] = array( 'type'=>'productcards', 'title'=>'Specials', 'base_url'=>$base_url, 'cards'=>$rc['products'],
                'thumbnail_format'=>$product_thumbnail_format, 'thumbnail_padding_color'=>$product_thumbnail_padding_color,
                );
        } */

        //
        // Show the products for this category
        //
        $rc = ciniki_foodmarket_web_productList($ciniki, $settings, $tnid, array('category_id'=>$category['id']));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['products']) && count($rc['products']) > 0 ) {
// $page['blocks'][] = array('type'=>'content', 'content'=>"Products<br/><pre>" . print_r($rc, true) . "</pre>");
            $page['blocks'][] = array('type'=>'productcards',
                'title'=>(isset($subcategories) || isset($specials) ? 'Products' : ''),  // No title if only block
                'base_url'=>$base_url, 'cards'=>$rc['products'],
                'hide_prices'=>$hide_prices,
                'hide_details'=>$hide_prices,
                'thumbnail_format'=>$product_thumbnail_format, 'thumbnail_padding_color'=>$product_thumbnail_padding_color,
                );
        }
    }

    //
    // Display the main categories
    //
    if( $display == 'categories' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'web', 'categoryList');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'web', 'productList');

        //
        // Show the subcategories for the category
        //
        $rc = ciniki_foodmarket_web_categoryList($ciniki, $settings, $tnid, array('parent_id'=>0));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['categories']) && count($rc['categories']) > 0 ) {

            if( isset($settings['page-foodmarket-categories-layout']) && $settings['page-foodmarket-categories-layout'] == 'expandsubcategories' ) {
                $categories = array();
                $subs = array();
                foreach($rc['categories'] as $c) {
                    if( !isset($c['subcategories']) ) {
                        $categories[] = $c;
                    } else {
                        $subs[] = $c;
                    }
                }
                $page['blocks'][] = array('type'=>'tagimages', 'base_url'=>$base_url, 'tags'=>$categories, 'size'=>'small',
                    'thumbnail_format'=>$category_thumbnail_format, 'thumbnail_padding_color'=>$category_thumbnail_padding_color,
                    );

                if( count($subs) > 0 ) {
                    foreach($subs as $sub) {
                        $page['blocks'][] = array('type'=>'tagimages', 'base_url'=>$base_url . '/' . $sub['permalink'], 
                            'title'=>$sub['name'],
                            'tags'=>$sub['subcategories'], 'size'=>'small',
                            'thumbnail_format'=>$category_thumbnail_format, 'thumbnail_padding_color'=>$category_thumbnail_padding_color,
                            );
                    }
                }
            } else {
                $page['blocks'][] = array('type'=>'tagimages', 'base_url'=>$base_url, 'tags'=>$rc['categories'], 'size'=>'small',
                    'thumbnail_format'=>$category_thumbnail_format, 'thumbnail_padding_color'=>$category_thumbnail_padding_color,
                    );
            }
        }

        //
        // Check for any specials for all categories
        //
/*        $rc = ciniki_foodmarket_web_productList($ciniki, $settings, $tnid, array('type'=>'specials'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['products']) && count($rc['products']) > 0 ) {
            $page['blocks'][] = array('type'=>'productcards', 'title'=>'Specials', 'base_url'=>$base_url, 'cards'=>$rc['products'],
                'thumbnail_format'=>$product_thumbnail_format, 'thumbnail_padding_color'=>$product_thumbnail_padding_color,
                );
        } */

        //
        // FIXME: Show the new products
        //
/*        $rc = ciniki_foodmarket_web_productList($ciniki, $settings, $tnid, array('latest'=>$category['id']));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['products']) && count($rc['products']) > 0 ) {
            $page['blocks'][] = array('type'=>'imagelist', 'title'=>'Products', 'base_url'=>$base_url, 'tags'=>$rc['products'],
                'thumbnail_format'=>$product_thumbnail_format, 'thumbnail_padding_color'=>$product_thumbnail_padding_color,
                );
        } */
    }
    elseif( $display == 'catalog' ) {
        //
        // Get the categories and their products that are available to the public
        //
        $strsql = "SELECT categories.id, "
            . "categories.name AS category_name, "
            . "categories.ctype, "
            . "IFNULL(subcategories.id, 0) AS sid, "
            . "IFNULL(subcategories.name, '') AS subcategory_name, "
            . "products.name AS product_name, "
            . "products.permalink, "
            . "outputs.id AS oid, "
            . "outputs.pio_name, "
            . "outputs.otype, "
            . "outputs.units, "
            . "outputs.flags, "
            . "outputs.retail_price_text, "
            . "outputs.retail_sprice_text, "
            . "outputs.retail_mprice_text, "
            . "inputs.inventory "
            . "FROM ciniki_foodmarket_categories AS categories "
            . "LEFT JOIN ciniki_foodmarket_categories AS subcategories ON ("
                . "categories.id = subcategories.parent_id "
                . "AND subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_category_items AS items ON ("
                . "(categories.id = items.category_id OR subcategories.id = items.category_id) "
                . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_products AS products ON ("
                . "items.product_id = products.id "
                . "AND products.status = 40 "
                . "AND products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_inputs AS inputs ON ("
                . "products.id = inputs.product_id "
                . "AND inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_outputs AS outputs ON ("
                . "products.id = outputs.product_id "
                . "AND outputs.status = 40 "
                . "AND outputs.otype < 71 "
                . "AND outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND categories.parent_id = 0 "
            . "AND (categories.ctype = 0 || categories.ctype = 30 || categories.ctype = 50 || categories.ctype = 90 ) "
            . "";
        if( isset($args['categories']) && count($args['categories']) > 0 ) {
            $strsql .= "AND categories.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['categories']) . ") ";
        }
        $strsql .= "ORDER BY categories.sequence, categories.name, subcategories.name, products.name, outputs.sequence, outputs.pio_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
            array('container'=>'categories', 'fname'=>'id', 'fields'=>array('name'=>'category_name', 'ctype')),
            array('container'=>'subcategories', 'fname'=>'sid', 'fields'=>array('name'=>'subcategory_name')),
            array('container'=>'outputs', 'fname'=>'oid', 'fields'=>array('id'=>'oid', 'name'=>'pio_name', 'permalink', 'otype', 'flags', 'ctype',
                'price_text'=>'retail_price_text', 'sale_price_text'=>'retail_sprice_text', 'member_price_text'=>'retail_mprice_text', 'inventory')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['categories']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.57', 'msg'=>'No products found'));
        }
        $categories = $rc['categories'];

        //
        // Get the list of specials
        //
        $strsql = "SELECT "
            . "products.name AS product_name, "
            . "products.permalink, "
            . "outputs.id AS oid, "
            . "outputs.pio_name, "
            . "outputs.otype, "
            . "outputs.units, "
            . "outputs.flags, "
            . "outputs.retail_price_text, "
            . "outputs.retail_sprice_text, "
            . "outputs.retail_mprice_text "
            . "FROM ciniki_foodmarket_product_outputs AS outputs "
            . "INNER JOIN ciniki_foodmarket_products AS products ON ("
                . "outputs.product_id = products.id "
                . "AND outputs.otype < 71 "
                . "AND outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND products.status = 40 "
            . "AND outputs.status = 40 "
            . "AND outputs.retail_sdiscount_percent > 0 "
            . "ORDER BY products.name, outputs.sequence, outputs.pio_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
            array('container'=>'outputs', 'fname'=>'oid', 'fields'=>array('id'=>'oid', 'name'=>'pio_name', 'permalink', 
                'otype', 'flags', 
                'price_text'=>'retail_price_text', 'sale_price_text'=>'retail_sprice_text', 'member_price_text'=>'retail_mprice_text')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['outputs']) ) {
            $specials = $rc['outputs'];
        }

        //
        // Get the list of new products
        //
        $strsql = "SELECT "
            . "products.name AS product_name, "
            . "products.permalink, "
            . "outputs.id AS oid, "
            . "outputs.pio_name, "
            . "outputs.otype, "
            . "outputs.units, "
            . "outputs.flags, "
            . "outputs.retail_price_text, "
            . "outputs.retail_sprice_text, "
            . "outputs.retail_mprice_text "
            . "FROM ciniki_foodmarket_product_outputs AS outputs "
            . "INNER JOIN ciniki_foodmarket_products AS products ON ("
                . "outputs.product_id = products.id "
                . "AND outputs.otype < 71 "
                . "AND outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND products.status = 40 "
            . "AND outputs.status = 40 "
            . "AND (products.flags&0x01) = 0x01 "
            . "AND outputs.retail_sdiscount_percent > 0 "
            . "ORDER BY products.name, outputs.sequence, outputs.pio_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
            array('container'=>'outputs', 'fname'=>'oid', 'fields'=>array('id'=>'oid', 'name'=>'pio_name', 'permalink', 
                'otype', 'flags', 
                'price_text'=>'retail_price_text', 'sale_price_text'=>'retail_sprice_text', 'member_price_text'=>'retail_mprice_text')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['outputs']) ) {
            $new = $rc['outputs'];
        }

        //
        // Output the catalog
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'web', 'prepareOutputs');
        foreach($categories as $category) {
            if( $category['ctype'] == 30 && isset($specials) && count($specials) > 0 ) {
                $rc = ciniki_foodmarket_web_prepareOutputs($ciniki, $settings, $tnid, array('outputs'=>$specials));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $page['blocks'][] = array('type'=>'orderoptions', 
                    'clickable'=>'yes',
                    'base_url'=>$base_url, 
                    'size'=>'wide',
                    'title'=>$category['name'],
                    'api_fav_on'=>$api_fav_on,
                    'api_fav_off'=>$api_fav_off,
                    'api_order_update'=>$api_order_update,
                    'api_repeat_update'=>$api_repeat_update,
                    'api_queue_update'=>$api_queue_update,
                    'options'=>$rc['outputs'],
                    );
            }
            elseif( $category['ctype'] == 50 && isset($new) && count($new) > 0 ) {
                $rc = ciniki_foodmarket_web_prepareOutputs($ciniki, $settings, $tnid, array('outputs'=>$new));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $page['blocks'][] = array('type'=>'orderoptions', 
                    'clickable'=>'yes',
                    'base_url'=>$base_url, 
                    'size'=>'wide',
                    'title'=>$category['name'],
                    'api_fav_on'=>$api_fav_on,
                    'api_fav_off'=>$api_fav_off,
                    'api_order_update'=>$api_order_update,
                    'api_repeat_update'=>$api_repeat_update,
                    'api_queue_update'=>$api_queue_update,
                    'options'=>$rc['outputs'],
                    );
            }
            foreach($category['subcategories'] AS $subcategory) {
                if( isset($subcategory['outputs']) && count($subcategory['outputs']) > 0 ) {
                    $rc = ciniki_foodmarket_web_prepareOutputs($ciniki, $settings, $tnid, array('outputs'=>$subcategory['outputs']));
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    $outputs = $rc['outputs'];
                    $page['blocks'][] = array('type'=>'orderoptions', 
                        'clickable'=>'yes',
                        'base_url'=>$base_url, 
                        'size'=>'wide',
                        'title'=>$category['name'] . ($subcategory['name'] != '' ? ' > ' . $subcategory['name'] : ''),
                        'api_fav_on'=>$api_fav_on,
                        'api_fav_off'=>$api_fav_off,
                        'api_order_update'=>$api_order_update,
                        'api_repeat_update'=>$api_repeat_update,
                        'api_queue_update'=>$api_queue_update,
                        'options'=>$outputs,
                        );
                }
            }
        }
    }

    return array('stat'=>'ok', 'page'=>$page);
}
?>
