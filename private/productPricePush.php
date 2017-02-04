<?php
//
// Description
// -----------
// This function will check for products in each category/subcategory and set the visible flag.
//
// Arguments
// ---------
//
function ciniki_foodmarket_productPricePush(&$ciniki, $business_id, $product_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'poma', 'private', 'orderUpdateStatusBalance');

    //
    // Load the outputs for the product
    //
    $strsql = "SELECT id, retail_price "
        . "FROM ciniki_foodmarket_product_outputs AS outputs "
        . "WHERE outputs.product_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
        . "AND outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'cinii.foodmarket', array(
        array('container'=>'outputs', 'fname'=>'id', 'fields'=>array('id', 'retail_price')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['outputs']) || count($rc['outputs']) == 0 ) {
        // No outputs return ok
        return array('stat'=>'ok');
    }
    $outputs = $rc['outputs'];
    $output_ids = array();
    foreach($outputs as $output) {
        $output_ids[] = $output['id'];
    }

    //
    // Check for those outputs on open orders in poma
    //
    $strsql = "SELECT items.id, "
        . "items.order_id, "
        . "items.object_id, "
        . "items.weight_quantity, "
        . "items.unit_quantity "
        . "FROM ciniki_poma_order_items AS items, ciniki_poma_orders AS orders "
        . "WHERE items.object = 'ciniki.foodmarket.output' "
        . "AND items.object_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $output_ids) . ") "
        . "AND items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND items.order_id = orders.id "
        . "AND orders.status < 50 "
        . "AND orders.payment_status < 50 "
        . "AND orders.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'items');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['rows']) || count($rc['rows']) == 0 ) {
        return array('stat'=>'ok');
    }
    $items = $rc['rows'];

    //
    // Update any prices on open orders
    //
    foreach($items as $item) {
        $output_id = $item['object_id'];
        if( isset($outputs[$output_id]) && $outputs[$output_id]['retail_price'] != $item['unit_amount'] ) {
            $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.poma.orderitem', $item['id'], array('unit_amount'=>$outputs[$output_id]['retail_price']), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }

            $rc = ciniki_poma_orderUpdateStatusBalance($ciniki, $business_id, $item['order_id']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>
