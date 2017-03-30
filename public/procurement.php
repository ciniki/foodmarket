<?php
//
// Description
// -----------
// This method is used to return the information required for the date limited product manager. 
// Currently it only works for the single date, but in the future a date range could be used instead.
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
function ciniki_foodmarket_procurement($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'date_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Date'),
        'supplier_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Supplier'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.procurement');
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

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
    $rsp = array('stat'=>'ok', 'procurement_suppliers'=>array(), 'procurement_supplier_inputs'=>array(), 'procurement_supplier_order'=>array(), 'procurement_supplier_queue'=>array());

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    $dt = new DateTime('now', new DateTimezone($intl_timezone));
    //
    // If the date wasn't set, then choose the closest date to now
    //
    if( !isset($args['date_id']) || $args['date_id'] == 0 ) {
        $strsql = "SELECT id, status, ABS(DATEDIFF(NOW(), order_date)) AS age "
            . "FROM ciniki_poma_order_dates "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_poma_order_dates.order_date >= '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' "
            . "ORDER BY age ASC "
            . "LIMIT 1 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.poma', 'date');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['date']['id']) ) {
            return $rsp;
        }
        $args['date_id'] = $rc['date']['id'];
        $rsp['date_id'] = $rc['date']['id'];
        $rsp['date_status'] = $rc['date']['status'];
    } else {
        $strsql = "SELECT id, status "
            . "FROM ciniki_poma_order_dates "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "LIMIT 1 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.poma', 'date');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['date']['id']) ) {
            return $rsp;
        }
        $rsp['date_status'] = $rc['date']['status'];
    }

    $strsql = "SELECT ciniki_poma_order_dates.id, "
        . "ciniki_poma_order_dates.order_date, "
        . "ciniki_poma_order_dates.display_name, "
        . "ciniki_poma_order_dates.status, "
        . "ciniki_poma_order_dates.flags "
        . "FROM ciniki_poma_order_dates "
        . "WHERE ciniki_poma_order_dates.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_poma_order_dates.order_date >= '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' "
        . "GROUP BY ciniki_poma_order_dates.id "
        . "ORDER BY ciniki_poma_order_dates.order_date DESC "
        . "LIMIT 15"
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.poma', array(
        array('container'=>'dates', 'fname'=>'id', 
            'fields'=>array('id', 'order_date', 'display_name', 'status', 'flags'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['dates']) || count($rc['dates']) < 1 ) {
        return $rsp;
    }
    $rsp['dates'] = $rc['dates'];
    $found = 0;
    foreach($rsp['dates'] as $did => $date) {
        $rsp['dates'][$did]['name_status'] = $date['display_name'] . ' - ' . $poma_maps['orderdate']['status'][$date['status']];
        if( $date['id'] == $args['date_id'] ) {
            $found = 1;
        }
        $last_date = $date;
    }
    if( $found == 0 ) {
        $args['date_id'] = $last_date['id'];
        $args['date_id'] = $last_date['id'];
        $rsp['date_id'] = $last_date['id'];
        $rsp['date_status'] = $last_date['status'];
    }

    //
    // Get the list of suppliers with products ordered for this week
    //
    $strsql = "SELECT DISTINCT ciniki_foodmarket_suppliers.id, "
        . "ciniki_foodmarket_suppliers.name "
        . "FROM ciniki_poma_orders "
        . "INNER JOIN ciniki_poma_order_items ON ("
            . "ciniki_poma_orders.id = ciniki_poma_order_items.order_id "
            . "AND ciniki_poma_order_items.object = 'ciniki.foodmarket.output' "
            . "AND ciniki_poma_order_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "INNER JOIN ciniki_foodmarket_product_outputs ON ("
            . "ciniki_poma_order_items.object_id = ciniki_foodmarket_product_outputs.id "
            . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "INNER JOIN ciniki_foodmarket_products ON ("
            . "ciniki_foodmarket_product_outputs.product_id = ciniki_foodmarket_products.id "
            . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "INNER JOIN ciniki_foodmarket_suppliers ON ("
            . "ciniki_foodmarket_products.supplier_id = ciniki_foodmarket_suppliers.id "
            . "AND ciniki_foodmarket_suppliers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_poma_orders.date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
        . "AND ciniki_poma_orders.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "ORDER BY ciniki_foodmarket_suppliers.name "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'suppliers', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $supplier_ids = array();
    $suppliers = array();
    if( isset($rc['suppliers']) ) {
        $suppliers = $rc['suppliers'];
    }

    //
    // The list of suppliers with items in the queue
    //
    $strsql = "SELECT DISTINCT ciniki_foodmarket_suppliers.id, "
        . "ciniki_foodmarket_suppliers.name "
        . "FROM ciniki_poma_queued_items "
        . "INNER JOIN ciniki_foodmarket_product_outputs ON ("
            . "ciniki_poma_queued_items.object_id = ciniki_foodmarket_product_outputs.id "
            . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "INNER JOIN ciniki_foodmarket_products ON ("
            . "ciniki_foodmarket_product_outputs.product_id = ciniki_foodmarket_products.id "
            . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "INNER JOIN ciniki_foodmarket_suppliers ON ("
            . "ciniki_foodmarket_products.supplier_id = ciniki_foodmarket_suppliers.id "
            . "AND ciniki_foodmarket_suppliers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_poma_queued_items.status = 40 "
        . "AND ciniki_poma_queued_items.object = 'ciniki.foodmarket.output' "
        . "AND ciniki_poma_queued_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "ORDER BY ciniki_foodmarket_suppliers.name "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'suppliers', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $supplier_ids = array();
    if( isset($rc['suppliers']) ) {
        foreach($rc['suppliers'] as $supplier) {
            if( !isset($suppliers[$supplier['id']]) ) {
                $suppliers[$supplier['id']] = $supplier;
            }
        }
    }

    //
    // Get the list of supplier ids
    //
    foreach($suppliers as $supplier) {
        $supplier_ids[] = $supplier['id'];
        $rsp['procurement_suppliers'][] = $supplier;
    }

    $rsp['procurement_suppliers'][] = array('id'=>0, 'name'=>'Misc Items');
    
    //
    // Get the list of products to order from the supplier
    //
    if( isset($args['supplier_id']) && $args['supplier_id'] > 0 && in_array($args['supplier_id'], $supplier_ids) ) {
        //
        // Get the products ordered
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
            . "SUM(ciniki_poma_order_items.weight_quantity) AS weight_quantity, "
            . "SUM(ciniki_poma_order_items.unit_quantity) AS unit_quantity "
            . "FROM ciniki_poma_orders "
            . "INNER JOIN ciniki_poma_order_items ON ("
                . "ciniki_poma_orders.id = ciniki_poma_order_items.order_id "
                . "AND ciniki_poma_order_items.object = 'ciniki.foodmarket.output' "
                . "AND ciniki_poma_order_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "INNER JOIN ciniki_foodmarket_product_outputs ON ("
                . "ciniki_poma_order_items.object_id  = ciniki_foodmarket_product_outputs.id "
                . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "INNER JOIN ciniki_foodmarket_product_inputs ON ("
                . "ciniki_foodmarket_product_outputs.input_id = ciniki_foodmarket_product_inputs.id "
                . "AND ciniki_foodmarket_product_inputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "INNER JOIN ciniki_foodmarket_products ON ("
                . "ciniki_foodmarket_product_inputs.product_id = ciniki_foodmarket_products.id "
                . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND ciniki_foodmarket_products.supplier_id = '" . ciniki_core_dbQuote($ciniki, $args['supplier_id']) . "' "
                . ") "
            . "WHERE ciniki_poma_orders.date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
            . "AND ciniki_poma_orders.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
//            . "AND NOT ISNULL(ciniki_poma_order_items.id) "
            . "GROUP BY ciniki_foodmarket_product_outputs.id "
            . "ORDER BY ciniki_foodmarket_product_outputs.pio_name "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
            array('container'=>'inputs', 'fname'=>'id', 
                'fields'=>array('id', 'product_id', 'sku', 'name', 'input_name', 'itype', 'units', 'flags', 
                    'min_quantity', 'inc_quantity', 'case_cost', 'half_cost', 'unit_cost', 'case_units')),
            array('container'=>'outputs', 'fname'=>'output_id', 
                'fields'=>array('id'=>'output_id', 'pio_name', 'otype', 'weight_quantity', 'unit_quantity')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $inputs = array();
        if( isset($rc['inputs']) ) {
            $inputs = $rc['inputs'];
        }

        //
        // Looked the queued items that required ordering
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
            . "SUM(ciniki_poma_queued_items.quantity) AS quantity "
            . "FROM ciniki_poma_queued_items "
            . "INNER JOIN ciniki_foodmarket_product_outputs ON ("
                . "ciniki_poma_queued_items.object_id  = ciniki_foodmarket_product_outputs.id "
                . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "INNER JOIN ciniki_foodmarket_product_inputs ON ("
                . "ciniki_foodmarket_product_outputs.input_id = ciniki_foodmarket_product_inputs.id "
                . "AND ciniki_foodmarket_product_inputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "INNER JOIN ciniki_foodmarket_products ON ("
                . "ciniki_foodmarket_product_inputs.product_id = ciniki_foodmarket_products.id "
                . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND ciniki_foodmarket_products.supplier_id = '" . ciniki_core_dbQuote($ciniki, $args['supplier_id']) . "' "
                . ") "
            . "WHERE ciniki_poma_queued_items.status = 40 "
            . "AND ciniki_poma_queued_items.object = 'ciniki.foodmarket.output' "
            . "AND ciniki_poma_queued_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "GROUP BY ciniki_foodmarket_product_outputs.id "
            . "ORDER BY ciniki_foodmarket_product_inputs.sku, ciniki_foodmarket_product_outputs.pio_name "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
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
        if( isset($rc['inputs']) ) {
            $queued_inputs = $rc['inputs'];
            foreach($queued_inputs as $input) {
                //
                // Setup the weight and unit quantity for each output
                //
                foreach($input['outputs'] as $oid => $output) {
                    if( $input['itype'] == 10 ) {
                        $input['outputs'][$oid]['weight_quantity'] = $output['quantity'];
                        $input['outputs'][$oid]['unit_quantity'] = 0;
                    } else {
                        $input['outputs'][$oid]['weight_quantity'] = 0;
                        $input['outputs'][$oid]['unit_quantity'] = $output['quantity'];
                    }
                }
                //
                // Attach input/outpus to main inputs array
                //
                if( !isset($inputs[$input['id']]) ) {
                    $inputs[$input['id']] = $input;
                } else {
                    //
                    // The input already exists, update output quantities or add the outputs
                    //
                    foreach($input['outputs'] as $oid => $output) {
                        if( isset($inputs[$input['id']]['outputs'][$output['id']]) ) {
                            $inputs[$input['id']]['outputs'][$output['id']]['weight_quantity'] += $output['weight_quantity'];
                            $inputs[$input['id']]['outputs'][$output['id']]['unit_quantity'] += $output['unit_quantity'];
                        } else {
                            $inputs[$input['id']]['outputs'][$output['id']] = $output;
                        }
                    }
                }
            }
        }

        //
        // Prepare the inputs, calculate how much of each should be ordered, etc.
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'prepareSuppliedOrderInputs');
        $rc = ciniki_foodmarket_prepareSuppliedOrderInputs($ciniki, $args['business_id'], $inputs);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['procurement_supplier_inputs'] = $rc['inputs'];

        //
        // Add the inputs to the order output for copying to email
        //
        foreach($rc['inputs'] as $input) {
            $rsp['procurement_supplier_order'][] = array(
                'sku'=>$input['sku'],
                'name'=>$input['name'],
                'quantity'=>$input['order_quantity'],
                'size'=>$input['sizetext'],
                );
        }
    }

    if( isset($args['supplier_id']) && $args['supplier_id'] == 0 ) {
        //
        // Get the list of misc items on invoices
        //
        $strsql = "SELECT ciniki_poma_order_items.id, "
            . "ciniki_poma_orders.billing_name, "
            . "ciniki_poma_order_items.code AS sku, "
            . "ciniki_poma_order_items.description AS name, "
            . "ciniki_poma_order_items.itype, "
            . "ciniki_poma_order_items.weight_units, "
            . "ciniki_poma_order_items.unit_suffix, "
            . "ciniki_poma_order_items.weight_quantity, "
            . "ciniki_poma_order_items.unit_quantity "
            . "FROM ciniki_poma_orders "
            . "INNER JOIN ciniki_poma_order_items ON ("
                . "ciniki_poma_orders.id = ciniki_poma_order_items.order_id "
                . "AND ciniki_poma_order_items.object = '' "
                . "AND ciniki_poma_order_items.object_id = 0 "
                . "AND ciniki_poma_order_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND (ciniki_poma_order_items.flags&0xc8) = 0 "
                . ") "
            . "WHERE ciniki_poma_orders.date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
            . "AND ciniki_poma_orders.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "ORDER BY name "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
            array('container'=>'inputs', 'fname'=>'id', 
                'fields'=>array('id', 'billing_name', 'sku', 'name', 'itype', 'weight_units', 'weight_quantity', 'unit_quantity')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $inputs = array();
        if( isset($rc['inputs']) ) {
            $rsp['procurement_misc_items'] = $rc['inputs'];
            foreach($rsp['procurement_misc_items'] as $iid => $item) {
                if( $item['itype'] == 10 ) {
                    $rsp['procurement_misc_items'][$iid]['quantity'] = (float)$item['weight_quantity'];
                } else {
                    $rsp['procurement_misc_items'][$iid]['quantity'] = (float)$item['unit_quantity'];
                }
            }
        }
    }

    return $rsp;
}
?>
