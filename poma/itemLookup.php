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
function ciniki_foodmarket_poma_itemLookup($ciniki, $tnid, $args) {

    if( !isset($args['object']) || $args['object'] == '' || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.41', 'msg'=>'No product specified.'));
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

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
            . "ciniki_foodmarket_product_outputs.retail_sdiscount_percent, "
            . "ciniki_foodmarket_product_outputs.retail_price, "
            . "ciniki_foodmarket_product_outputs.retail_price_text, "
            . "ciniki_foodmarket_product_outputs.retail_mdiscount_percent, "
            . "ciniki_foodmarket_product_outputs.retail_mprice, "
            . "ciniki_foodmarket_product_outputs.retail_mprice_text, "
            . "ciniki_foodmarket_product_outputs.retail_taxtype_id, "
            . "IFNULL(ciniki_foodmarket_product_inputs.inventory, 0) AS inventory, "
            . "IFNULL(ciniki_foodmarket_product_inputs.flags, 0) AS input_flags, "
            . "IFNULL(ciniki_foodmarket_product_inputs.cdeposit_name, '') AS cdeposit_name, "
            . "IFNULL(ciniki_foodmarket_product_inputs.cdeposit_amount, 0) AS cdeposit_amount, "
            . "IFNULL(ciniki_foodmarket_products.packing_order, 10) AS packing_order, "
            . "ciniki_foodmarket_products.flags AS product_flags "
            . "FROM ciniki_foodmarket_product_outputs "
            . "LEFT JOIN ciniki_foodmarket_product_inputs ON ("
                . "ciniki_foodmarket_product_outputs.input_id = ciniki_foodmarket_product_inputs.id "
                . "AND ciniki_foodmarket_product_inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_products ON ("
                . "ciniki_foodmarket_product_outputs.product_id = ciniki_foodmarket_products.id "
                . "AND ciniki_foodmarket_products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ciniki_foodmarket_product_outputs.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND ciniki_foodmarket_product_outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
        // Check if item is limited quantity and check for current available number
        //
        $item['num_ordered'] = 0;
        if( ($item['input_flags']&0x02) == 0x02 ) {
            $dt = new DateTime('now', new DateTimezone($intl_timezone));
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
            $strsql = "SELECT items.object_id, SUM(items.unit_quantity) as num_ordered "
                . "FROM ciniki_poma_order_items AS items "
                . "INNER JOIN ciniki_poma_orders AS orders ON ("
                    . "items.order_id = orders.id "
                    . "AND orders.status < 50 "
                    . "AND orders.order_date >= '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' "
                    . "AND orders.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE items.object = 'ciniki.foodmarket.output' "
                . "AND items.object_id = '" . ciniki_core_dbQuote($ciniki, $item['id']) . "' "
                . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "GROUP BY items.object_id "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'item');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.98', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
            }
            if( isset($rc['item']) ) {
                $item['num_ordered'] = $rc['item']['num_ordered'];
            }
        }
        $item['num_available'] = bcsub($item['inventory'], $item['num_ordered'], 6);

        //
        // Prepare output for adding to order
        //
        $rc = ciniki_foodmarket_convertOutputItem($ciniki, $tnid, $item);
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
                    . "AND ciniki_foodmarket_product_outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "LEFT JOIN ciniki_foodmarket_product_inputs ON ("
                    . "ciniki_foodmarket_product_outputs.input_id = ciniki_foodmarket_product_inputs.id "
                    . "AND ciniki_foodmarket_product_inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "LEFT JOIN ciniki_foodmarket_products ON ("
                    . "ciniki_foodmarket_product_outputs.product_id = ciniki_foodmarket_products.id "
                    . "AND ciniki_foodmarket_products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE ciniki_foodmarket_basket_items.basket_output_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
                . "AND ciniki_foodmarket_basket_items.date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
                . "AND ciniki_foodmarket_basket_items.quantity > 0 "
                . "AND ciniki_foodmarket_basket_items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'output');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['rows']) ) {
                $subitems = $rc['rows'];
                foreach($subitems as $subitem) {
                    $rc = ciniki_foodmarket_convertOutputItem($ciniki, $tnid, $subitem);
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
