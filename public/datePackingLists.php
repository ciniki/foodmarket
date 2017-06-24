<?php
//
// Description
// -----------
// This method returns the PDF of the packing lists for a date.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Order Date Item for.
//
// Returns
// -------
//
function ciniki_foodmarket_datePackingLists($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'date_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Order Date'),
        'order_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Order'),
        'size'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Size'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.datePacking');
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'templates', 'packingLists');
    $rc = ciniki_foodmarket_templates_packingLists($ciniki, $args['business_id'], $args);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['pdf']) ) {
        $rc['pdf']->Output('PackingList.pdf', 'D');
        return array('stat'=>'exit');
    } 

    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.45', 'msg'=>'No pdf generated'));
}
?>
