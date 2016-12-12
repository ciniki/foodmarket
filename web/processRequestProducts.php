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
// args:            The possible arguments for posts
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
    if( isset($settings['page-products-thumbnail-format']) && $settings['page-products-thumbnail-format'] == 'square-padded' ) {
        $thumbnail_format = $settings['page-products-thumbnail-format'];
        if( isset($settings['page-products-thumbnail-padding-color']) && $settings['page-products-thumbnail-padding-color'] != '' ) {
            $thumbnail_padding_color = $settings['page-products-thumbnail-padding-color'];
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
        $page['blocks'][] = array('type'=>'content', 'title'=>'Display Product', 'content'=>$product_permalink);
       
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

        //
        // Display the product
        //


    }

    //
    // Display the list of new products
    //
    if( $display == 'category' && isset($category['ctype']) && $category['ctype'] == 50 ) {
        //
        // Check for any specials for all categories
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'web', 'productList');
        $rc = ciniki_foodmarket_web_productList($ciniki, $settings, $business_id, array('type'=>'newproducts'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['products']) && count($rc['products']) > 0 ) {
            $page['blocks'][] = array('type'=>'imagelist', 'base_url'=>$base_url, 'list'=>$rc['products'],
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
            $page['blocks'][] = array('type'=>'imagelist', 'base_url'=>$base_url, 'list'=>$rc['products'],
                'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color,
                );
        } else {
            $page['blocks'][] = array('type'=>'content', 'content'=>"I'm sorry, we don't currently have any new products.");
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
            $page['blocks'][] = array( 'type'=>'tagimages', 'base_url'=>$base_url, 'list'=>$rc['categories'],
                'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color,
                );
        }

        //
        // Check for any specials for all categories
        //
        $rc = ciniki_foodmarket_web_productList($ciniki, $settings, $business_id, array('parent_id'=>$category['id'], 'type'=>'specials', 'limit'=>20));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['products']) && count($rc['products']) > 0 ) {
            $specials = $rc['categories'];
            $page['blocks'][] = array( 'type'=>'tagimages', 'title'=>'Specials', 'base_url'=>$base_url, 'list'=>$rc['products'],
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
        print_r($rc);
        if( isset($rc['products']) && count($rc['products']) > 0 ) {
            $page['blocks'][] = array( 'type'=>'tagimages', 
                'title'=>(isset($subcategories) || isset($specials) ? 'Products' : ''),  // No title if only block
                'base_url'=>$base_url, 'list'=>$rc['products'],
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
            $page['blocks'][] = array('type'=>'tagimages', 'base_url'=>$base_url, 'tags'=>$rc['categories'],
                'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color,
                );
        }

        //
        // Check for any specials for all categories
        //
        $rc = ciniki_foodmarket_web_productList($ciniki, $settings, $business_id, array('type'=>'specials', 'limit'=>20));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['products']) && count($rc['products']) > 0 ) {
            $page['blocks'][] = array('type'=>'tagimages', 'title'=>'Specials', 'base_url'=>$base_url, 'list'=>$rc['products'],
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
            $page['blocks'][] = array('type'=>'imagelist', 'title'=>'Products', 'base_url'=>$base_url, 'list'=>$rc['products'],
                'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color,
                );
        } */
    }

    return array('stat'=>'ok', 'page'=>$page);
}
?>
