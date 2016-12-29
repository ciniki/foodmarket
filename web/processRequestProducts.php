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
// business_id:     The ID of the business to get events for.
//
// args:            The possible arguments for products
//
//
// Returns
// -------
//
function ciniki_foodmarket_web_processRequestProducts(&$ciniki, $settings, $business_id, $args) {

    $page = array(
        'title'=>$args['page_title'],
        'breadcrumbs'=>$args['breadcrumbs'],
        'blocks'=>array(),
        'submenu'=>array(),
        );

    //
    // Check for image format
    //
    $thumbnail_format = 'square-cropped';
    $thumbnail_padding_color = '#ffffff';
    if( isset($settings['page-foodmarket-products-thumbnail-format']) && $settings['page-foodmarket-products-thumbnail-format'] == 'square-padded' ) {
        $thumbnail_format = $settings['page-foodmarket-products-thumbnail-format'];
        if( isset($settings['page-foodmarket-products-thumbnail-padding-color']) && $settings['page-foodmarket-products-thumbnail-padding-color'] != '' ) {
            $thumbnail_padding_color = $settings['page-foodmarket-products-thumbnail-padding-color'];
        } 
    }
    
    $display = 'categories';

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
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.foodmarket', 0x20) ) {
        while( ($permalink = array_shift($args['uri_split'])) !== null ) {
            //
            // Check for the category
            //
            $strsql = "SELECT id, permalink, name, ctype "
                . "FROM ciniki_foodmarket_categories "
                . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
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
        $product_permalink = $args['uri_split'][0];
        $display = 'product';
    }

    //
    // Display the product
    //
    if( $display == 'product' ) {
//        $page['blocks'][] = array('type'=>'content', 'title'=>'Display Product', 'content'=>$product_permalink);
       
        //
        // Load the product
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'web', 'productLoad');
        $rc = ciniki_foodmarket_web_productLoad($ciniki, $settings, $business_id, array('permalink'=>$product_permalink));
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
        if( isset($product['outputs']) && count($product['outputs']) > 0 ) {
            $page['blocks'][] = array('type'=>'orderoptions', 'section'=>'prices', 'size'=>$size,
                'title'=>(count($product['outputs']) > 1 ? 'Options' : ''),
                'options'=>$product['outputs'],
                );
//            $page['blocks'][] = array('type'=>'content', 'section'=>'prices', 'title'=>'Options', 'content'=>$content);
//            $page['blocks'][] = array('type'=>'content', 'section'=>'prices', 'title'=>'Options', 'content'=>"<pre>" . print_r($product['outputs'], true) . "</pre>");
//            $page['blocks'][] = array('type'=>'content', 'section'=>'prices', 'title'=>'Options', 'content'=>"<pre>" . print_r($ciniki['business'], true) . "</pre>");
        }
    }

    //
    // Display the list of new products
    //
    if( $display == 'category' && isset($category['ctype']) && $category['ctype'] == 50 ) {
        //
        // Check for any new products for all categories
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'web', 'productList');
        $rc = ciniki_foodmarket_web_productList($ciniki, $settings, $business_id, array('type'=>'newproducts'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['products']) && count($rc['products']) > 0 ) {
            $page['blocks'][] = array('type'=>'productcards', 'base_url'=>$base_url, 'cards'=>$rc['products'],
                'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color,
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
        $rc = ciniki_foodmarket_web_productList($ciniki, $settings, $business_id, array('flags'=>0x10, 'limit'=>20));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['products']) && count($rc['products']) > 0 ) {
            $page['blocks'][] = array('type'=>'productcards', 'base_url'=>$base_url, 'cards'=>$rc['products'],
                'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color,
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
        // Check for any specials for all categories
        //
        if( isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'web', 'favourites');
            $rc = ciniki_foodmarket_web_favourites($ciniki, $settings, $business_id, array());
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['options']) && count($rc['options']) > 0 ) {
                $page['blocks'][] = array('type'=>'orderoptions', 'base_url'=>$base_url, 'size'=>'wide', 'options'=>$rc['options']);
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
        // Show the subcategories for the category
        //
        $rc = ciniki_foodmarket_web_categoryList($ciniki, $settings, $business_id, array('parent_id'=>$category['id']));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['categories']) && count($rc['categories']) > 0 ) {
            $subcategories = $rc['categories'];
            $page['blocks'][] = array( 'type'=>'tagimages', 'base_url'=>$base_url, 'size'=>'small', 'tags'=>$rc['categories'],
                'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color,
                );
        }

        //
        // Check for any specials for all categories
        //
        $rc = ciniki_foodmarket_web_productList($ciniki, $settings, $business_id, array('parent_id'=>$category['id'], 'flags'=>0x10, 'limit'=>20));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['products']) && count($rc['products']) > 0 ) {
            $specials = $rc['products'];
            $page['blocks'][] = array( 'type'=>'productcards', 'title'=>'Specials', 'base_url'=>$base_url, 'cards'=>$rc['products'],
                'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color,
                );
        }

        //
        // Show the products for this category
        //
        $rc = ciniki_foodmarket_web_productList($ciniki, $settings, $business_id, array('category_id'=>$category['id']));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['products']) && count($rc['products']) > 0 ) {
// $page['blocks'][] = array('type'=>'content', 'content'=>"Products<br/><pre>" . print_r($rc, true) . "</pre>");
            $page['blocks'][] = array('type'=>'productcards',
                'title'=>(isset($subcategories) || isset($specials) ? 'Products' : ''),  // No title if only block
                'base_url'=>$base_url, 'cards'=>$rc['products'],
                'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color,
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
        $rc = ciniki_foodmarket_web_categoryList($ciniki, $settings, $business_id, array('parent_id'=>0));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['categories']) && count($rc['categories']) > 0 ) {
            $page['blocks'][] = array('type'=>'tagimages', 'base_url'=>$base_url, 'tags'=>$rc['categories'], 'size'=>'small',
                'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color,
                );
        }

        //
        // Check for any specials for all categories
        //
        $rc = ciniki_foodmarket_web_productList($ciniki, $settings, $business_id, array('flags'=>0x10, 'limit'=>20));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['products']) && count($rc['products']) > 0 ) {
            $page['blocks'][] = array('type'=>'productcards', 'title'=>'Specials', 'base_url'=>$base_url, 'cards'=>$rc['products'],
                'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color,
                );
        }

        //
        // FIXME: Show the new products
        //
/*        $rc = ciniki_foodmarket_web_productList($ciniki, $settings, $business_id, array('latest'=>$category['id']));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['products']) && count($rc['products']) > 0 ) {
            $page['blocks'][] = array('type'=>'imagelist', 'title'=>'Products', 'base_url'=>$base_url, 'tags'=>$rc['products'],
                'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color,
                );
        } */
    }

    return array('stat'=>'ok', 'page'=>$page);
}
?>
