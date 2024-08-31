<?php
//
// Description
// ===========
// This method will return all the information about an season product.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the season product is attached to.
// sp_id:          The ID of the season product to get the details for.
//
// Returns
// -------
//
function ciniki_foodmarket_seasonProductGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'sp_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Season Product'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['tnid'], 'ciniki.foodmarket.seasonProductGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Season Product
    //
    if( $args['sp_id'] == 0 ) {
        $product = array('id'=>0,
            'season_id'=>'',
            'output_id'=>'',
            'repeat_days'=>'',
            'repeat_weeks'=>'',
            'price'=>'',
        );
    }

    //
    // Get the details for an existing Season Product
    //
    else {
        $strsql = "SELECT ciniki_foodmarket_season_products.id, "
            . "ciniki_foodmarket_season_products.season_id, "
            . "ciniki_foodmarket_season_products.output_id, "
            . "ciniki_foodmarket_season_products.repeat_days, "
            . "ciniki_foodmarket_season_products.repeat_weeks, "
            . "ciniki_foodmarket_season_products.price "
            . "FROM ciniki_foodmarket_season_products "
            . "WHERE ciniki_foodmarket_season_products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_foodmarket_season_products.id = '" . ciniki_core_dbQuote($ciniki, $args['sp_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
            array('container'=>'products', 'fname'=>'id', 
                'fields'=>array('season_id', 'output_id', 'repeat_days', 'repeat_weeks', 'price'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.107', 'msg'=>'Season Product not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['products'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.108', 'msg'=>'Unable to find Season Product'));
        }
        $product = $rc['products'][0];
    }

    //
    // Get the list of product outputs
    //
    $strsql = "SELECT id, pio_name "
        . "FROM ciniki_foodmarket_product_outputs "
        . "WHERE ciniki_foodmarket_product_outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND status > 5 AND status < 90 "
        . "AND otype <= 70 "
        . "ORDER BY pio_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'outputs', 'fname'=>'id', 
            'fields'=>array('id', 'pio_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.109', 'msg'=>'Unable to load product list', 'err'=>$rc['err']));
    }
    $outputs = isset($rc['outputs']) ? $rc['outputs'] : array();

    return array('stat'=>'ok', 'product'=>$product, 'outputs'=>$outputs);
}
?>
