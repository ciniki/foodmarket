<?php
//
// Description
// -----------
// This function will check for products in each category/subcategory and set the visible flag.
//
// Arguments
// ---------
//
function ciniki_foodmarket_productPricePush(&$ciniki, $tnid, $product_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'poma', 'private', 'orderUpdateStatusBalance');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    //
    // Load the outputs for the product
    //
    $strsql = "SELECT id, retail_price, retail_sdiscount_percent "
        . "FROM ciniki_foodmarket_product_outputs AS outputs "
        . "WHERE outputs.product_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
        . "AND outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'cinii.foodmarket', array(
        array('container'=>'outputs', 'fname'=>'id', 'fields'=>array('id', 'retail_price', 'retail_sdiscount_percent')),
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
    $items = array();
    $strsql = "SELECT items.id, "
        . "items.order_id, "
        . "items.object_id, "
        . "items.weight_quantity, "
        . "items.unit_quantity, "
        . "items.unit_discount_percentage, "
        . "items.unit_amount "
        . "FROM ciniki_poma_order_items AS items, ciniki_poma_orders AS orders "
        . "WHERE items.object = 'ciniki.foodmarket.output' "
        . "AND items.object_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $output_ids) . ") "
        . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND items.order_id = orders.id "
        . "AND orders.status < 50 "
        . "AND orders.payment_status < 50 "
        . "AND orders.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $items = array_merge($items, $rc['rows']);
    }

    //
    // Get the queued items
    //
    $strsql = "SELECT items.id, "
        . "items.order_id, "
        . "qitems.object_id, "
        . "items.weight_quantity, "
        . "items.unit_quantity, "
        . "items.unit_discount_percentage, "
        . "items.unit_amount "
        . "FROM ciniki_poma_queued_items AS qitems, ciniki_poma_order_items AS items, ciniki_poma_orders AS orders "
        . "WHERE qitems.object = 'ciniki.foodmarket.output' "
        . "AND qitems.object_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $output_ids) . ") "
        . "AND items.object = 'ciniki.poma.queueditem' "
        . "AND items.object_id = qitems.id "
        . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND items.order_id = orders.id "
        . "AND orders.status < 50 "
        . "AND orders.payment_status < 50 "
        . "AND orders.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $items = array_merge($items, $rc['rows']);
    }

    //
    // Update any prices on open orders
    //
    foreach($items as $item) {
        $output_id = $item['object_id'];
        $update_args = array();
        if( isset($outputs[$output_id]) && $outputs[$output_id]['retail_price'] != $item['unit_amount'] ) {
            $update_args['unit_amount'] = $outputs[$output_id]['retail_price'];
        }
        if( isset($outputs[$output_id]) && ($outputs[$output_id]['retail_sdiscount_percent'] * 100) != $item['unit_discount_percentage'] ) {
            $update_args['unit_discount_percentage'] = bcmul($outputs[$output_id]['retail_sdiscount_percent'], 100, 6);
        }
        if( count($update_args) > 0 ) {
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.poma.orderitem', $item['id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }

            $rc = ciniki_poma_orderUpdateStatusBalance($ciniki, $tnid, $item['order_id']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>
