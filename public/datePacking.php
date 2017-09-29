<?php
//
// Description
// -----------
// This method returns the information for the packing section of the UI. 
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
function ciniki_foodmarket_datePacking($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'date_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Order Date'),
        'order_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Order'),
        'order_packed'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Order Packed'),
        'orders'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Orders'),
        'baskets'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Baskets'),
        'packing_basket_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Basket'),
        'packing_basket_item_update'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Item'),
        'packing_basket_item_quantity'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Quantity'),
        'packing_basket_output_add'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Output'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.datePacking');
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

    $rsp = array('stat'=>'ok', 'dates'=>array(), 'unpacked_orders'=>array(), 'packed_orders'=>array(), 'packing_orderitems'=>array(),
        'packing_baskets'=>array(), 'basket'=>array(), 'packing_basket_items'=>array(), 'packing_basket_outputs'=>array(),
        );

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
//        . "AND ciniki_poma_order_dates.order_date >= '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' "
//        . "GROUP BY ciniki_poma_order_dates.id "
        . "ORDER BY ciniki_poma_order_dates.order_date DESC "
        . "LIMIT 25"
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
    // Mark the order as packed
    //
    if( isset($args['order_packed']) && $args['order_packed'] == 'yes' && isset($args['order_id']) && $args['order_id'] > 0 ) {
        $strsql = "SELECT id, status "
            . "FROM ciniki_poma_orders "
            . "WHERE ciniki_poma_orders.id = '" . ciniki_core_dbQuote($ciniki, $args['order_id']) . "' "
            . "AND ciniki_poma_orders.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.poma', 'order');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( isset($rc['order']) && $rc['order']['status'] < 50 ) {
            $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.poma.order', $args['order_id'], array('status'=>50), 0x07);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    //
    // Get the items for an order
    //
    if( isset($args['order_id']) && $args['order_id'] > 0 ) {
        $strsql = "SELECT ciniki_poma_order_items.id, "
            . "ciniki_poma_order_items.parent_id, "
            . "ciniki_poma_order_items.object, "
            . "ciniki_poma_order_items.object_id, "
            . "ciniki_poma_order_items.description, "
            . "ciniki_poma_order_items.itype, "
            . "ciniki_poma_order_items.weight_quantity, "
            . "ciniki_poma_order_items.unit_quantity, "
            . "ciniki_poma_order_items.unit_amount "
            . "FROM ciniki_poma_order_items "
            . "WHERE ciniki_poma_order_items.order_id = '" . ciniki_core_dbQuote($ciniki, $args['order_id']) . "' "
            . "AND ciniki_poma_order_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "ORDER BY parent_id, ciniki_poma_order_items.packing_order DESC, description "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.poma', array(
            array('container'=>'items', 'fname'=>'id', 'fields'=>array('id', 'parent_id', 'object', 'object_id', 'description',
                'itype', 'weight_quantity', 'unit_quantity', 'unit_amount')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['items']) ) {
            foreach($rc['items'] as $iid => $item) {
                if( $item['parent_id'] > 0 && isset($rc['items'][$item['parent_id']]['description']) ) {
                    $item['description'] = $rc['items'][$item['parent_id']]['description'] . ' - ' . $item['description'];
                }
                if( $item['itype'] == 10 ) {
                    $item['quantity'] = (float)$item['weight_quantity'];
                } else {
                    $item['quantity'] = (float)$item['unit_quantity'];
                }
                if( $item['quantity'] > 0 ) {
                    $rsp['packing_orderitems'][] = $item;
                }
            }
        }
    }

    //
    // Check if orders requested
    //
    if( isset($args['orders']) && $args['orders'] == 'yes' ) {
        //
        // Get the list of orders to be packed
        //
        $strsql = "SELECT ciniki_poma_orders.id, "
            . "ciniki_poma_orders.billing_name "
            . "FROM ciniki_poma_orders "
            . "WHERE date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
            . "AND status < 50 "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "ORDER BY billing_name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.poma', array(
            array('container'=>'orders', 'fname'=>'id', 'fields'=>array('id', 'billing_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['orders']) ) {
            $rsp['unpacked_orders'] = $rc['orders'];
        }

        //
        // Get the list of orders that have been packed
        //
        $strsql = "SELECT ciniki_poma_orders.id, "
            . "ciniki_poma_orders.billing_name "
            . "FROM ciniki_poma_orders "
            . "WHERE date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
            . "AND status > 30 "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "ORDER BY billing_name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.poma', array(
            array('container'=>'orders', 'fname'=>'id', 'fields'=>array('id', 'billing_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['orders']) ) {
            $rsp['packed_orders'] = $rc['orders'];
        }
    }

    //
    // Check if baskets requested
    //
    if( isset($args['baskets']) && $args['baskets'] == 'yes' ) {
        //
        // Get the baskets for the date
        //
        $strsql = "SELECT DISTINCT ciniki_foodmarket_basket_items.basket_output_id, "
            . "ciniki_foodmarket_product_outputs.pio_name "
            . "FROM ciniki_foodmarket_basket_items "
            . "LEFT JOIN ciniki_foodmarket_product_outputs ON ("
                . "ciniki_foodmarket_basket_items.basket_output_id = ciniki_foodmarket_product_outputs.id "
                . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE ciniki_foodmarket_basket_items.date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
            . "AND ciniki_foodmarket_basket_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
            array('container'=>'baskets', 'fname'=>'basket_output_id', 'fields'=>array('id'=>'basket_output_id', 'name'=>'pio_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['baskets']) ) {
            $baskets = array();
            $basket_ids = array();
        } else {
            $baskets = $rc['baskets'];
            $basket_ids = array_keys($baskets);
        }
    }

    //
    // Get the contents of a basket
    //
    if( isset($args['packing_basket_id']) && $args['packing_basket_id'] > 0 ) {
        //
        // Load the detail for the item in the order
        //
        $strsql = "SELECT ciniki_poma_order_items.id, "
            . "ciniki_poma_order_items.uuid, "
            . "ciniki_poma_order_items.order_id, "
            . "ciniki_poma_order_items.object, "
            . "ciniki_poma_order_items.object_id, "
            . "ciniki_poma_order_items.description, "
            . "ciniki_poma_order_items.itype, "
            . "ciniki_poma_order_items.weight_quantity, "
            . "ciniki_poma_order_items.unit_quantity, "
            . "ciniki_poma_order_items.total_amount, "
            . "ciniki_poma_orders.billing_name "
            . "FROM ciniki_poma_order_items, ciniki_poma_orders "
            . "WHERE ciniki_poma_order_items.id = '" . ciniki_core_dbQuote($ciniki, $args['packing_basket_id']) . "' "
            . "AND ciniki_poma_order_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_poma_order_items.order_id = ciniki_poma_orders.id "
            . "AND ciniki_poma_orders.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.poma', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['item']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.51', 'msg'=>'Invalid order item.'));
        }
        $rsp['basket'] = $rc['item'];

        //
        // Get the order basket items
        //
        $strsql = "SELECT ciniki_poma_order_items.id, "
            . "ciniki_poma_order_items.uuid, "
            . "ciniki_poma_order_items.description, "
            . "ciniki_poma_order_items.object, "
            . "ciniki_poma_order_items.object_id, "
            . "ciniki_poma_order_items.itype, "
            . "ciniki_poma_order_items.weight_units, "
            . "ciniki_poma_order_items.weight_quantity, "
            . "ciniki_poma_order_items.unit_quantity, "
            . "ciniki_poma_order_items.unit_suffix, "
            . "ciniki_poma_order_items.unit_amount, "
            . "ciniki_foodmarket_product_outputs.retail_price_text AS price_text, "
            . "ciniki_poma_order_items.total_amount "
            . "FROM ciniki_poma_order_items "
            . "LEFT JOIN ciniki_foodmarket_product_outputs ON ("
                . "ciniki_poma_order_items.object = 'ciniki.foodmarket.output' "
                . "AND ciniki_poma_order_items.object_id = ciniki_foodmarket_product_outputs.id "
                . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE ciniki_poma_order_items.parent_id = '" . ciniki_core_dbQuote($ciniki, $args['packing_basket_id']) . "' "
            . "AND ciniki_poma_order_items.order_id = '" . ciniki_core_dbQuote($ciniki, $rsp['basket']['order_id']) . "' "
            . "AND ciniki_poma_order_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "ORDER BY ciniki_poma_order_items.description "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.poma', array(
            array('container'=>'subitems', 'fname'=>'id', 'fields'=>array('id', 'uuid', 'description', 'object', 'object_id', 
                'itype', 'weight_units', 'weight_quantity', 'unit_quantity', 'unit_suffix', 'unit_amount', 'price_text', 'total_amount')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['subitems']) ) {
            $rsp['packing_basket_items'] = $rc['subitems'];
        }

        //
        // Get the available amount remaining
        //
        $rsp['basket']['curtotal'] = 0;
        $rsp['basket']['limit'] = bcadd($rsp['basket']['total_amount'], 1, 2);
        $output_ids = array();
        foreach($rsp['packing_basket_items'] as $iid => $itm) {
            if( isset($args['packing_basket_item_update']) && $args['packing_basket_item_update'] == $itm['id'] 
                && isset($args['packing_basket_item_quantity']) && $args['packing_basket_item_quantity'] != '' 
                ) {
                if( $itm['itype'] == 10 ) {
                    $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.poma.orderitem', $itm['id'], 
                        array('weight_quantity'=>$args['packing_basket_item_quantity']), 0x07);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    $rsp['packing_basket_items'][$iid]['weight_quantity'] = $args['packing_basket_item_quantity'];
                    $itm['weight_quantity'] = $args['packing_basket_item_quantity'];
                } else {
                    $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.poma.orderitem', $itm['id'], 
                        array('unit_quantity'=>$args['packing_basket_item_quantity']), 0x07);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    $rsp['packing_basket_items'][$iid]['unit_quantity'] = $args['packing_basket_item_quantity'];
                    $itm['unit_quantity'] = $args['packing_basket_item_quantity'];
                }
            }
            if( $itm['itype'] == 10 ) {
                $rsp['basket']['curtotal'] = bcadd($rsp['basket']['curtotal'], bcmul($itm['unit_amount'], $itm['weight_quantity'], 6), 2);
                $rsp['packing_basket_items'][$iid]['quantity'] = $itm['weight_quantity'];
            } else {
                $rsp['basket']['curtotal'] = bcadd($rsp['basket']['curtotal'], bcmul($itm['unit_amount'], $itm['unit_quantity'], 6), 2);
                $rsp['packing_basket_items'][$iid]['quantity'] = $itm['unit_quantity'];
            }
            if( $itm['object'] == 'ciniki.foodmarket.output' ) {
                $output_ids[] = $itm['object_id'];
            }
        }

        //
        // Check if adding an item
        //
        if( isset($args['packing_basket_output_add']) && $args['packing_basket_output_add'] > 0 
            && !in_array($args['packing_basket_output_add'], $output_ids) 
            ) {
            //
            // Lookup the object
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'poma', 'itemLookup');
            $rc = ciniki_foodmarket_poma_itemLookup($ciniki, $args['business_id'], 
                array('object'=>'ciniki.foodmarket.output', 'object_id'=>$args['packing_basket_output_add']));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( !isset($rc['item']) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.52', 'msg'=>'Unable to add item.'));
            }
            $newitem = $rc['item'];

            //
            // Add the item
            //
            $newitem['parent_id'] = $args['packing_basket_id'];
            $newitem['order_id'] = $rsp['basket']['order_id'];
            $newitem['quantity'] = 1;
            $newitem['price_text'] = $newitem['retail_price_text'];
            if( $newitem['itype'] == 10 ) {
                $newitem['weight_quantity'] = 1;
                $rsp['basket']['curtotal'] = bcadd($rsp['basket']['curtotal'], bcmul($newitem['unit_amount'], $newitem['weight_quantity'], 6), 2);
            } else {
                $newitem['unit_quantity'] = 1;
                $rsp['basket']['curtotal'] = bcadd($rsp['basket']['curtotal'], bcmul($newitem['unit_amount'], $newitem['unit_quantity'], 6), 2);
            }
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.poma.orderitem', $newitem, 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.poma');
                return $rc;
            }
            $rsp['packing_basket_items'][] = $newitem;
            $output_ids[] = $args['packing_basket_output_add'];

            //
            // Sort items
            //
            uasort($rsp['packing_basket_items'], function($a, $b) {
                return strcasecmp($a['description'], $b['description']);
                });
        }

        $rsp['basket']['available'] = bcsub($rsp['basket']['limit'], $rsp['basket']['curtotal'], 2);
        $rsp['basket']['curtotal_text'] = '$' . number_format($rsp['basket']['curtotal'], 2);
        $rsp['basket']['total_percent'] = bcmul(bcdiv($rsp['basket']['curtotal'], $rsp['basket']['total_amount'], 6), 100, 0) . '%';

        //
        // Get the remaining available output for baskets for the week
        //
        $strsql = "SELECT ciniki_foodmarket_product_outputs.id, "
            . "ciniki_foodmarket_product_outputs.product_id, "
            . "ciniki_foodmarket_products.supplier_id, "
            . "IFNULL(ciniki_foodmarket_suppliers.code, '') AS supplier_code, "
            . "ciniki_foodmarket_product_outputs.pio_name AS name, "
            . "IFNULL(MAX(ciniki_poma_order_dates.order_date), '') AS last_order_date, "
            . "DATEDIFF(NOW(), MAX(ciniki_poma_order_dates.order_date)) AS days "
            . "FROM ciniki_foodmarket_product_outputs "
            . "INNER JOIN ciniki_foodmarket_basket_items ON ("
                . "ciniki_foodmarket_product_outputs.id = ciniki_foodmarket_basket_items.item_output_id "
                . "AND ciniki_foodmarket_basket_items.date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
                . "AND ciniki_foodmarket_basket_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_poma_order_dates ON ("
                . "ciniki_foodmarket_basket_items.date_id = ciniki_poma_order_dates.id "
                . "AND ciniki_poma_order_dates.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_products ON ("
                . "ciniki_foodmarket_product_outputs.product_id = ciniki_foodmarket_products.id "
                . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_suppliers ON ("
                . "ciniki_foodmarket_products.supplier_id = ciniki_foodmarket_suppliers.id "
                . "AND ciniki_foodmarket_suppliers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE ciniki_foodmarket_product_outputs.otype IN (71, 72) "
            . "";
        if( count($output_ids) > 0 ) {
            $strsql .= "AND ciniki_foodmarket_product_outputs.id NOT IN (" . ciniki_core_dbQuoteIDs($ciniki, $output_ids) . ") ";
        }
        $strsql .= "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "GROUP BY ciniki_foodmarket_product_outputs.id "
            . "ORDER BY supplier_code, pio_name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.poma', array(
            array('container'=>'outputs', 'fname'=>'id', 
                'fields'=>array('id', 'product_id', 'supplier_id', 'supplier_code', 'name'=>'name', 'last_order_date', 'days'),
                'utctotz'=>array('last_order_date'=>array('timezone'=>'UTC', 'format'=>$date_format)),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['outputs']) ) {
            $rsp['packing_basket_outputs'] = $rc['outputs'];
        }
    }

    if( isset($args['baskets']) && $args['baskets'] == 'yes' && count($baskets) > 0 ) {
        //
        // Get the customers who ordered baskets and calculate totals of their items.
        //
        $strsql = "SELECT ciniki_poma_orders.id AS order_id, "
            . "ciniki_poma_orders.billing_name, "
            . "baskets.id AS order_basket_id, "
            . "baskets.object_id AS basket_id, "
            . "baskets.unit_amount AS basket_amount, "
            . "items.id AS item_id, "
            . "items.itype, "
            . "items.weight_quantity, "
            . "items.unit_quantity, "
            . "items.unit_amount "
            . "FROM ciniki_poma_orders " 
            . "INNER JOIN ciniki_poma_order_items AS baskets ON ("
                . "ciniki_poma_orders.id = baskets.order_id "
                . "AND baskets.object = 'ciniki.foodmarket.output' "
                . "AND baskets.object_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $basket_ids) . ") "
                . "AND baskets.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_poma_order_items AS items ON ("
                . "baskets.id = items.parent_id "
                . "AND items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE ciniki_poma_orders.date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
            . "AND ciniki_poma_orders.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "ORDER BY order_id, order_basket_id "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.poma', array(
            array('container'=>'baskets', 'fname'=>'order_basket_id', 'fields'=>array('order_id', 'billing_name', 'order_basket_id', 'basket_amount', 'basket_id')),
            array('container'=>'items', 'fname'=>'item_id', 'fields'=>array('id'=>'item_id', 'itype', 'weight_quantity', 'unit_quantity', 'unit_amount')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['baskets']) ) {   
            foreach($rc['baskets'] as $basket) {
                $basket_total = 0;
                if( isset($basket['items']) ) {
                    foreach($basket['items'] as $item) {
                        if( $item['itype'] == 10 ) {
                            $basket_total = bcadd($basket_total, bcmul($item['weight_quantity'], $item['unit_amount'], 6), 2);
                        } else {
                            $basket_total = bcadd($basket_total, bcmul($item['unit_quantity'], $item['unit_amount'], 6), 2);
                        }
                    }
                }
                $rsp['packing_baskets'][] = array(
                    'order_id'=>$basket['order_id'],
                    'billing_name'=>$basket['billing_name'],
                    'basket_id'=>$basket['basket_id'],
                    'basket_name'=>(isset($baskets[$basket['basket_id']]['name']) ? $baskets[$basket['basket_id']]['name'] : ''),
                    'basket_amount'=>$basket['basket_amount'],
                    'order_basket_id'=>$basket['order_basket_id'],
                    'total_amount'=>$basket_total,
                    'total_percent'=>bcmul(bcdiv($basket_total, $basket['basket_amount'], 6), 100, 0) . '%',
                    );
            }
        }
    }

    return $rsp;
}
?>
