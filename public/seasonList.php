<?php
//
// Description
// -----------
// This method will return the list of Seasons for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Season for.
//
// Returns
// -------
//
function ciniki_foodmarket_seasonList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['tnid'], 'ciniki.foodmarket.seasonList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of seasons
    //
    $strsql = "SELECT ciniki_foodmarket_seasons.id, "
        . "ciniki_foodmarket_seasons.name, "
        . "ciniki_foodmarket_seasons.start_date, "
        . "ciniki_foodmarket_seasons.end_date, "
        . "ciniki_foodmarket_seasons.csa_start_date, "
        . "ciniki_foodmarket_seasons.csa_end_date, "
        . "ciniki_foodmarket_seasons.csa_days "
        . "FROM ciniki_foodmarket_seasons "
        . "WHERE ciniki_foodmarket_seasons.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'seasons', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'start_date', 'end_date', 'csa_start_date', 'csa_end_date', 'csa_days')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['seasons']) ) {
        $seasons = $rc['seasons'];
        $season_ids = array();
        foreach($seasons as $iid => $season) {
            $season_ids[] = $season['id'];
        }
    } else {
        $seasons = array();
        $season_ids = array();
    }

    return array('stat'=>'ok', 'seasons'=>$seasons, 'nplist'=>$season_ids);
}
?>
