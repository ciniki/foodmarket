<?php
//
// Description
// -----------
// This method will return the list of Slideshows for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Slideshow for.
//
// Returns
// -------
//
function ciniki_foodmarket_slideshowList($ciniki) {
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
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['tnid'], 'ciniki.foodmarket.slideshowList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of slideshows
    //
    $strsql = "SELECT ciniki_foodmarket_slideshows.id, "
        . "ciniki_foodmarket_slideshows.name, "
        . "ciniki_foodmarket_slideshows.permalink, "
        . "ciniki_foodmarket_slideshows.type, "
        . "ciniki_foodmarket_slideshows.effect, "
        . "ciniki_foodmarket_slideshows.speed, "
        . "ciniki_foodmarket_slideshows.flags "
        . "FROM ciniki_foodmarket_slideshows "
        . "WHERE ciniki_foodmarket_slideshows.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'slideshows', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'type', 'effect', 'speed', 'flags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['slideshows']) ) {
        $slideshows = $rc['slideshows'];
        $slideshow_ids = array();
        foreach($slideshows as $iid => $slideshow) {
            $slideshow_ids[] = $slideshow['id'];
        }
    } else {
        $slideshows = array();
        $slideshow_ids = array();
    }

    return array('stat'=>'ok', 'slideshows'=>$slideshows, 'nplist'=>$slideshow_ids);
}
?>
