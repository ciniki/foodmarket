<?php
//
// Description
// -----------
// This function will update the order sub items for basket orders.
//
// Arguments
// ---------
//
function ciniki_foodmarket_basketsUpdateOrders(&$ciniki, $business_id, $args) {
    //
    // The date must be specified
    //
    if( !isset($args['date_id']) || $args['date_id'] == '' || $args['date_id'] < 1 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.40', 'msg'=>'An order date must be specified'));
    }

    //
    // Load functions required
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'convertOutputItem');

    //
    // Check if a specific basket is specified
    //
    $basket_select_strsql = '';
    if( isset($args['basket_output_id']) && $args['basket_output_id'] > 0 ) {
        $basket_select_strsql .= "AND ciniki_foodmarket_basket_items.basket_output_id = '" . ciniki_core_dbQuote($ciniki, $args['basket_output_id']) . "' ";
    }

    //
    // Check if a specific item is specified
    //
    $item_select_strsql = '';
    if( isset($args['item_output_id']) && $args['item_output_id'] > 0 ) {
        $item_select_strsql .= "AND ciniki_foodmarket_basket_items.item_output_id = '" . ciniki_core_dbQuote($ciniki, $args['item_output_id']) . "' ";
    }

    //
    // Get the baskets, their items and quantities
    //
    $strsql = "SELECT ciniki_foodmarket_basket_items.id AS basket_item_id, "
        . "ciniki_foodmarket_basket_items.basket_output_id AS basket_id, "
        . "ciniki_foodmarket_basket_items.item_output_id AS item_id, "
        . "ciniki_foodmarket_basket_items.quantity, "
        . "items.pio_name, "
        . "items.otype, "      // Convert to poma itype (NOT input type)
        . "items.units, "
        . "items.flags, "
        . "items.retail_price, "
        . "items.retail_taxtype_id, "
        . "IFNULL(ciniki_foodmarket_product_inputs.inventory, 0) AS inventory, "
        . "IFNULL(ciniki_foodmarket_products.packing_order, 10) AS packing_order, "
        . "IFNULL(ciniki_foodmarket_products.flags, 0) AS product_flags "
        . "FROM ciniki_foodmarket_basket_items "
        . "INNER JOIN ciniki_foodmarket_product_outputs AS items ON ("
            . "ciniki_foodmarket_basket_items.item_output_id = items.id "
            . "AND items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "LEFT JOIN ciniki_foodmarket_product_inputs ON ("
            . "items.input_id = ciniki_foodmarket_product_inputs.id "
            . "AND ciniki_foodmarket_product_inputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "LEFT JOIN ciniki_foodmarket_products ON ("
            . "items.product_id = ciniki_foodmarket_products.id "
            . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "WHERE ciniki_foodmarket_basket_items.date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
        . "AND ciniki_foodmarket_basket_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_foodmarket_basket_items.basket_output_id > 0 "
        . $basket_select_strsql
        . $item_select_strsql
        . "ORDER BY ciniki_foodmarket_basket_items.basket_output_id, ciniki_foodmarket_basket_items.item_output_id "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.poma', array(
        array('container'=>'items', 'fname'=>'basket_item_id', 'fields'=>array('id'=>'item_id', 'item_id', 'basket_item_id', 'basket_id', 'quantity', 'pio_name',
            'otype', 'flags', 'units', 'retail_price', 'retail_taxtype_id', 'inventory', 'packing_order', 'product_flags',
            )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['items']) || count($rc['items']) == 0 ) {
        // Nothing to do, return ok
        return array('stat'=>'ok');
    }
    $items = $rc['items'];
    $item_ids = array();
    $basket_ids = array();
    foreach($items as $basket_item) {
        $item_ids[] = $basket_item['item_id'];
        if( !in_array($basket_item['basket_id'], $basket_ids) ) {
            $basket_ids[] = $basket_item['basket_id'];
        }
    }

    //
    // Load the orders with the baskets specified
    //
    $strsql = "SELECT items.id, items.uuid, "
        . "ciniki_poma_orders.id AS order_id, "
        . "baskets.id AS basket_item_id, "
        . "baskets.object_id AS basket_id, "
        . "items.object_id AS item_id, "
        . "items.flags, "
        . "items.itype, "
        . "items.weight_units, "
        . "items.weight_quantity, "
        . "items.unit_quantity "
        . "FROM ciniki_poma_orders "
        . "LEFT JOIN ciniki_poma_order_items AS baskets ON ("
            . "ciniki_poma_orders.id = baskets.order_id "
            . "AND baskets.object = 'ciniki.foodmarket.output' "
            . "AND baskets.object_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $basket_ids) . ") "
            . "AND baskets.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "LEFT JOIN ciniki_poma_order_items AS items ON ("
            . "baskets.id = items.parent_id "
            . "AND items.object = 'ciniki.foodmarket.output' "
            . "AND items.object_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $item_ids) . ") "
            . "AND baskets.order_id = items.order_id "
            . "AND items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "WHERE ciniki_poma_orders.date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
        . "AND ciniki_poma_orders.status < 50 "
        . "AND ciniki_poma_orders.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "ORDER BY basket_id, order_id "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'baskets', 'fname'=>'basket_id', 'fields'=>array('id'=>'basket_item_id')),
        array('container'=>'orders', 'fname'=>'order_id','fields'=>array('id'=>'order_id', 'basket_item_id')), 
        array('container'=>'items', 'fname'=>'item_id',
            'fields'=>array('id', 'uuid', 'order_id', 'basket_id', 'item_id', 'flags', 'itype', 'weight_units', 'weight_quantity', 'unit_quantity')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['baskets']) ) {
        $order_baskets = $rc['baskets'];
    } else {
        $order_baskets = array();
    }

    //
    // Go through the basket items and check for orders of that basket
    //
    foreach($items AS $basket_item) {
        if( !isset($order_baskets[$basket_item['basket_id']]['orders']) ) {
            continue;
        }
        //
        // Go through the orders for the basket 
        //
        foreach($order_baskets[$basket_item['basket_id']]['orders'] as $order) {
            //
            // Check if this order contains this item already
            //
            if( isset($order['items'][$basket_item['item_id']]) ) {
                $order_item = $order['items'][$basket_item['item_id']];
                if( $order_item['itype'] == 10 ) {
                    $order_item['quantity'] = $order_item['weight_quantity'];
                } else {
                    $order_item['quantity'] = $order_item['unit_quantity'];
                }
                //
                // Check of item on order was not substituted, then update the order quantity
                //
                if( ($order_item['flags']&0x14) == 0 && $order_item['quantity'] != $basket_item['quantity'] ) {
                    if( $basket_item['quantity'] == 0 ) {
                        $rc = ciniki_core_objectDelete($ciniki, $business_id, 'ciniki.poma.orderitem', $order_item['id'], $order_item['uuid'], 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            return $rc;
                        }
                    } else {
                        $update_args = array();
                        if( $order_item['itype'] == 10 ) {
                            $update_args['weight_quantity'] = $basket_item['quantity'];
                        } else {
                            $update_args['unit_quantity'] = $basket_item['quantity'];
                        }
                        error_log('update:' . $order_item['flags']);
                        error_log('update:' . $order_item['id']);
                        $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.poma.orderitem', $order_item['id'], $update_args, 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            return $rc;
                        }
                    }
                }
            } 
            //
            // If not contained, then it should be added as a subitem to the order
            //
            elseif( $basket_item['quantity'] > 0 ) {
                $rc = ciniki_foodmarket_convertOutputItem($ciniki, $business_id, $basket_item);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.44', 'msg'=>'Unable to update order', 'err'=>$rc['err']));
                }
                $order_item = $rc['item'];
                $order_item['order_id'] = $order['id'];
                $order_item['parent_id'] = $order['basket_item_id'];
                if( $basket_item['otype'] == 10 || $basket_item['otype'] == 71 ) {
                    $order_item['weight_quantity'] = $basket_item['quantity'];
                } else {
                    $order_item['unit_quantity'] = $basket_item['quantity'];
                }

                $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.poma.orderitem', $order_item, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
