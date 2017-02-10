<?php
//
// Description
// -----------
// This method is used to return the information required for the date limited product manager.
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
function ciniki_foodmarket_dateItems($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'date_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Order Date'),
        'add_output_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Add Product'),
        'delete_output_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Delete Product'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.dateItemList');
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

    $rsp = array('stat'=>'ok', 'dates'=>array(), 'availability_date_outputs'=>array(), 'availability_recent_outputs'=>array(), 'availability_outputs'=>array(),
        'nplists'=>array('availability_date_outputs'=>array(), 'availability_recent_outputs'=>array(), 'availability_outputs'=>array()),
        );

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

    //
    // If the date wasn't set, then choose the closest date to now
    //
    if( !isset($args['date_id']) || $args['date_id'] == 0 ) {
        $strsql = "SELECT id, ABS(DATEDIFF(NOW(), order_date)) AS age "
            . "FROM ciniki_poma_order_dates "
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
    }

    $dt = new DateTime('now', new DateTimezone($intl_timezone));

    $strsql = "SELECT ciniki_poma_order_dates.id, "
        . "ciniki_poma_order_dates.order_date, "
        . "ciniki_poma_order_dates.display_name, "
        . "ciniki_poma_order_dates.status, "
        . "ciniki_poma_order_dates.flags "
        . "FROM ciniki_poma_order_dates "
        . "WHERE ciniki_poma_order_dates.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_poma_order_dates.order_date > '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' "
        . "GROUP BY ciniki_poma_order_dates.id "
        . "ORDER BY ciniki_poma_order_dates.order_date DESC "
        . "LIMIT 15"
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.poma', array(
        array('container'=>'dates', 'fname'=>'id', 'fields'=>array('id', 'order_date', 'display_name', 'status', 'flags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['dates']) || count($rc['dates']) < 1 ) {
        return array('stat'=>'ok', 'dates'=>array(), 'open_orders'=>array(), 'closed_orders'=>array(), 'order'=>array());
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
    // Add the order date item to the database
    //
    if( isset($args['add_output_id']) && $args['add_output_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.foodmarket.dateitem', array(
            'date_id'=>$args['date_id'], 
            'output_id'=>$args['add_output_id'],
            ), 0x07);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        //
        // Update the last_change date in the business modules
        // Ignore the result, as we don't want to stop user updates if this fails.
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
        ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'foodmarket');
    }

    //
    // Delete the order date item to the database
    //
    if( isset($args['delete_output_id']) && $args['delete_output_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
        $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.foodmarket.dateitem', $args['delete_output_id'], null, 0x07);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        //
        // Update the last_change date in the business modules
        // Ignore the result, as we don't want to stop user updates if this fails.
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
        ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'foodmarket');
    }


    //
    // Get the products for the current date
    //
    $strsql = "SELECT ciniki_foodmarket_product_outputs.id, "
        . "ciniki_foodmarket_product_outputs.product_id, "
        . "IFNULL(ciniki_foodmarket_products.supplier_id, 0) AS supplier_id, "
        . "IFNULL(ciniki_foodmarket_suppliers.code, '') AS supplier_code, "
        . "ciniki_foodmarket_product_outputs.pio_name "
        . "FROM ciniki_foodmarket_date_items "
        . "LEFT JOIN ciniki_foodmarket_product_outputs ON ("
            . "ciniki_foodmarket_date_items.output_id  = ciniki_foodmarket_product_outputs.id "
            . "AND ciniki_foodmarket_product_outputs.status > 5 "
            . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_foodmarket_products ON ("
            . "ciniki_foodmarket_product_outputs.product_id = ciniki_foodmarket_products.id "
            . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_foodmarket_suppliers ON ("
            . "ciniki_foodmarket_products.supplier_id = ciniki_foodmarket_suppliers.id "
            . "AND ciniki_foodmarket_suppliers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_foodmarket_date_items.date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
        . "AND ciniki_foodmarket_date_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "ORDER BY supplier_code, pio_name "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.poma', array(
        array('container'=>'products', 'fname'=>'id', 'fields'=>array('id', 'product_id', 'supplier_id', 'supplier_code', 'name'=>'pio_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['products']) ) {
        $rsp['availability_date_outputs'] = $rc['products'];
    }
    $date_output_ids = array();
    foreach($rsp['availability_date_outputs'] as $product) {
        $date_output_ids[] = $product['id'];
    }

    //
    // Get all outputs that are date limited
    //
    $strsql = "SELECT ciniki_foodmarket_product_outputs.id, "
        . "ciniki_foodmarket_product_outputs.product_id, "
        . "ciniki_foodmarket_products.supplier_id, "
        . "IFNULL(ciniki_foodmarket_suppliers.code, '') AS supplier_code, "
        . "ciniki_foodmarket_product_outputs.pio_name AS name, "
        . "IFNULL(MAX(ciniki_poma_order_dates.order_date), '') AS last_order_date, "
        . "DATEDIFF(NOW(), MAX(ciniki_poma_order_dates.order_date)) AS days "
        . "FROM ciniki_foodmarket_product_outputs "
        . "LEFT JOIN ciniki_foodmarket_date_items ON ("
            . "ciniki_foodmarket_product_outputs.id = ciniki_foodmarket_date_items.output_id "
            . "AND ciniki_foodmarket_date_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_poma_order_dates ON ("
            . "ciniki_foodmarket_date_items.date_id = ciniki_poma_order_dates.id "
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
        . "WHERE (ciniki_foodmarket_product_outputs.flags&0x0200) = 0x0200 "
        . "AND ciniki_foodmarket_product_outputs.status > 5 "
        . "";
    if( count($date_output_ids) > 0 ) {
        $strsql .= "AND ciniki_foodmarket_product_outputs.id NOT IN (" . ciniki_core_dbQuoteIDs($ciniki, $date_output_ids) . ") ";
    }
    $strsql .= "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "GROUP BY ciniki_foodmarket_product_outputs.id "
        . "ORDER BY supplier_code, pio_name "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.poma', array(
        array('container'=>'products', 'fname'=>'id', 
            'fields'=>array('id', 'product_id', 'supplier_id', 'supplier_code', 'name'=>'name', 'last_order_date', 'days'),
            'utctotz'=>array('last_order_date'=>array('timezone'=>'UTC', 'format'=>$date_format)),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['products']) ) {
        $rsp['availability_outputs'] = $rc['products'];
        foreach($rsp['availability_outputs'] as $pid => $product) {
            if( $product['days'] != '' && $product['days'] < 30 ) {
                $rsp['availability_recent_outputs'][] = $product;
                unset($rsp['availability_outputs'][$pid]);
            }
        }
    }

    return $rsp;
}
?>
