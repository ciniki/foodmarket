<?php
//
// Description
// ===========
// This method will return all the information about an product version.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the product version is attached to.
// productversion_id:          The ID of the product version to get the details for.
//
// Returns
// -------
//
function ciniki_foodmarket_productVersionGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'productversion_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product Version'),
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
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.productVersionGet');
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

    //
    // Return default for new Product Version
    //
    if( $args['productversion_id'] == 0 ) {
        $version = array('id'=>0,
            'product_id'=>'',
            'name'=>'',
            'permalink'=>'',
            'status'=>'10',
            'flags'=>'0',
            'sequence'=>'1',
            'recipe_id'=>'0',
            'recipe_quantity'=>'1',
            'container_id'=>'0',
            'materials_cost_per_container'=>'0',
            'time_cost_per_container'=>'0',
            'total_cost_per_container'=>'0',
            'total_time_per_container'=>'0',
            'inventory'=>'0',
            'supplier_price'=>'0',
            'wholesale_price'=>'0',
            'basket_price'=>'0',
            'retail_price'=>'0',
        );
    }

    //
    // Get the details for an existing Product Version
    //
    else {
        $strsql = "SELECT ciniki_foodmarket_product_versions.id, "
            . "ciniki_foodmarket_product_versions.product_id, "
            . "ciniki_foodmarket_product_versions.name, "
            . "ciniki_foodmarket_product_versions.permalink, "
            . "ciniki_foodmarket_product_versions.status, "
            . "ciniki_foodmarket_product_versions.flags, "
            . "ciniki_foodmarket_product_versions.sequence, "
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
            . "AND ciniki_foodmarket_product_versions.id = '" . ciniki_core_dbQuote($ciniki, $args['productversion_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'version');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.22', 'msg'=>'Product Version not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['version']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.23', 'msg'=>'Unable to find Product Version'));
        }
        $version = $rc['version'];
        
        if( ($version['flags']&0x02) == 0 ) {
            $version['inventory'] = '0';
        }
        $version['supplier_price'] = ($version['supplier_price'] == 0 ? '' : numfmt_format_currency($intl_currency_fmt, $version['supplier_price'], $intl_currency));
        $version['wholesale_price'] = ($version['wholesale_price'] == 0 ? '' : numfmt_format_currency($intl_currency_fmt, $version['wholesale_price'], $intl_currency));
        $version['basket_price'] = ($version['basket_price'] == 0 ? '' : numfmt_format_currency($intl_currency_fmt, $version['basket_price'], $intl_currency));
        $version['retail_price'] = ($version['retail_price'] == 0 ? '' : numfmt_format_currency($intl_currency_fmt, $version['retail_price'], $intl_currency));
    }

    return array('stat'=>'ok', 'productversion'=>$version);
}
?>
