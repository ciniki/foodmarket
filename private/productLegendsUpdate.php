<?php
//
// Description
// -----------
// This function will update the legends a product is a part of
//
// Arguments
// ---------
//
function ciniki_foodmarket_productLegendsUpdate(&$ciniki, $tnid, $product_id, $legends) {

    //
    // Get the existing list of legends for the product
    //
    $strsql = "SELECT id, uuid, legend_id "
        . "FROM ciniki_foodmarket_legend_items "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND product_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'legends', 'fname'=>'legend_id', 'fields'=>array('id', 'uuid', 'legend_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['legends']) ) {
        $existing_legends = $rc['legends'];
    } else {
        $existing_legends = array();
    }

    //
    // Check for any new legends that need to be added
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    foreach($legends as $legend_id) {
        if( !isset($existing_legends[$legend_id]) ) {
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.foodmarket.legenditem', array(
                'legend_id'=>$legend_id,
                'product_id'=>$product_id,
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    //
    // Check for any legends that need to be removed
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    foreach($existing_legends as $legend_id => $legend) {
        if( !in_array($legend_id, $legends) ) {
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.foodmarket.legenditem', $legend['id'], $legend['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>
