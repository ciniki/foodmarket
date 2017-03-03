<?php
//
// Description
// -----------
// This method will return the list of Categorys for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Category for.
//
// Returns
// -------
//
function ciniki_foodmarket_legendList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'subscriptions'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Subscriptions'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.legendList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of legends
    //
    $strsql = "SELECT ciniki_foodmarket_legends.id, "
        . "ciniki_foodmarket_legends.name, "
        . "ciniki_foodmarket_legends.permalink, "
        . "ciniki_foodmarket_legends.code "
        . "FROM ciniki_foodmarket_legends "
        . "WHERE ciniki_foodmarket_legends.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ctype = 0 "
        . "ORDER BY ciniki_foodmarket_legends.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'legends', 'fname'=>'id', 'fields'=>array('id', 'name', 'permalink', 'code')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['legends']) ) {
        $legends = $rc['legends'];
    } else {
        $legends = array();
    }

    return array('stat'=>'ok', 'legends'=>$legends);
}
?>
