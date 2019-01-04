<?php
//
// Description
// -----------
// This method will return a list of favourites for tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Order Date for.
//
// Returns
// -------
//
function ciniki_foodmarket_queueList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer ID'),
        'customers'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customers'),
        'object'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object'),
        'object_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object ID'),
        'quantity'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Quantity'),
        'add_quantity'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Quantity'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['tnid'], 'ciniki.foodmarket.queueList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $time_format = ciniki_users_timeFormat($ciniki, 'php');

    $dt = new DateTime('now', new DateTimeZone('UTC'));

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'poma', 'private', 'maps');
    $rc = ciniki_poma_maps($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rsp = array('stat'=>'ok');

    //
    // Check if queue object should be updated for a customer
    //
    if( isset($args['object']) && $args['object'] != '' 
        && isset($args['object_id']) && $args['object_id'] > 0 
        && isset($args['customer_id']) && $args['customer_id'] > 0 
        ) {
        if( !isset($args['quantity']) ) {
            $args['quantity'] = 1;
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'poma', 'private', 'queueUpdateObject');
        $rc = ciniki_poma_queueUpdateObject($ciniki, $args['tnid'], $args);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }


    //
    // Get the list of favourites for a customer and the number of times they've ordered them.
    //
    if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
        $strsql = "SELECT ciniki_poma_queued_items.id, "
            . "ciniki_poma_queued_items.object, "
            . "ciniki_poma_queued_items.object_id, "
            . "ciniki_poma_queued_items.description, "
            . "ciniki_poma_queued_items.status, "
            . "ciniki_poma_queued_items.status AS status_text, "
            . "ciniki_poma_queued_items.quantity, "
            . "SUM(ciniki_poma_order_items.total_amount) AS deposited_amount "
            . "FROM ciniki_poma_queued_items "
            . "LEFT JOIN ciniki_poma_orders ON ("
                . "ciniki_poma_orders.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
                . "AND ciniki_poma_orders.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_poma_order_items ON ("
                . "ciniki_poma_orders.id = ciniki_poma_order_items.order_id "
                . "AND ciniki_poma_queued_items.id = ciniki_poma_order_items.object_id "
                . "AND ciniki_poma_order_items.object = 'ciniki.poma.queueditem' " 
                . "AND (ciniki_poma_order_items.flags&0x40) = 0x40 "
                . "AND ciniki_poma_order_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_poma_queued_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_poma_queued_items.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND ciniki_poma_queued_items.status < 90 "
            . "GROUP BY ciniki_poma_queued_items.id "
            . "ORDER BY ciniki_poma_queued_items.description "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.poma', array(
            array('container'=>'items', 'fname'=>'id', 'fields'=>array('id', 'object', 'object_id', 'description', 'quantity', 'status', 'status_text', 'deposited_amount'),
                'maps'=>array('status_text'=>$maps['queueditem']['status']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['items']) ) {
            $rsp['customer_queue'] = array();
        } else {
            $rsp['customer_queue'] = $rc['items'];
            foreach($rsp['customer_queue'] as $qid => $q) {
                $rsp['customer_queue'][$qid]['quantity'] = (float)$q['quantity'];
                if( $q['deposited_amount'] > 0 ) {
                    $rsp['customer_queue'][$qid]['deposited_amount_display'] = '$' . number_format($q['deposited_amount'], 2);
                } else {
                    $rsp['customer_queue'][$qid]['deposited_amount_display'] = '';
                }
            }
        }
    } else {
        //
        // Get the list of ordered items
        //
/*        $strsql = "SELECT CONCAT_WS('-', ciniki_poma_queued_items.object, ciniki_poma_queued_items.object_id) AS oid, "
            . "ciniki_poma_queued_items.description, "
            . "SUM(ciniki_poma_queued_items.quantity) AS quantity "
            . "FROM ciniki_poma_queued_items "
            . "WHERE ciniki_poma_queued_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_poma_queued_items.status = 40 "
            . "GROUP BY oid, status "
            . "ORDER BY ciniki_poma_queued_items.description "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.poma', array(
            array('container'=>'items', 'fname'=>'oid', 'fields'=>array('id'=>'oid', 'description', 'quantity')),
            ));
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['items']) ) {
            $rsp['queue_ordered'] = array();
        } else {
            $rsp['queue_ordered'] = $rc['items'];
            foreach($rsp['queue_ordered'] as $qid => $q) {
                $rsp['queue_ordered'][$qid]['quantity'] = (float)$q['quantity'];
            }
        } */

        //
        // Looked the queued items 
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
            . "ciniki_poma_queued_items.status, "
            . "SUM(ciniki_poma_queued_items.quantity) AS quantity "
            . "FROM ciniki_poma_queued_items "
            . "INNER JOIN ciniki_foodmarket_product_outputs ON ("
                . "ciniki_poma_queued_items.object_id  = ciniki_foodmarket_product_outputs.id "
                . "AND ciniki_foodmarket_product_outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_foodmarket_product_inputs ON ("
                . "ciniki_foodmarket_product_outputs.input_id = ciniki_foodmarket_product_inputs.id "
                . "AND ciniki_foodmarket_product_inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_foodmarket_products ON ("
                . "ciniki_foodmarket_product_inputs.product_id = ciniki_foodmarket_products.id "
                . "AND ciniki_foodmarket_products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//                . "AND ciniki_foodmarket_products.supplier_id = '" . ciniki_core_dbQuote($ciniki, $args['supplier_id']) . "' "
                . ") "
            . "WHERE ciniki_poma_queued_items.status < 90 "
            . "AND ciniki_poma_queued_items.object = 'ciniki.foodmarket.output' "
            . "AND ciniki_poma_queued_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY ciniki_poma_queued_items.status, ciniki_foodmarket_product_outputs.id "
            . "ORDER BY ciniki_poma_queued_items.status, ciniki_foodmarket_product_inputs.sku, ciniki_foodmarket_product_outputs.pio_name "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
            array('container'=>'status', 'fname'=>'status', 'fields'=>array()),
            array('container'=>'inputs', 'fname'=>'id', 
                'fields'=>array('id', 'product_id', 'sku', 'name', 'input_name', 'itype', 'units', 'flags', 
                    'min_quantity', 'inc_quantity', 'case_cost', 'half_cost', 'unit_cost', 'case_units')),
            array('container'=>'outputs', 'fname'=>'output_id', 
                'fields'=>array('id'=>'output_id', 'pio_name', 'otype', 'quantity')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $queued_inputs = array();
        if( isset($rc['status']['10']['inputs']) ) {
            $queued_inputs = $rc['status']['10']['inputs'];
            foreach($queued_inputs as $iid => $input) {
                //
                // Setup the weight and unit quantity for each output
                //
                foreach($input['outputs'] as $oid => $output) {
                    if( $input['itype'] == 10 ) {
                        $queued_inputs[$iid]['outputs'][$oid]['weight_quantity'] = $output['quantity'];
                        $queued_inputs[$iid]['outputs'][$oid]['unit_quantity'] = 0;
                    } else {
                        $queued_inputs[$iid]['outputs'][$oid]['weight_quantity'] = 0;
                        $queued_inputs[$iid]['outputs'][$oid]['unit_quantity'] = $output['quantity'];
                    }
                }
            }
        }
        $ordered_inputs = array();
        if( isset($rc['status']['40']['inputs']) ) {
            $ordered_inputs = $rc['status']['40']['inputs'];
            foreach($ordered_inputs as $iid => $input) {
                //
                // Setup the weight and unit quantity for each output
                //
                foreach($input['outputs'] as $oid => $output) {
                    if( $input['itype'] == 10 ) {
                        $ordered_inputs[$iid]['outputs'][$oid]['weight_quantity'] = $output['quantity'];
                        $ordered_inputs[$iid]['outputs'][$oid]['unit_quantity'] = 0;
                    } else {
                        $ordered_inputs[$iid]['outputs'][$oid]['weight_quantity'] = 0;
                        $ordered_inputs[$iid]['outputs'][$oid]['unit_quantity'] = $output['quantity'];
                    }
                }
            }
        }

        //
        // Prepare the inputs, calculate how much of each should be ordered, etc.
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'prepareSuppliedOrderInputs');
        $rc = ciniki_foodmarket_prepareSuppliedOrderInputs($ciniki, $args['tnid'], $queued_inputs);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['queued_items'] = $rc['inputs'];

        $rc = ciniki_foodmarket_prepareSuppliedOrderInputs($ciniki, $args['tnid'], $ordered_inputs);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['queue_ordered'] = $rc['inputs'];
    }

    //
    // Get the list of customers with favourites
    //
    if( isset($args['customers']) && $args['customers'] == 'yes' ) {
        $strsql = "SELECT ciniki_poma_queued_items.customer_id, "
            . "ciniki_customers.display_name, "
            . "COUNT(ciniki_poma_queued_items.id) AS num_items "
            . "FROM ciniki_poma_queued_items "
            . "LEFT JOIN ciniki_customers ON ("
                . "ciniki_poma_queued_items.customer_id = ciniki_customers.id "
                . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_poma_queued_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_poma_queued_items.status < 90 "
            . "GROUP BY customer_id "
            . "ORDER BY ciniki_customers.display_name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.poma', array(
            array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array('id'=>'customer_id', 'display_name', 'num_items')),
            ));
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['customers']) ) {
            $rsp['customers'] = array();
        } else {
            $rsp['customers'] = $rc['customers'];
        }
    }

    return $rsp;
}
?>
