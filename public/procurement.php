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
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'suppliers', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $supplier_ids = array();
    if( isset($rc['suppliers']) ) {
        $rsp['procurement_suppliers'] = $rc['suppliers'];
        foreach($rsp['procurement_suppliers'] as $supplier) {
            $supplier_ids[] = $supplier['id'];
        }
    }

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
            . "FROM ciniki_foodmarket_products "
            . "INNER JOIN ciniki_foodmarket_product_inputs ON ("
                . "ciniki_foodmarket_products.id = ciniki_foodmarket_product_inputs.product_id "
                . "AND ciniki_foodmarket_product_inputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "INNER JOIN ciniki_foodmarket_product_outputs ON ("
                . "ciniki_foodmarket_product_inputs.id = ciniki_foodmarket_product_outputs.input_id "
                . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "INNER JOIN ciniki_poma_orders ON ("
                . "ciniki_poma_orders.date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
                . "AND ciniki_poma_orders.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "INNER JOIN ciniki_poma_order_items ON ("
                . "ciniki_poma_orders.id = ciniki_poma_order_items.order_id "
                . "AND ciniki_poma_order_items.object = 'ciniki.foodmarket.output' "
                . "AND ciniki_foodmarket_product_outputs.id = ciniki_poma_order_items.object_id "
                . "AND ciniki_poma_order_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE ciniki_foodmarket_products.supplier_id = '" . ciniki_core_dbQuote($ciniki, $args['supplier_id']) . "' "
            . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "GROUP BY ciniki_foodmarket_product_inputs.id, ciniki_foodmarket_product_outputs.id "
            . "ORDER BY ciniki_foodmarket_product_outputs.pio_name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
            array('container'=>'inputs', 'fname'=>'id', 
                'fields'=>array('id', 'product_id', 'sku', 'name', 'input_name', 'itype', 'units', 'flags', 
                    'min_quantity', 'inc_quantity', 'case_cost', 'half_cost', 'unit_cost', 'case_units')),
            array('container'=>'outputs', 'fname'=>'output_id', 
                'fields'=>array('id'=>'output_id', 'pio_name', 'otype', 'weight_quantity', 'unit_quantity')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['inputs']) ) {
            foreach($rc['inputs'] as $input) {
                $input['requested_quantity'] = 0;
                $input['order_quantity'] = 0;
                if( $input['input_name'] != '' ) {
                    $input['name'] .= ' - ' . $input['input_name'];
                }
                $input['weight_quantity'] = 0;
                $input['unit_quantity'] = 0;
                $input['case_quantity'] = 0;
                foreach($input['outputs'] as $output) {
                    if( $output['otype'] == 10 || $output['otype'] == 71 ) {
                        $input['weight_quantity'] = bcadd($input['weight_quantity'], $output['weight_quantity'], 2);
                    } elseif( $output['otype'] == 20 || $output['otype'] == 30 || $output['otype'] == 72 ) {
                        $input['unit_quantity'] = bcadd($input['unit_quantity'], $output['unit_quantity'], 2);
                    } elseif( $output['otype'] == 50 ) {
                        $input['unit_quantity'] = bcadd($input['unit_quantity'], $input['case_units'], 2);
                    } elseif( $output['otype'] == 52 ) {
                        $input['unit_quantity'] = bcadd($input['unit_quantity'], bcdiv($input['case_units'], 2, 2), 2);
                    } elseif( $output['otype'] == 53 ) {
                        $input['unit_quantity'] = bcadd($input['unit_quantity'], bcdiv($input['case_units'], 3, 2), 2);
                    } elseif( $output['otype'] == 54 ) {
                        $input['unit_quantity'] = bcadd($input['unit_quantity'], bcdiv($input['case_units'], 4, 2), 2);
                    } elseif( $output['otype'] == 55 ) {
                        $input['unit_quantity'] = bcadd($input['unit_quantity'], bcdiv($input['case_units'], 5, 2), 2);
                    } elseif( $output['otype'] == 56 ) {
                        $input['unit_quantity'] = bcadd($input['unit_quantity'], bcdiv($input['case_units'], 6, 2), 2);
                    } elseif( $output['otype'] == 58 ) {
                        $input['unit_quantity'] = bcadd($input['unit_quantity'], bcdiv($input['case_units'], 8, 2), 2);
                    } elseif( $output['otype'] == 59 ) {
                        $input['unit_quantity'] = bcadd($input['unit_quantity'], bcdiv($input['case_units'], 9, 2), 2);
                    } elseif( $output['otype'] == 60 ) {
                        $input['unit_quantity'] = bcadd($input['unit_quantity'], bcdiv($input['case_units'], 10, 2), 2);
                    }
                }
                //
                // Skip items with no quantity
                //
                if( $input['weight_quantity'] == 0 && $input['unit_quantity'] == 0 ) {
                    continue;
                }
                //
                // Decide the quantity that should be ordered
                //
                if( $input['itype'] == 10 ) {
                    $sizetext = 'Single';
                    $input['required_quantity'] = (float)$input['weight_quantity'];
                    if( $input['required_quantity'] <= $input['min_quantity'] ) {
                        $input['order_quantity'] = (float)$input['min_quantity'];
                    } else {
                        $extra_amount = bcsub($input['required_quantity'], $input['min_quantity'], 6);
                        $multiples = ceil(bcdiv($extra_amount, $input['inc_quantity'], 6));
                        $input['order_quantity'] = (float)bcadd($input['min_quantity'], bcmul($input['inc_quantity'], $multiples, 2), 2);
                    }
                    if( ($input['units']&0x02) == 0x02 ) {
                        $stext = 'lb';
                        $ptext = 'lbs';
                    } elseif( ($input['units']&0x04) == 0x04 ) {
                        $stext = 'oz';
                        $ptext = 'ozs';
                    } elseif( ($input['units']&0x20) == 0x20 ) {
                        $stext = 'kg';
                        $ptext = 'kgs';
                    } elseif( ($input['units']&0x40) == 0x40 ) {
                        $stext = 'g';
                        $ptext = 'gs';
                    }
                    $input['required_quantity_text'] = $input['required_quantity'] . ($input['required_quantity'] > 1 ? ' ' . $ptext : ' ' . $stext);
                    $input['order_quantity_text'] = $input['order_quantity'] . ($input['order_quantity'] > 1 ? ' ' . $ptext : ' ' . $stext);
                    if( $input['min_quantity'] > 1 ) {
                        $input['cost_text'] = '$' . number_format(bcmul($input['unit_cost'], $input['min_quantity'], 2), 2) . '/' . (float)$input['min_quantity'] . '' . $stext;
                    } else {
                        $input['cost_text'] = '$' . number_format($input['unit_cost'], 2) . '/' . $stext;
                    }
                } elseif( $input['itype'] == 20 || $input['itype'] == 30 ) {
                    $sizetext = 'Single';
                    $stext = '';
                    $ptext = '';
                    if( ($input['units']&0x0200) == 0x0200 ) {
                        $stext = ' pair';
                        $ptext = ' pairs';
                    } elseif( ($input['units']&0x0400) == 0x0400 ) {
                        $stext = ' bunch';
                        $ptext = ' bunches';
                    } elseif( ($input['units']&0x0800) == 0x0800 ) {
                        $stext = ' bag';
                        $ptext = ' bags';
                    }
                    $input['required_quantity'] = (float)$input['unit_quantity'];
                    $input['required_quantity_text'] = $input['required_quantity'] . ($input['required_quantity'] > 1 ? $ptext : $stext);
                    $input['order_quantity'] = (float)$input['unit_quantity'];
                    $input['order_quantity_text'] = $input['order_quantity'] . ($input['order_quantity'] > 1 ? $ptext : $stext);
                    if( $input['itype'] == 20 ) {
                        if( ($input['units']&0x02) == 0x02 ) {
                            $input['cost_text'] = '$' . number_format($input['unit_cost'], 2) . '/lb';
                        } elseif( ($input['units']&0x04) == 0x04 ) {
                            $input['cost_text'] = '$' . number_format($input['unit_cost'], 2) . '/oz';
                        } elseif( ($input['units']&0x20) == 0x20 ) {
                            $input['cost_text'] = '$' . number_format($input['unit_cost'], 2) . '/kg';
                        } elseif( ($input['units']&0x40) == 0x40 ) {
                            $input['cost_text'] = '$' . number_format($input['unit_cost'], 2) . '/g';
                        }
                    } else {
                        if( ($input['units']&0x0200) == 0x0200 ) {
                            $input['cost_text'] = '$' . number_format($input['unit_cost'], 2) . '/pair';
                        } elseif( ($input['units']&0x0400) == 0x0400 ) {
                            $input['cost_text'] = '$' . number_format($input['unit_cost'], 2) . '/bunch';
                        } elseif( ($input['units']&0x0800) == 0x0800 ) {
                            $input['cost_text'] = '$' . number_format($input['unit_cost'], 2) . '/bag';
                        } else {
                            $input['cost_text'] = '$' . number_format($input['unit_cost'], 2) . '';
                        }
                    }
                } elseif( $input['itype'] == 50 ) {
                    $input['required_quantity'] = (float)bcdiv($input['unit_quantity'], $input['case_units'], 2);
                    $sizetext = 'Case';
                    $stext = 'case';
                    $ptext = 'cases';
                    if( ($input['units']&0x020000) == 0x020000 ) {
                        $stext = 'bushel';
                        $ptext = 'bushels';
                    }
                    if( $input['half_cost'] > 0 ) {
                        $cases = bcdiv($input['unit_quantity'], $input['case_units'], 2);
                        $full_cases = floor($cases);
                        $partial_cases = $cases - $full_cases;
                        if( $partial_cases > 0 && $partial_cases <= 0.5 ) {
                            $input['order_quantity'] = $full_cases + 0.5;
                        } elseif( $partial_cases > 0 && $partial_cases > 0.5 ) {
                            $input['order_quantity'] = $full_cases + 1;
                        }
                    } else {
                        $input['order_quantity'] = ceil($input['required_quantity']);
                    }
                    $input['required_quantity_text'] = $input['required_quantity'] . ($input['order_quantity'] > 1 ? ' ' . $ptext : ' ' . $stext) 
                        . ' (' . (float)$input['unit_quantity'] . ')';
                    $input['order_quantity_text'] = $input['order_quantity'] . ($input['order_quantity'] > 1 ? ' ' . $ptext : ' ' . $stext);
                    $input['cost_text'] = '$' . number_format($input['case_cost'], 2) . '/' . $stext;
                }
                $rsp['procurement_supplier_inputs'][] = $input;

                //
                // Add the input to the order output for copying to email
                //
                $rsp['procurement_supplier_order'][] = array(
                    'sku'=>$input['sku'],
                    'name'=>$input['name'],
                    'quantity'=>$input['order_quantity'],
                    'size'=>$sizetext,
                    );
            }
        }


        //
        // FIXME: Check for any supplier queue items
        //

        
    }


    return $rsp;
}
?>
