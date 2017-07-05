<?php
//
// Description
// -----------
// This method returns the list of orders for a procurement item.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Order Date Item for.
//
// Returns
// -------
//
function ciniki_foodmarket_procurementItemOrders($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'input_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Input'),
        'date_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Date'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.procurementItemOrders');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Load poma maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'poma', 'private', 'maps');
    $rc = ciniki_poma_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $poma_maps = $rc['maps'];

    //
    // Setup the default response
    //
    $rsp = array('stat'=>'ok', 'product_name'=>'', 'orderitems'=>array());

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

    //
    // Get the order items for an procurement input id
    //
    $strsql = "SELECT "
        . "ciniki_poma_order_items.id, "
        . "ciniki_poma_orders.billing_name AS display_name, "
        . "ciniki_foodmarket_products.name AS product_name, "
        . "ciniki_foodmarket_product_inputs.product_id, "
        . "ciniki_foodmarket_product_inputs.sku, "
        . "ciniki_foodmarket_product_inputs.name AS input_name, "
        . "ciniki_foodmarket_product_inputs.itype, "
        . "ciniki_foodmarket_product_inputs.units, "
        . "ciniki_foodmarket_product_inputs.flags, "
        . "ciniki_foodmarket_product_inputs.min_quantity, "
        . "ciniki_foodmarket_product_inputs.inc_quantity, "
        . "ciniki_foodmarket_product_inputs.case_cost, "
        . "ciniki_foodmarket_product_inputs.half_cost, "
        . "ciniki_foodmarket_product_inputs.unit_cost, "
        . "ciniki_foodmarket_product_inputs.case_units, "
        . "ciniki_foodmarket_product_outputs.id AS output_id, "
        . "ciniki_foodmarket_product_outputs.otype, "
        . "ciniki_foodmarket_product_outputs.name AS output_name, "
        . "ciniki_foodmarket_product_outputs.io_name, "
        . "ciniki_foodmarket_product_outputs.pio_name, "
        . "ciniki_poma_order_items.weight_quantity, "
        . "ciniki_poma_order_items.unit_quantity, "
        . "ciniki_poma_order_items.date_added, "
        . "ciniki_poma_order_items.last_updated, "
        . "UNIX_TIMESTAMP(ciniki_poma_order_items.date_added) AS date_added_ts,  "
        . "UNIX_TIMESTAMP(ciniki_poma_order_items.last_updated) AS last_updated_ts  "
        . "FROM ciniki_foodmarket_product_inputs "
        . "INNER JOIN ciniki_foodmarket_product_outputs ON ("
            . "ciniki_foodmarket_product_inputs.id  = ciniki_foodmarket_product_outputs.input_id "
            . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "INNER JOIN ciniki_foodmarket_products ON ("
            . "ciniki_foodmarket_product_inputs.product_id  = ciniki_foodmarket_products.id "
            . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "INNER JOIN ciniki_poma_orders ON ("
            . "ciniki_poma_orders.date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
            . "AND ciniki_poma_orders.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "INNER JOIN ciniki_poma_order_items ON ("
            . "ciniki_poma_orders.id = ciniki_poma_order_items.order_id "
            . "AND ciniki_foodmarket_product_outputs.id = ciniki_poma_order_items.object_id "
            . "AND ciniki_poma_order_items.object = 'ciniki.foodmarket.output' "
            . "AND ciniki_poma_order_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_foodmarket_product_inputs.id = '" . ciniki_core_dbQuote($ciniki, $args['input_id']) . "' "
        . "AND ciniki_foodmarket_product_inputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "ORDER BY ciniki_poma_order_items.date_added, ciniki_poma_order_items.last_updated "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'items', 'fname'=>'id', 
            'fields'=>array('id', 'display_name', 'product_id', 'sku', 'name'=>'product_name', 'output_name', 'io_name', 'pio_name', 'otype', 'itype', 'units', 'flags', 
                'min_quantity', 'inc_quantity', 'case_cost', 'half_cost', 'unit_cost', 'case_units', 'weight_quantity', 'unit_quantity',
                'date_added', 'last_updated', 'date_added_ts', 'last_updated_ts'),
            'utctotz'=>array('date_added'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                'last_updated'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format)),
                ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['items']) ) {
        $rsp['orderitems'] = $rc['items'];
        foreach($rsp['orderitems'] as $iid => $item) {
            if( $rsp['product_name'] == '' && $item['name'] != '' ) {
                $rsp['product_name'] = $item['name'];
            }
            if( $input['itype'] == 10 ) {
                $rsp['orderitems'][$iid]['quantity'] = (float)$item['weight_quantity'];
            } else {
                $rsp['orderitems'][$iid]['quantity'] = (float)$item['unit_quantity'];
            }
        }
    }

    //
    // Looked the queued items that required ordering
    //
    $strsql = "SELECT "
        . "ciniki_poma_queued_items.id, "
        . "ciniki_customers.display_name, "
        . "ciniki_foodmarket_products.name AS product_name, "
        . "ciniki_foodmarket_product_inputs.product_id, "
        . "ciniki_foodmarket_product_inputs.sku, "
        . "ciniki_foodmarket_product_inputs.name AS input_name, "
        . "ciniki_foodmarket_product_inputs.itype, "
        . "ciniki_foodmarket_product_inputs.units, "
        . "ciniki_foodmarket_product_inputs.flags, "
        . "ciniki_foodmarket_product_inputs.min_quantity, "
        . "ciniki_foodmarket_product_inputs.inc_quantity, "
        . "ciniki_foodmarket_product_inputs.case_cost, "
        . "ciniki_foodmarket_product_inputs.half_cost, "
        . "ciniki_foodmarket_product_inputs.unit_cost, "
        . "ciniki_foodmarket_product_inputs.case_units, "
        . "ciniki_foodmarket_product_outputs.id AS output_id, "
        . "ciniki_foodmarket_product_outputs.otype, "
        . "ciniki_foodmarket_product_outputs.name AS output_name, "
        . "ciniki_foodmarket_product_outputs.io_name, "
        . "ciniki_foodmarket_product_outputs.pio_name, "
        . "ciniki_poma_queued_items.quantity, "
        . "ciniki_poma_queued_items.date_added, "
        . "ciniki_poma_queued_items.last_updated, "
        . "UNIX_TIMESTAMP(ciniki_poma_queued_items.date_added) AS date_added_ts,  "
        . "UNIX_TIMESTAMP(ciniki_poma_queued_items.last_updated) AS last_updated_ts  "
        . "FROM ciniki_poma_queued_items "
        . "INNER JOIN ciniki_foodmarket_product_outputs ON ("
            . "ciniki_poma_queued_items.object_id  = ciniki_foodmarket_product_outputs.id "
            . "AND ciniki_foodmarket_product_outputs.input_id = '" . ciniki_core_dbQuote($ciniki, $args['input_id']) . "' "
            . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "INNER JOIN ciniki_foodmarket_product_inputs ON ("
            . "ciniki_foodmarket_product_outputs.input_id = ciniki_foodmarket_product_inputs.id "
            . "AND ciniki_foodmarket_product_inputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "INNER JOIN ciniki_foodmarket_products ON ("
            . "ciniki_foodmarket_product_inputs.product_id = ciniki_foodmarket_products.id "
            . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "INNER JOIN ciniki_customers ON ("
            . "ciniki_poma_queued_items.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_poma_queued_items.status = 40 "
        . "AND ciniki_poma_queued_items.object = 'ciniki.foodmarket.output' "
        . "AND ciniki_poma_queued_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "ORDER BY ciniki_poma_queued_items.date_added, ciniki_poma_queued_items.last_updated "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'items', 'fname'=>'id', 
            'fields'=>array('id', 'display_name', 'product_id', 'sku', 'name'=>'product_name', 'io_name', 'pio_name', 'otype', 'itype', 'units', 'flags', 
                'min_quantity', 'inc_quantity', 'case_cost', 'half_cost', 'unit_cost', 'case_units', 'quantity',
                'date_added', 'last_updated', 'date_added_ts', 'last_updated_ts'),
            'utctotz'=>array('date_added'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                'last_updated'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format)),
                ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['items']) ) {
        $rsp['queueitems'] = $rc['items'];
        foreach($rsp['queueitems'] as $iid => $item) {
            $rsp['queueitems'][$iid]['quantity'] = (float)$item['quantity'];
            if( $rsp['product_name'] == '' && $item['name'] != '' ) {
                $rsp['product_name'] = $item['name'];
            }
        }
    }

    return $rsp;
}
?>
