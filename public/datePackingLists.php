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
// tnid:        The ID of the tenant to get Order Date Item for.
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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'date_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Order Date'),
        'order_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Order'),
        'size'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Size'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['tnid'], 'ciniki.foodmarket.datePacking');
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'templates', 'packingLists');
    $rc = ciniki_foodmarket_templates_packingLists($ciniki, $args['tnid'], $args);
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
