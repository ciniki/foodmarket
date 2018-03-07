<?php
//
// Description
// -----------
// This method will return the list of actions that were applied to an element of an season product.
// This method is typically used by the UI to display a list of changes that have occured
// on an element through time. This information can be used to revert elements to a previous value.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the details for.
// sp_id:          The ID of the season product to get the history for.
// field:                   The field to get the history for.
//
// Returns
// -------
//
function ciniki_foodmarket_seasonProductHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'sp_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Season Product'),
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'field'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['tnid'], 'ciniki.foodmarket.seasonProductHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.foodmarket', 'ciniki_foodmarket_history', $args['tnid'], 'ciniki_foodmarket_season_products', $args['sp_id'], $args['field']);
}
?>
