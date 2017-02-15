<?php
//
// Description
// -----------
// This method returns the information to layout baskets for a date.
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
function ciniki_foodmarket_dateBaskets($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'date_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Order Date'),
        'datestatus'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Date Status'),
        'basket_output_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Basket'),
        'item_output_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Item'),
        'remove_item_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Remove Item'),
        'quantity'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Quantity'),
        'outputs'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Products'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.dateBaskets');
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

    $rsp = array('stat'=>'ok', 'dates'=>array(), 'baskets'=>array(), 'baskets_items'=>array(), 'baskets_recent_outputs'=>array(), 'baskets_outputs'=>array(),
        'nplists'=>array('basket_items'=>array(), 'baskets_recent_outputs'=>array(), 'baskets_outputs'=>array()),
        );

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'basketsUpdateOrders');

    //
    // If the date wasn't set, then choose the closest date to now
    //
    if( !isset($args['date_id']) || $args['date_id'] == 0 ) {
        $strsql = "SELECT id, status, ABS(DATEDIFF(NOW(), order_date)) AS age "
            . "FROM ciniki_poma_order_dates "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
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

    //
    // Check if date status is set to subscriptions
    //
    if( isset($args['datestatus']) && $args['datestatus'] == 'substitutions' && $rsp['date_status'] < 30 ) {
        //
        // Get the list of existing date available items for this order date
        //
        $strsql = "SELECT ciniki_foodmarket_date_items.output_id AS id, "
            . "ciniki_foodmarket_date_items.quantity "
            . "FROM ciniki_foodmarket_date_items "
            . "WHERE ciniki_foodmarket_date_items.date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
            . "AND ciniki_foodmarket_date_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.poma', array(
            array('container'=>'items', 'fname'=>'id', 'fields'=>array('id', 'quantity')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $edateitems = array();
        if( isset($rc['items']) ) {
            $edateitems = $rc['items'];
        }

        //
        // Get the list of basket items for this order date, and their matching public items
        //
        $strsql = "SELECT o2.id, o2.otype, o2.flags "
            . "FROM ciniki_foodmarket_basket_items AS bitems "
            . "LEFT JOIN ciniki_foodmarket_product_outputs AS o1 ON ("
                . "bitems.item_output_id = o1.id "
                . "AND o1.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_outputs AS o2 ON ("
                . "o1.input_id = o2.input_id "
                . "AND ((o1.otype = 71 AND o2.otype = 10) OR (o1.otype = 72 AND o2.otype = 30)) "
                . "AND (o2.flags&0x0200) = 0x0200 "
                . "AND o2.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE bitems.date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
            . "AND bitems.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.poma', array(
            array('container'=>'items', 'fname'=>'id', 'fields'=>array('id', 'otype', 'flags')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $bdateitems = array();
        if( isset($rc['items']) ) {
            $bdateitems = $rc['items'];
        }

        //
        // Check if any are date only items, and add to date availability.
        //
        foreach($bdateitems as $item) {
            if( !isset($edateitems[$item['id']]) ) {
                $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.foodmarket.dateitem', array('date_id'=>$args['date_id'], 'output_id'=>$item['id'], 'quantity'=>0), 0x07);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
        //
        // Set the date to allow substitutions
        //
        $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.poma.orderdate', $args['date_id'], array('status'=>30), 0x07);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['date_status'] = 30;
    }


    $dt = new DateTime('now', new DateTimezone($intl_timezone));

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
    // FIXME: Check if date is still open for new items
    //

    //
    // Check if an item is to be removed from baskets
    //
    if( isset($args['remove_item_id']) && $args['remove_item_id'] != '' && $args['remove_item_id'] > 0 ) {
        $strsql = "SELECT id, uuid, quantity "
            . "FROM ciniki_foodmarket_basket_items "
            . "WHERE date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
            . "AND item_output_id = '" . ciniki_core_dbQuote($ciniki, $args['remove_item_id']) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
            $items = $rc['rows'];
            // 
            // Check if any items still have a quantity
            //
            foreach($items as $item) {
                if( $item['quantity'] > 0 ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.64', 'msg'=>'All quantities must be removed from baskets'));
                }
            }
            //
            // If all ok, remove
            //
            foreach($items as $item) {
                $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.foodmarket.basketitem', $item['id'], $item['uuid'], 0x07);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
    }

    //
    // Check if quantity should be changed for a basket first
    //
    if( isset($args['basket_output_id']) && $args['basket_output_id'] != '' 
        && isset($args['item_output_id']) && $args['item_output_id'] > 0 
        && isset($args['quantity']) 
        ) {
        if( $args['quantity'] == '' ) {
            $args['quantity'] = 0;
        }
        $strsql = "SELECT id, quantity "
            . "FROM ciniki_foodmarket_basket_items "
            . "WHERE basket_output_id = '" . ciniki_core_dbQuote($ciniki, $args['basket_output_id']) . "' "
            . "AND date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
            . "AND item_output_id = '" . ciniki_core_dbQuote($ciniki, $args['item_output_id']) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['item']['quantity']) ) {
            if( $rc['item']['quantity'] != $args['quantity'] ) {
                $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.foodmarket.basketitem', $rc['item']['id'], array('quantity'=>$args['quantity']), 0x07);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        } else {
            $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.foodmarket.basketitem', array(
                'basket_output_id'=>$args['basket_output_id'],
                'date_id'=>$args['date_id'],
                'item_output_id'=>$args['item_output_id'],
                'quantity'=>$args['quantity'],
                ), 0x07);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
        //
        // Update the basket item in the orders
        //
        if( $args['basket_output_id'] > 0 ) {
            $rc = ciniki_foodmarket_basketsUpdateOrders($ciniki, $args['business_id'], array(
                'date_id'=>$args['date_id'],
                'basket_output_id'=>$args['basket_output_id'],
                'item_output_id'=>$args['item_output_id'],
                'quantity'=>$args['quantity'],
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }
    
    //
    // Get the baskets
    //
    $strsql = "SELECT ciniki_foodmarket_product_outputs.id, "
        . "ciniki_foodmarket_product_outputs.pio_name AS name, "
        . "ciniki_foodmarket_product_outputs.retail_price AS price, "
        . "ciniki_foodmarket_product_outputs.retail_price_text AS price_text, "
        . "COUNT(ciniki_poma_order_items.id) AS num_ordered "
        . "FROM ciniki_foodmarket_product_outputs "
        . "LEFT JOIN ciniki_poma_orders ON ("
            . "ciniki_poma_orders.date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
            . "AND ciniki_poma_orders.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_poma_order_items ON ("
            . "ciniki_poma_orders.id = ciniki_poma_order_items.order_id "
            . "AND ciniki_foodmarket_product_outputs.id = ciniki_poma_order_items.object_id "
            . "AND ciniki_poma_order_items.object = 'ciniki.foodmarket.output' "
            . "AND ciniki_poma_order_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_foodmarket_product_outputs.otype = 70 "
        . "AND ciniki_foodmarket_product_outputs.status > 5 "
        . "GROUP BY ciniki_foodmarket_product_outputs.id "
        . "ORDER BY ciniki_foodmarket_product_outputs.retail_price "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'baskets', 'fname'=>'id', 'fields'=>array('id', 'name', 'price', 'price_text', 'num_ordered')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['baskets']) ) {
        $baskets = $rc['baskets'];
        foreach($baskets as $bid => $basket) {
            $baskets[$bid]['total'] = 0;
//            $baskets[$bid]['profit'] = 0;
        }
    }
    
    //
    // Get the basket items
    //
    $strsql = "SELECT "
        . "ciniki_foodmarket_basket_items.item_output_id AS id, "
        . "ciniki_foodmarket_product_outputs.product_id, "
        . "ciniki_foodmarket_products.supplier_id, "
        . "ciniki_foodmarket_suppliers.code AS supplier_code, "
        . "ciniki_foodmarket_product_inputs.itype, "
        . "IFNULL(ciniki_foodmarket_product_inputs.case_units, 1) AS case_units, "
        . "IFNULL(ciniki_foodmarket_product_inputs.min_quantity, 1) AS min_quantity, "
        . "ciniki_foodmarket_product_outputs.pio_name AS name, "
        . "ciniki_foodmarket_product_outputs.otype, "
        . "ciniki_foodmarket_product_outputs.retail_price AS price, "
        . "ciniki_foodmarket_product_outputs.retail_price_text AS price_text, "
        . "ciniki_foodmarket_basket_items.basket_output_id, "
        . "ciniki_foodmarket_basket_items.quantity "
        . "FROM ciniki_foodmarket_basket_items "
        . "LEFT JOIN ciniki_foodmarket_product_outputs ON ("
            . "ciniki_foodmarket_basket_items.item_output_id = ciniki_foodmarket_product_outputs.id "
            . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_foodmarket_product_inputs ON ("
            . "ciniki_foodmarket_product_outputs.input_id = ciniki_foodmarket_product_inputs.id "
            . "AND ciniki_foodmarket_product_inputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_foodmarket_products ON ("
            . "ciniki_foodmarket_product_outputs.product_id = ciniki_foodmarket_products.id "
            . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_foodmarket_suppliers ON ("
            . "ciniki_foodmarket_products.supplier_id = ciniki_foodmarket_suppliers.id "
            . "AND ciniki_foodmarket_suppliers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_foodmarket_basket_items.date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
        . "AND ciniki_foodmarket_basket_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "ORDER BY pio_name, ciniki_foodmarket_basket_items.item_output_id "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'basket_items', 'fname'=>'id', 
            'fields'=>array('id', 'product_id', 'supplier_id', 'supplier_code', 'itype', 'case_units', 'min_quantity', 'name', 'otype', 'price', 'price_text')),
        array('container'=>'basket_quantities', 'fname'=>'basket_output_id', 'fields'=>array('quantity')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['basket_items']) ) {
        $rsp['baskets_items'] = $rc['basket_items'];
    }
    $output_ids = array();
    foreach($rsp['baskets_items'] as $iid => $item) {
        $output_ids[] = $item['id'];
        $rsp['baskets_items'][$iid]['min_order_quantity'] = 1;
        if( $item['itype'] <= 30 && $item['min_quantity'] > 0 ) {
            $rsp['baskets_items'][$iid]['min_order_quantity'] = $item['min_quantity'];
        } else if( $item['itype'] == 50 && $item['case_units'] > 0 ) {
            $rsp['baskets_items'][$iid]['min_order_quantity'] = $item['case_units'];
        }
        
        //
        // Update the basket totals
        //
        $rsp['baskets_items'][$iid]['quantity'] = 0;
        if( isset($item['basket_quantities']) ) {
            foreach($item['basket_quantities'] as $bid => $q) {
                if( $bid > 0 ) {
                    $baskets[$bid]['total'] = bcadd($baskets[$bid]['total'], bcmul($q['quantity'], $item['price'], 6), 6);
                    $rsp['baskets_items'][$iid]['quantity'] += bcmul($baskets[$bid]['num_ordered'], $q['quantity'], 0);
                }
            }
        }

        if( $rsp['baskets_items'][$iid]['min_order_quantity'] > 1 ) {
            $rsp['baskets_items'][$iid]['quantity_text'] = (float)$rsp['baskets_items'][$iid]['quantity'] . '/' . (float)$rsp['baskets_items'][$iid]['min_order_quantity'];
            $percent = bcmul(bcdiv($rsp['baskets_items'][$iid]['quantity'], $rsp['baskets_items'][$iid]['min_order_quantity'], 6), 100, 0);
            $rsp['baskets_items'][$iid]['percent_text'] = $percent . '%';
        } else {
            $rsp['baskets_items'][$iid]['quantity_text'] = $rsp['baskets_items'][$iid]['quantity'];
            $rsp['baskets_items'][$iid]['percent_text'] = '';
        }
    }

    usort($rsp['baskets_items'], function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
        });

    //
    // Get all outputs that are for baskets and aren't already in the baskets_items
    //
    if( isset($args['outputs']) && $args['outputs'] == 'yes' ) {
        $strsql = "SELECT ciniki_foodmarket_product_outputs.id, "
            . "ciniki_foodmarket_product_outputs.product_id, "
            . "ciniki_foodmarket_products.supplier_id, "
            . "IFNULL(ciniki_foodmarket_suppliers.code, '') AS supplier_code, "
            . "ciniki_foodmarket_product_outputs.pio_name AS name, "
            . "IFNULL(MAX(ciniki_poma_order_dates.order_date), '') AS last_order_date, "
            . "DATEDIFF(NOW(), MAX(ciniki_poma_order_dates.order_date)) AS days "
            . "FROM ciniki_foodmarket_product_outputs "
            . "LEFT JOIN ciniki_foodmarket_basket_items ON ("
                . "ciniki_foodmarket_product_outputs.id = ciniki_foodmarket_basket_items.item_output_id "
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
            $rsp['baskets_outputs'] = $rc['outputs'];
            foreach($rsp['baskets_outputs'] as $oid => $output) {
                if( $output['days'] != '' && $output['days'] < 30 ) {
                    $rsp['baskets_recent_outputs'][] = $output;
                    unset($rsp['baskets_outputs'][$oid]);
                    $rsp['nplists']['baskets_recent_outputs'][] = $output['id'];
                } else {
                    $rsp['nplists']['baskets_outputs'][] = $output['id'];
                }
            }
        }
    }

    //
    // Format the basket totals
    //
    foreach($baskets as $bid => $basket) {
        $basket['total_text'] = '$' . number_format($basket['total'], 2);
        $percent = bcmul(bcdiv($basket['total'], $basket['price'], 6), 100, 0);
        $basket['total_text'] .= ' (' . $percent . '%)';
        $rsp['baskets'][] = $basket;
    }

    return $rsp;
}
?>
