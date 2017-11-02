<?php
//
// Description
// -----------
// This method searchs for a Slideshows for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Slideshow for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_foodmarket_slideshowSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.slideshowSearch');
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
        . "WHERE ciniki_foodmarket_slideshows.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ("
            . "name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
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
