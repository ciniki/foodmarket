<?php
//
// Description
// ===========
// This function loads all the details for a product.
//
// Arguments
// ---------
// ciniki:
// business_id:         The ID of the business the product is attached to.
// product_id:          The ID of the product to get the details for.
//
// Returns
// -------
//
function ciniki_foodmarket_web_productLoad($ciniki, $settings, $business_id, $args) {

    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'maps');
    $rc = ciniki_foodmarket_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');

    //
    // Get the product details from the products table
    //
    $strsql = "SELECT ciniki_foodmarket_products.id, "
        . "ciniki_foodmarket_products.name, "
        . "ciniki_foodmarket_products.permalink, "
        . "ciniki_foodmarket_products.status, "
        . "ciniki_foodmarket_products.ptype, "
        . "ciniki_foodmarket_products.flags, "
        . "ciniki_foodmarket_products.category, "
        . "ciniki_foodmarket_products.primary_image_id AS image_id, "
        . "ciniki_foodmarket_products.synopsis, "
        . "ciniki_foodmarket_products.description, "
        . "ciniki_foodmarket_products.ingredients, "
        . "ciniki_foodmarket_products.supplier_id "
        . "FROM ciniki_foodmarket_products "
        . "WHERE ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_foodmarket_products.status = 40 "
        . "";
    if( isset($args['permalink']) && $args['permalink'] != '' ) {
        $strsql .= "AND ciniki_foodmarket_products.permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' ";
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.39', 'msg'=>'No product specified.'));
    }
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'product');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.20', 'msg'=>'Product not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['product']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.21', 'msg'=>'Unable to find Product'));
    }
    $product = $rc['product'];

    //
    // Get the outputs for the product
    //
    $strsql = "SELECT ciniki_foodmarket_product_outputs.id, "
        . "ciniki_foodmarket_product_outputs.product_id, "
        . "ciniki_foodmarket_product_outputs.input_id, "
        . "ciniki_foodmarket_product_outputs.io_name AS name, "
        . "ciniki_foodmarket_product_outputs.permalink, "
        . "ciniki_foodmarket_product_outputs.status, "
        . "ciniki_foodmarket_product_outputs.otype, "
        . "ciniki_foodmarket_product_outputs.units, "
        . "ciniki_foodmarket_product_outputs.flags, "
        . "ciniki_foodmarket_product_outputs.sequence, "
        . "ciniki_foodmarket_product_outputs.start_date, "
        . "ciniki_foodmarket_product_outputs.end_date, "
        . "ciniki_foodmarket_product_outputs.retail_price AS price, "
        . "ciniki_foodmarket_product_outputs.retail_price_text AS price_text, "
        . "ciniki_foodmarket_product_outputs.retail_taxtype_id AS taxtype_id, "
        . "IFNULL(ciniki_foodmarket_product_inputs.inventory, 0) AS inventory "
        . "FROM ciniki_foodmarket_product_outputs "
        . "LEFT JOIN ciniki_foodmarket_product_inputs ON ("
            . "ciniki_foodmarket_product_outputs.input_id = ciniki_foodmarket_product_inputs.id "
            . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "WHERE ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_foodmarket_product_outputs.product_id = '" . ciniki_core_dbQuote($ciniki, $product['id']) . "' "
        . "AND ciniki_foodmarket_product_outputs.status = 40 "
        . "ORDER BY sequence, name "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'outputs', 'fname'=>'id', 
            'fields'=>array('id', 'product_id', 'input_id', 'name', 'permalink', 'status', 'status_text'=>'status', 'otype', 'otype_text'=>'otype', 
                'units', 'units_text'=>'units', 'flags', 'flags_text'=>'flags', 'sequence', 'start_date', 'end_date', 
                'price', 'price_text', 'taxtype_id', 'inventory',
                ),
            'currency'=>array(
                'wholesale_price'=>array('fmt'=>$intl_currency_fmt, 'currency'=>$intl_currency),
                'retail_price'=>array('fmt'=>$intl_currency_fmt, 'currency'=>$intl_currency),
                ),
            'maps'=>array(
                'status_text'=>$maps['output']['status'],
                'otype_text'=>$maps['output']['otype'],
                'units_text'=>$maps['output']['units'],
                ),
            'flags'=>array(
                'flags_text'=>$maps['output']['flags'],
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['outputs']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'web', 'prepareOutputs');
        $rc = ciniki_foodmarket_web_prepareOutputs($ciniki, $settings, $business_id, array('outputs'=>$rc['outputs']));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $product['outputs'] = $rc['outputs'];
    } else {
        $product['outputs'] = array();
    }

    return array('stat'=>'ok', 'product'=>$product);
}
?>
