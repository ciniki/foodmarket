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
function ciniki_foodmarket_poma_itemLookup($ciniki, $business_id, $args) {

    if( !isset($args['object']) || $args['object'] == '' || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.41', 'msg'=>'No product specified.'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'convertOutputItem');

    //
    // An event was added to an invoice item, get the details and see if we need to 
    // create a registration for this event
    //
    if( $args['object'] == 'ciniki.foodmarket.output' ) {
        $strsql = "SELECT "
            . "ciniki_foodmarket_product_outputs.id, "
            . "ciniki_foodmarket_product_outputs.pio_name, "
            . "ciniki_foodmarket_product_outputs.otype, "      // Item type
            . "ciniki_foodmarket_product_outputs.units, "
            . "ciniki_foodmarket_product_outputs.flags, "
            . "ciniki_foodmarket_product_outputs.retail_price, "
            . "ciniki_foodmarket_product_outputs.retail_price_text, "
            . "ciniki_foodmarket_product_outputs.retail_taxtype_id, "
            . "IFNULL(ciniki_foodmarket_product_inputs.inventory, 0) AS inventory, "
            . "IFNULL(ciniki_foodmarket_product_inputs.flags, 0) AS input_flags, "
            . "IFNULL(ciniki_foodmarket_product_inputs.cdeposit_name, '') AS cdeposit_name, "
            . "IFNULL(ciniki_foodmarket_product_inputs.cdeposit_amount, 0) AS cdeposit_amount, "
            . "IFNULL(ciniki_foodmarket_products.packing_order, 10) AS packing_order "
            . "FROM ciniki_foodmarket_product_outputs "
            . "LEFT JOIN ciniki_foodmarket_product_inputs ON ("
                . "ciniki_foodmarket_product_outputs.input_id = ciniki_foodmarket_product_inputs.id "
                . "AND ciniki_foodmarket_product_inputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_products ON ("
                . "ciniki_foodmarket_product_outputs.product_id = ciniki_foodmarket_products.id "
                . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . ") "
            . "WHERE ciniki_foodmarket_product_outputs.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'output');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['output']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.42', 'msg'=>'Unable to find item'));
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

        //
        // Check for subitems
        //
        if( $item['otype'] == 70 && isset($args['date_id']) && $args['date_id'] > 0 ) {
            $poma_item['subitems'] = array();
            $strsql = "SELECT "
                . "ciniki_foodmarket_product_outputs.id, "
                . "ciniki_foodmarket_product_outputs.pio_name, "
                . "ciniki_foodmarket_product_outputs.otype, "      // Item type
                . "ciniki_foodmarket_product_outputs.units, "
                . "ciniki_foodmarket_product_outputs.flags, "
                . "ciniki_foodmarket_product_outputs.retail_price, "
                . "ciniki_foodmarket_product_outputs.retail_taxtype_id, "
                . "ciniki_foodmarket_basket_items.quantity, "
                . "IFNULL(ciniki_foodmarket_product_inputs.inventory, 0) AS inventory, "
                . "IFNULL(ciniki_foodmarket_products.packing_order, 10) AS packing_order "
                . "FROM ciniki_foodmarket_basket_items "
                . "INNER JOIN ciniki_foodmarket_product_outputs ON ("
                    . "ciniki_foodmarket_basket_items.item_output_id = ciniki_foodmarket_product_outputs.id "
                    . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                    . ") "
                . "LEFT JOIN ciniki_foodmarket_product_inputs ON ("
                    . "ciniki_foodmarket_product_outputs.input_id = ciniki_foodmarket_product_inputs.id "
                    . "AND ciniki_foodmarket_product_inputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                    . ") "
                . "LEFT JOIN ciniki_foodmarket_products ON ("
                    . "ciniki_foodmarket_product_outputs.product_id = ciniki_foodmarket_products.id "
                    . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                    . ") "
                . "WHERE ciniki_foodmarket_basket_items.basket_output_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
                . "AND ciniki_foodmarket_basket_items.date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
                . "AND ciniki_foodmarket_basket_items.quantity > 0 "
                . "AND ciniki_foodmarket_basket_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'output');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['rows']) ) {
                $subitems = $rc['rows'];
                foreach($subitems as $subitem) {
                    $rc = ciniki_foodmarket_convertOutputItem($ciniki, $business_id, $subitem);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    if( $subitem['otype'] == 71 ) {
                        $rc['item']['weight_quantity'] = $subitem['quantity'];
                    } else {
                        $rc['item']['unit_quantity'] = $subitem['quantity'];
                    }
                    $poma_item['subitems'][] = $rc['item'];
                }
            }
        }

        return array('stat'=>'ok', 'item'=>$poma_item);
    }

    return array('stat'=>'ok');
}
?>
