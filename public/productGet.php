<?php
//
// Description
// ===========
// This method will return all the information about an product.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the product is attached to.
// product_id:          The ID of the product to get the details for.
//
// Returns
// -------
//
function ciniki_foodmarket_productGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'product_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product'),
        'suppliers'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Suppliers'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.productGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');

    //
    // Return default for new Product
    //
    if( $args['product_id'] == 0 ) {
        $product = array('id'=>0,
            'name'=>'',
            'permalink'=>'',
            'status'=>'10',
            'flags'=>'0',
            'category'=>'',
            'primary_image_id'=>'0',
            'synopsis'=>'',
            'description'=>'',
            'supplier_id'=>'0',
            'categories'=>'',
        );
    }

    //
    // Get the details for an existing Product
    //
    else {
        $strsql = "SELECT ciniki_foodmarket_products.id, "
            . "ciniki_foodmarket_products.name, "
            . "ciniki_foodmarket_products.permalink, "
            . "ciniki_foodmarket_products.status, "
            . "ciniki_foodmarket_products.flags, "
            . "ciniki_foodmarket_products.category, "
            . "ciniki_foodmarket_products.primary_image_id, "
            . "ciniki_foodmarket_products.synopsis, "
            . "ciniki_foodmarket_products.description, "
            . "ciniki_foodmarket_products.ingredients, "
            . "ciniki_foodmarket_products.supplier_id "
            . "FROM ciniki_foodmarket_products "
            . "WHERE ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_foodmarket_products.id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'product');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3613', 'msg'=>'Product not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['product']) ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3614', 'msg'=>'Unable to find Product'));
        }
        $product = $rc['product'];

        //
        // Get the list of categories the product is in
        //
        $strsql = "SELECT category_id "
            . "FROM ciniki_foodmarket_category_items "
            . "WHERE ciniki_foodmarket_category_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_foodmarket_category_items.ref_object = 'ciniki.foodmarket.product' "
            . "AND ciniki_foodmarket_category_items.ref_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
            . "";
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.foodmarket', 'categories', 'category_id');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['categories']) ) {
            $product['categories'] = implode(',', $rc['categories']);
        } else {
            $product['categories'] = '';
        }

        //
        // Get the versions of the product
        //
        $strsql = "SELECT ciniki_foodmarket_product_versions.id, "
            . "ciniki_foodmarket_product_versions.product_id, "
            . "ciniki_foodmarket_product_versions.name, "
            . "ciniki_foodmarket_product_versions.permalink, "
            . "ciniki_foodmarket_product_versions.status, "
            . "ciniki_foodmarket_product_versions.flags, "
            . "ciniki_foodmarket_product_versions.recipe_id, "
            . "ciniki_foodmarket_product_versions.recipe_quantity, "
            . "ciniki_foodmarket_product_versions.container_id, "
            . "ciniki_foodmarket_product_versions.materials_cost_per_container, "
            . "ciniki_foodmarket_product_versions.time_cost_per_container, "
            . "ciniki_foodmarket_product_versions.total_cost_per_container, "
            . "ciniki_foodmarket_product_versions.total_time_per_container, "
            . "ciniki_foodmarket_product_versions.inventory, "
            . "ciniki_foodmarket_product_versions.supplier_price, "
            . "ciniki_foodmarket_product_versions.wholesale_price, "
            . "ciniki_foodmarket_product_versions.basket_price, "
            . "ciniki_foodmarket_product_versions.retail_price "
            . "FROM ciniki_foodmarket_product_versions "
            . "WHERE ciniki_foodmarket_product_versions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_foodmarket_product_versions.product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
            . "ORDER BY sequence, name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
            array('container'=>'versions', 'fname'=>'id', 
                'fields'=>array('id', 'product_id', 'name', 'permalink', 'status', 'flags', 'recipe_id', 'recipe_quantity', 'container_id', 
                    'materials_cost_per_container', 'time_cost_per_container', 'total_cost_per_container', 'total_time_per_container', 'inventory', 
                    'supplier_price', 'wholesale_price', 'basket_price', 'retail_price')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['versions']) ) {
            $product['versions'] = $rc['versions'];
            foreach($product['versions'] as $vid => $version) {
                $product['versions'][$vid]['materials_cost_per_container_display'] = numfmt_format_currency($intl_currency_fmt, $version['materials_cost_per_container'], $intl_currency);
                $product['versions'][$vid]['time_cost_per_container_display'] = numfmt_format_currency($intl_currency_fmt, $version['time_cost_per_container'], $intl_currency);
                $product['versions'][$vid]['total_cost_per_container_display'] = numfmt_format_currency($intl_currency_fmt, $version['total_cost_per_container'], $intl_currency);
                $product['versions'][$vid]['supplier_price_display'] = ($version['supplier_price'] == 0 ? '' : numfmt_format_currency($intl_currency_fmt, $version['supplier_price'], $intl_currency));
                $product['versions'][$vid]['wholesale_price_display'] = ($version['wholesale_price'] == 0 ? '' : numfmt_format_currency($intl_currency_fmt, $version['wholesale_price'], $intl_currency));
                $product['versions'][$vid]['basket_price_display'] = ($version['basket_price'] == 0 ? '' : numfmt_format_currency($intl_currency_fmt, $version['basket_price'], $intl_currency));
                $product['versions'][$vid]['retail_price_display'] = ($version['retail_price'] == 0 ? '' : numfmt_format_currency($intl_currency_fmt, $version['retail_price'], $intl_currency));
            }
        } else {
            $product['versions'] = array();
        }

    }

    $rsp = array('stat'=>'ok', 'product'=>$product);

    //
    // Get the list of suppliers
    //
    if( isset($args['suppliers']) && $args['suppliers'] == 'yes' ) {
        $strsql = "SELECT id, name "
            . "FROM ciniki_foodmarket_suppliers "
            . "WHERE ciniki_foodmarket_suppliers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "ORDER BY name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(array('container'=>'suppliers', 'fname'=>'id', 'fields'=>array('id', 'name'))));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['suppliers']) ) {
            $rsp['suppliers'] = $rc['suppliers'];
        } else {
            $rsp['suppliers'] = array();
        }
        array_unshift($rsp['suppliers'], array('id'=>'0', 'name'=>'None'));
    }

    //
    // Get the list of categories
    //
    if( isset($args['categories']) && $args['categories'] == 'yes' ) {
        $strsql = "SELECT c1.id AS id, c1.name AS name, "
            . "IFNULL(c2.id, 0) AS sub_id, "
            . "IFNULL(c2.name, '') AS sub_name "
            . "FROM ciniki_foodmarket_categories AS c1 "
            . "LEFT JOIN ciniki_foodmarket_categories AS c2 ON ("
                . "c1.id = c2.parent_id "
                . "AND c2.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE c1.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND c1.parent_id = 0 "
            . "ORDER BY c1.name, c2.name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
            array('container'=>'parents', 'fname'=>'id', 'fields'=>array('id', 'name')),
            array('container'=>'children', 'fname'=>'sub_id', 'fields'=>array('id'=>'sub_id', 'name'=>'sub_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['categories'] = array();
        if( isset($rc['parents']) ) {
            //
            // Flatten the array
            //
            foreach($rc['parents'] as $parent) {
                $rsp['categories'][] = array(
                    'id'=>$parent['id'], 
                    'name'=>$parent['name'],
                    );
                if( isset($parent['children']) ) {
                    foreach($parent['children'] as $child) {
                        $rsp['categories'][] = array(
                            'id'=>$child['id'], 
                            'name'=>$parent['name'] . ' - ' . $child['name'],
                            );
                    }
                }
            }
        } 
    }

    return $rsp;
}
?>
