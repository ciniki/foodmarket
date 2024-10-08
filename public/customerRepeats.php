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
function ciniki_foodmarket_customerRepeats($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer ID'),
        'allitems'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Return All Items'),
        'customers'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customers'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['tnid'], 'ciniki.foodmarket.customerRepeats');
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rsp = array('stat'=>'ok');

    //
    // Get the list of repeats for a customer and the number of times they've ordered them.
    //
    if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
        $strsql = "SELECT ciniki_poma_customer_items.id, "
            . "ciniki_poma_customer_items.description, "
            . "ciniki_poma_customer_items.quantity, "
            . "ciniki_poma_customer_items.last_order_date, "
            . "ciniki_poma_customer_items.next_order_date, "
            . "IFNULL(COUNT(ciniki_poma_orders.id), 0) AS num_orders "
            . "FROM ciniki_poma_customer_items "
            . "LEFT JOIN ciniki_poma_order_items ON ("
                . "ciniki_poma_customer_items.object = ciniki_poma_order_items.object "
                . "AND ciniki_poma_customer_items.object_id = ciniki_poma_order_items.object_id "
                . "AND ciniki_poma_order_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_poma_orders ON ("
                . "ciniki_poma_order_items.order_id = ciniki_poma_orders.id "
                . "AND ciniki_poma_orders.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
                . "AND ciniki_poma_orders.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_poma_customer_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_poma_customer_items.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND ciniki_poma_customer_items.itype = 40 "
            . "GROUP BY ciniki_poma_customer_items.id "
            . "ORDER BY ciniki_poma_customer_items.description "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.poma', array(
            array('container'=>'items', 'fname'=>'id', 
                'fields'=>array('id', 'description', 'quantity', 'last_order_date', 'next_order_date', 'num_orders'),
                'utctotz'=>array(
                    'last_order_date'=>array('format'=>$date_format, 'timezone'=>'UTC'),
                    'next_order_date'=>array('format'=>$date_format, 'timezone'=>'UTC'),
                )),
            ));
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['items']) ) {
            $rsp['customer_repeats'] = array();
        } else {
            $rsp['customer_repeats'] = $rc['items'];
            foreach($rsp['customer_repeats'] as $rid => $repeat) {
                $rsp['customer_repeats'][$rid]['quantity'] = (float)$repeat['quantity'];
            }
        }
    } else {
        $strsql = "SELECT CONCAT_WS('-', ciniki_poma_customer_items.object, ciniki_poma_customer_items.object_id) AS oid, "
            . "ciniki_poma_customer_items.description, "
            . "COUNT(ciniki_poma_customer_items.customer_id) AS num_customers "
            . "FROM ciniki_poma_customer_items "
            . "WHERE ciniki_poma_customer_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_poma_customer_items.itype = 40 "
            . "GROUP BY oid "
            . "ORDER BY ciniki_poma_customer_items.description "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.poma', array(
            array('container'=>'items', 'fname'=>'oid', 'fields'=>array('id'=>'oid', 'description', 'num_customers')),
            ));
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['items']) ) {
            $rsp['repeat_items'] = array();
        } else {
            $rsp['repeat_items'] = $rc['items'];
        }
    }

    //
    // Get the complete list of items and customers
    //
    if( (!isset($args['customer_id']) || $args['customer_id'] == 0) && isset($args['allitems']) && $args['allitems'] == 'yes' ) {
        $strsql = "SELECT i.id, "
            . "i.customer_id, "
            . "c.display_name, "
            . "i.description, "
            . "i.repeat_days, "
            . "i.last_order_date, "
            . "i.next_order_date, "
            . "i.quantity "
            . "FROM ciniki_poma_customer_items AS i "
            . "LEFT JOIN ciniki_customers AS c ON ("
                . "i.customer_id = c.id "
                . "AND c.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE i.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND i.itype = 40 "
            . "ORDER BY c.display_name, description "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.poma', array(
            array('container'=>'items', 'fname'=>'id', 
                'fields'=>array('id', 'customer_id', 'display_name', 'description', 'repeat_days', 'quantity', 'last_order_date', 'next_order_date'),
                'utctotz'=>array(
                    'last_order_date'=>array('format'=>$date_format, 'timezone'=>'UTC'),
                    'next_order_date'=>array('format'=>$date_format, 'timezone'=>'UTC'),
                )),
            ));
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['items']) ) {
            $rsp['repeat_list'] = array();
        } else {
            $rsp['repeat_list'] = $rc['items'];
            foreach($rsp['repeat_list'] as $i => $item) {
                $rsp['repeat_list'][$i]['quantity'] = (float)$item['quantity'];
            }
        }
    }

    //
    // Get the list of customers with repeats
    //
    if( isset($args['customers']) && $args['customers'] == 'yes' ) {
        $strsql = "SELECT ciniki_poma_customer_items.customer_id, "
            . "ciniki_customers.display_name, "
            . "COUNT(ciniki_poma_customer_items.id) AS num_items "
            . "FROM ciniki_poma_customer_items "
            . "LEFT JOIN ciniki_customers ON ("
                . "ciniki_poma_customer_items.customer_id = ciniki_customers.id "
                . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_poma_customer_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_poma_customer_items.itype = 40 "
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
