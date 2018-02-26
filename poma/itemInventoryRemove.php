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
function ciniki_foodmarket_poma_itemInventoryRemove($ciniki, $tnid, $args) {

    if( !isset($args['object']) || $args['object'] == '' || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.95', 'msg'=>'No product specified.'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'convertOutputItem');

    //
    // An event was added to an invoice item, get the details and see if we need to 
    // create a registration for this event
    //
    if( $args['object'] == 'ciniki.foodmarket.output' ) {
        //
        // Get the current inventory
        //
        $strsql = "SELECT "
            . "outputs.id AS output_id, "
            . "outputs.product_id, "
            . "inputs.id AS input_id, "
            . "inputs.inventory "
            . "FROM ciniki_foodmarket_product_outputs AS outputs "
            . "INNER JOIN ciniki_foodmarket_product_inputs AS inputs ON ("
                . "outputs.input_id = inputs.id "
                . "AND inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE outputs.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'input');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['input']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.96', 'msg'=>'Unable to find item'));
        }
        $input = $rc['input'];

        //
        // Decrease inventory
        //
        $new_inventory = (float)bcsub($input['inventory'], $args['quantity'], 6);
        if( $new_inventory != $input['inventory'] ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.foodmarket.input', $input['input_id'], array(
                'inventory' => $new_inventory,
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.97', 'msg'=>'Unable to remove inventory'));
            }
        }
    }

    return array('stat'=>'ok');
}
?>
