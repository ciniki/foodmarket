<?php
//
// Description
// -----------
// This method will return the list of queued items for a product input.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Order Date for.
//
// Returns
// -------
//
function ciniki_foodmarket_queueInputGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'input_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product Input'),
        'order_item_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order Item'),
        'invoice_item_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice Item'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.queueInputGet');
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
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'mysql');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $time_format = ciniki_users_timeFormat($ciniki, 'php');

    $dt = new DateTime('now', new DateTimeZone('UTC'));

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'poma', 'private', 'maps');
    $rc = ciniki_poma_maps($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

    //
    // Check if item status should be change to ordered
    //
    if( isset($args['order_item_id']) && $args['order_item_id'] > 0 ) {
        $strsql = "SELECT status, quantity "
            . "FROM ciniki_poma_queued_items AS items "
            . "WHERE items.id = '" . ciniki_core_dbQuote($ciniki, $args['order_item_id']) . "' "
            . "AND items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.poma', 'item');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['item']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.75', 'msg'=>'Queued item not found'));
        }
        $item = $rc['item'];
        if( $item['status'] == 10 ) {   
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.poma.queueditem', $args['order_item_id'], array('status'=>40), 0x07);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    //
    // Check if the item should be invoiced
    //
    if( isset($args['invoice_item_id']) && $args['invoice_item_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'poma', 'private', 'queueInvoiceItem');
        $rc = ciniki_poma_queueInvoiceItem($ciniki, $args['business_id'], $args['invoice_item_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }


    //
    // Looked the queued items 
    //
    $strsql = "SELECT "
        . "inputs.id, "
        . "inputs.product_id, "
        . "inputs.sku, "
        . "products.name, "
        . "inputs.name AS input_name, "
        . "inputs.itype, "
        . "inputs.units, "
        . "inputs.flags, "
        . "inputs.min_quantity, "
        . "inputs.inc_quantity, "
        . "inputs.case_cost, "
        . "inputs.half_cost, "
        . "inputs.unit_cost, "
        . "inputs.case_units, "
        . "outputs.id AS output_id, "
        . "outputs.otype, "
        . "outputs.name AS output_name, "
        . "outputs.io_name, "
        . "outputs.pio_name, "
        . "items.id AS item_id, "
        . "items.status, "
        . "items.quantity, "
        . "DATE_FORMAT(items.queued_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS queued_date, "
        . "customers.display_name "
        . "FROM ciniki_foodmarket_product_inputs AS inputs "
        . "LEFT JOIN ciniki_foodmarket_product_outputs AS outputs ON ("
            . "inputs.id = outputs.input_id "
            . "AND outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_foodmarket_products AS products ON ("
            . "inputs.product_id = products.id "
            . "AND products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_poma_queued_items AS items ON ("
            . "outputs.id = items.object_id "
            . "AND items.object = 'ciniki.foodmarket.output' "
            . "AND items.status < 90 "
            . "AND items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "items.customer_id = customers.id "
            . "AND customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE inputs.id = '" . ciniki_core_dbQuote($ciniki, $args['input_id']) . "' "
        . "AND inputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "ORDER BY items.status, items.queued_date ASC "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'status', 'fname'=>'status', 'fields'=>array('status')),
        array('container'=>'items', 'fname'=>'item_id', 'fields'=>array('id'=>'item_id', 'display_name', 'name', 'input_name', 'output_name', 'io_name', 'queued_date', 'status', 'quantity')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $queued_items = array();
    $ordered_items = array();
    $products = array();
    $name = '';
    if( isset($rc['status']) ) {
        foreach($rc['status'] as $status) {
            foreach($status['items'] as $iid => $item) {
                if( $name == '' ) {
                    $products[] = $item;
                    $name = $item['name'] . ($item['input_name'] != '' ? ' - ' . $item['input_name'] : '');
                }
                $item['quantity'] = (float)$item['quantity'];
                if( $status['status'] == 10 ) {
                    $queued_items[] = $item;
                } else {
                    $ordered_items[] = $item;
                }
            }
        }
    }

    //
    // Get the list products
    //
    $strsql = "SELECT "
        . "ciniki_foodmarket_products.name, "
        . "ciniki_foodmarket_product_inputs.id, "
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
        . "ciniki_foodmarket_product_outputs.pio_name, "
        . "1 AS unit_quantity, "
        . "1 AS weight_quantity "
        . "FROM ciniki_foodmarket_product_inputs "
        . "INNER JOIN ciniki_foodmarket_product_outputs ON ("
            . "ciniki_foodmarket_product_inputs.id  = ciniki_foodmarket_product_outputs.input_id "
            . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "INNER JOIN ciniki_foodmarket_products ON ("
            . "ciniki_foodmarket_product_inputs.product_id = ciniki_foodmarket_products.id "
            . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_foodmarket_product_inputs.id = '" . ciniki_core_dbQuote($ciniki, $args['input_id']) . "' "
        . "AND ciniki_foodmarket_product_inputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "ORDER BY ciniki_foodmarket_product_inputs.sku, ciniki_foodmarket_product_outputs.pio_name "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'inputs', 'fname'=>'id', 
            'fields'=>array('id', 'product_id', 'sku', 'name', 'input_name', 'itype', 'units', 'flags', 
                'min_quantity', 'inc_quantity', 'case_cost', 'half_cost', 'unit_cost', 'case_units')),
        array('container'=>'outputs', 'fname'=>'output_id', 
            'fields'=>array('id'=>'output_id', 'pio_name', 'otype', 'unit_quantity', 'weight_quantity')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $products = array();
    if( isset($rc['inputs']) ) {
        $products = $rc['inputs'];
    }

    //
    // Prepare the inputs, calculate how much of each should be ordered, etc.
    //
    if( count($products) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'prepareSuppliedOrderInputs');
        $rc = ciniki_foodmarket_prepareSuppliedOrderInputs($ciniki, $args['business_id'], $products);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $products = $rc['inputs'];
    }

    //
    // FIXME: Load recent invoiced items
    //

    return array('stat'=>'ok', 'name'=>$name, 'products'=>$products, 'queued_items'=>$queued_items, 'ordered_items'=>$ordered_items);
}
?>
