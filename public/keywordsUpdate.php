<?php
//
// Description
// ===========
// Update the output keywords
//
// Arguments
// ---------
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_foodmarket_keywordsUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
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
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.keywordsUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current product
    //
    $strsql = "SELECT id, pio_name, keywords "
        . "FROM ciniki_foodmarket_product_outputs "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'product');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( !isset($rc['rows']) ) { 
        return array('stat'=>'ok');
    }
    $outputs = $rc['rows'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makeKeywords');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    foreach($outputs as $output) {
        $keywords = ciniki_core_makeKeywords($ciniki, $output['pio_name']);
        if( $keywords != $output['keywords'] ) {
            $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.foodmarket.output', $output['id'], array('keywords'=>$keywords), 0x07);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>
