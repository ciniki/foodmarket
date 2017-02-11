<?php
//
// Description
// -----------
// This function returns the list of objects and object_ids that should be indexed on the website.
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business to get events for.
//
// Returns
// -------
//
function ciniki_foodmarket_hooks_webIndexList($ciniki, $business_id, $args) {

    $objects = array();

    //
    // Get the list of items that should be in the index
    //
    $strsql = "SELECT CONCAT('ciniki.foodmarket.product.', id) AS oid, 'ciniki.foodmarket.product' AS object, id AS object_id "
        . "FROM ciniki_foodmarket_products "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND status = 40 "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
        array('container'=>'objects', 'fname'=>'oid', 'fields'=>array('object', 'object_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['objects']) ) {
        $objects = $rc['objects'];
    }

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
