<?php
//
// Description
// -----------
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_foodmarket_seasonCustomerProductRemove(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'poma', 'private', 'orderCreateItemsAdd');
   
    //
    // Load the season product
    //
    $strsql = "SELECT products.id, "
        . "products.season_id, "
        . "products.output_id, "
        . "outputs.pio_name, "
        . "products.repeat_days, "
        . "products.repeat_weeks, "
        . "products.price "
        . "FROM ciniki_foodmarket_season_products AS products "
        . "LEFT JOIN ciniki_foodmarket_product_outputs AS outputs ON ("
            . "products.output_id = outputs.id "
            . "AND outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND products.id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'product');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.132', 'msg'=>'Unable to load product', 'err'=>$rc['err']));
    }
    if( !isset($rc['product']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.133', 'msg'=>'Unable to find requested product'));
    }
    $product = $rc['product'];

    //
    // Load the list of products from the orders where the order dates are pending
    //
    $strsql = "SELECT items.id, items.uuid, dates.id AS date_id, dates.order_date "
        . "FROM ciniki_poma_order_dates AS dates "
        . "INNER JOIN ciniki_poma_orders AS orders ON ( "
            . "dates.id = orders.date_id "
            . "AND orders.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND orders.status = 10 "
            . "AND orders.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_poma_order_items AS items ON ( "
            . "orders.id = items.order_id "
            . "AND items.object = 'ciniki.foodmarket.output' "
            . "AND items.object_id = '" . ciniki_core_dbQuote($ciniki, $product['output_id']) . "' "
            . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE dates.status = 5 " // Pending order dates
        . "AND dates.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY dates.id, items.line_number DESC "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.134', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    }
    $prev_date_id = 0;
    if( isset($rc['rows']) ) {
        $items = $rc['rows'];
        foreach($items as $item) {
            //
            // Skip multiples for same date
            //
            if( $item['date_id'] == $prev_date_id ) {
                continue;
            }
            //
            // Remove the item from the order
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.poma.orderitem', $item['id'], $item['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.135', 'msg'=>'Unable to remove the item for order ' . $item['order_date']));
            }
            $prev_date_id = $item['date_id']; 
        }
    }
    
    return array('stat'=>'ok');
}
?>
