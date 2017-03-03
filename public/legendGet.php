<?php
//
// Description
// ===========
// This method will return all the information about an legend.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the legend is attached to.
// legend_id:          The ID of the legend to get the details for.
//
// Returns
// -------
//
function ciniki_foodmarket_legendGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'legend_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Category'),
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
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.legendGet');
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Return default for new Category
    //
    if( $args['legend_id'] == 0 ) {
        $legend = array('id'=>0,
            'name'=>'',
            'permalink'=>'',
            'code'=>'',
            'flags'=>'0',
            'image_id'=>'0',
            'synopsis'=>'',
            'description'=>'',
        );
    }

    //
    // Get the details for an existing Category
    //
    else {
        $strsql = "SELECT ciniki_foodmarket_legends.id, "
            . "ciniki_foodmarket_legends.name, "
            . "ciniki_foodmarket_legends.permalink, "
            . "ciniki_foodmarket_legends.code, "
            . "ciniki_foodmarket_legends.flags, "
            . "ciniki_foodmarket_legends.image_id, "
            . "ciniki_foodmarket_legends.synopsis, "
            . "ciniki_foodmarket_legends.description "
            . "FROM ciniki_foodmarket_legends "
            . "WHERE ciniki_foodmarket_legends.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_foodmarket_legends.id = '" . ciniki_core_dbQuote($ciniki, $args['legend_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'legend');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.8', 'msg'=>'Category not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['legend']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.9', 'msg'=>'Unable to find Category'));
        }
        $legend = $rc['legend'];
    }

    return array('stat'=>'ok', 'legend'=>$legend);
}
?>
