<?php
//
// Description
// ===========
// This function will be a callback when an item is added to ciniki.sapos.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_foodmarket_poma_queueItemLookup($ciniki, $business_id, $args) {

    if( !isset($args['object']) || $args['object'] == '' || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.73', 'msg'=>'No product specified.'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'convertOutputItem');

    //
    // Look for the item as queued item.
    //
    if( $args['object'] == 'ciniki.foodmarket.output' ) {
        $strsql = "SELECT "
            . "outputs.id, "
            . "outputs.pio_name, "
            . "outputs.otype, "      // Item type
            . "outputs.units, "
            . "outputs.flags, "
            . "outputs.retail_sdiscount_percent, "
            . "outputs.retail_price, "
            . "outputs.retail_price_text, "
            . "outputs.retail_deposit, "
            . "outputs.retail_taxtype_id, "
            . "IFNULL(inputs.inventory, 0) AS inventory, "
            . "IFNULL(inputs.flags, 0) AS input_flags, "
            . "IFNULL(products.flags, 0) AS product_flags "
            . "FROM ciniki_foodmarket_product_outputs AS outputs "
            . "LEFT JOIN ciniki_foodmarket_product_inputs AS inputs ON ("
                . "outputs.input_id = inputs.id "
                . "AND inputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_products AS products ON ("
                . "outputs.product_id = products.id "
                . "AND products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . ") "
            . "WHERE outputs.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND (outputs.flags&0x0400) = 0x0400 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'output');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['output']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.74', 'msg'=>'Unable to find item'));
        }
        $item = $rc['output'];

        //
        // Prepare output for adding to order
        //
        $rc = ciniki_foodmarket_convertOutputItem($ciniki, $business_id, $item);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $poma_item = $rc['item'];

        return array('stat'=>'ok', 'item'=>$poma_item);
    }

    return array('stat'=>'ok');
}
?>
