<?php
//
// Description
// -----------
// This method returns the orders for a specific date, and the details of a specific order if specified.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Product for.
//
// Returns
// -------
//
function ciniki_foodmarket_members($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'season_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Season'),
        'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'),
        'action'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Action'),
        'product_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Product'),   // Product id in season_products table
        'day'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order Day'),   // Day of the week for product order/pickup
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['tnid'], 'ciniki.foodmarket.productList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'maps');
    $rc = ciniki_foodmarket_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];
    
    //
    // Load ciniki.poma maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'poma', 'private', 'maps');
    $rc = ciniki_poma_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $poma_maps = $rc['maps'];
    
    //
    // Load the season
    //
    $strsql = "SELECT id, start_date, end_date, csa_start_date, csa_end_date, csa_days "
        . "FROM ciniki_foodmarket_seasons "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
    if( isset($args['season_id']) && $args['season_id'] > 0 ) {
        $strsql .= "AND id = '" . ciniki_core_dbQuote($ciniki, $args['season_id']) . "' ";
    }
    $strsql .= "ORDER BY end_date DESC "
        . "LIMIT 1 ";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'season');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.110', 'msg'=>'Unable to load season', 'err'=>$rc['err']));
    }
    if( !isset($rc['season']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.111', 'msg'=>'No seasons setup'));
    }
    $season = $rc['season'];

    //
    // Check if action is to add customer to the season
    //
    if( isset($args['action']) && $args['action'] == 'newcustomer' && isset($args['customer_id']) && $args['customer_id'] > 0 ) {
        //
        // Check to make sure not already a member
        //
        $strsql = "SELECT id "
            . "FROM ciniki_foodmarket_season_customers "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND season_id = '" . ciniki_core_dbQuote($ciniki, $season['id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.113', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
        }
        if( !isset($rc['item']) ) {
            //
            // Customer has not already been added, add
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.foodmarket.seasoncustomer', array(
                'season_id' => $season['id'],
                'customer_id' => $args['customer_id'],
                ), 0x07);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.114', 'msg'=>'Unable to add customer to season.', 'err'=>$rc['err']));
            }
        }
    }

    $rsp = array('stat'=>'ok', 'season'=>$season, 'customers'=>array(), 'memberorders'=>array());

    //
    // Get the list of season products
    //
//    $selected_product = null;
    $strsql = "SELECT products.id, "
        . "products.season_id, "
        . "products.output_id, "
        . "outputs.pio_name, "
        . "products.repeat_days, "
        . "products.repeat_weeks, "
        . "products.price "
        . "FROM ciniki_foodmarket_season_products AS products "
        . "LEFT JOIN ciniki_foodmarket_product_outputs AS outputs ON ("
            . "products.output_id = outputs.id "
            . "AND outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'products', 'fname'=>'id', 
            'fields'=>array('id', 'season_id', 'output_id', 'pio_name', 'repeat_days', 'repeat_weeks', 'price'),
            'maps'=>array('repeat_days'=>$maps['seasonproduct']['repeat_days']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['products']) ) {
        $rsp['seasonproducts'] = $rc['products'];
        foreach($rsp['seasonproducts'] as $iid => $product) {
            $rsp['seasonproducts'][$iid]['price_display'] = '$' . number_format($product['price'], 2);
//            if( isset($args['product_id']) && $args['product_id'] == $product['id'] ) {
//                $selected_product = $product;
//            }
        }
    } else {
        $rsp['seasonproducts'] = array();
    }

    //
    // Check if action is to add product to customer
    //
    if( isset($args['action']) && $args['action'] == 'customerproductadd' 
        && isset($args['customer_id']) && $args['customer_id'] > 0 
        && isset($args['product_id']) && $args['product_id'] > 0 
        && isset($args['day']) && $args['day'] != '' 
        ) {
        //
        // Add the orders and products for the customer
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'seasonCustomerProductAdd');
        $rc = ciniki_foodmarket_seasonCustomerProductAdd($ciniki, $args['tnid'], array(
            'season_id' => $season['id'], 
            'customer_id' => $args['customer_id'], 
            'product_id' => $args['product_id'],
            'day' => $args['day'],
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.115', 'msg'=>'Unable to setup orders', 'err'=>$rc['err']));
        }
    }
    if( isset($args['action']) && $args['action'] == 'customerproductremove' 
        && isset($args['customer_id']) && $args['customer_id'] > 0 
        && isset($args['product_id']) && $args['product_id'] > 0 
        && isset($args['day']) && $args['day'] != '' 
        ) {
        //
        // Add the orders and products for the customer
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'seasonCustomerProductRemove');
        $rc = ciniki_foodmarket_seasonCustomerProductRemove($ciniki, $args['tnid'], array(
            'season_id' => $season['id'], 
            'customer_id' => $args['customer_id'], 
            'product_id' => $args['product_id'],
            'day' => $args['day'],
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.131', 'msg'=>'Unable to remove product', 'err'=>$rc['err']));
        }
    }

    //
    // Get the list of season members
    //
    $strsql = "SELECT members.id, "
        . "members.customer_id, "
        . "customers.display_name "
        . "FROM ciniki_foodmarket_season_customers AS members "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "members.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE members.season_id = '" . ciniki_core_dbQuote($ciniki, $season['id']) . "' "
        . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY customers.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'members', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'display_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.112', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
    }
    $rsp['members'] = isset($rc['members']) ? $rc['members'] : array();

    //
    // If the customer has been specified, load the details and orders
    //
    if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
        //
        // Get the customer details
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
        $rc = ciniki_customers_hooks_customerDetails($ciniki, $args['tnid'], array('customer_id'=>$args['customer_id']));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['details']) ) {
            $rsp['customer_details'] = $rc['details'];
        }

        //
        // Get the list of orders, and seasonal products ordered
        //
        $strsql = "SELECT orders.id, "
            . "orders.order_number, "
            . "orders.order_date, "
            . "orders.status, "
            . "orders.status AS status_text, "
            . "items.id AS item_id, "
            . "items.code, "
            . "items.description, "
            . "items.itype, "
            . "items.weight_quantity, "
            . "items.unit_quantity "
            . "FROM ciniki_poma_orders AS orders "
            . "LEFT JOIN ciniki_poma_order_items AS items ON ("
                . "orders.id = items.order_id "
                . "AND (items.flags&0x0200) = 0x0200 "
                . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE orders.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND orders.order_date >= '" . ciniki_core_dbQuote($ciniki, $season['csa_start_date']) . "' " 
            . "AND orders.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY orders.order_date ASC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
            array('container'=>'orders', 'fname'=>'id', 
                'fields'=>array('id', 'order_number', 'order_date', 'status', 'status_text'),
                'maps'=>array('status_text'=>$poma_maps['order']['status']),
                'utctotz'=>array('order_date'=>array('format'=>$date_format, 'timezone'=>'UTC')),
                ),
            array('container'=>'items', 'fname'=>'item_id', 
                'fields'=>array('id'=>'item_id', 'code', 'description', 'itype', 'weight_quantity', 'unit_quantity'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.123', 'msg'=>'Unable to load orders', 'err'=>$rc['err']));
        }
        if( isset($rc['orders']) ) {
            $rsp['memberorders'] = $rc['orders'];
            $order_sequence = 1;
            foreach($rsp['memberorders'] as $oid => $order) {
                $rsp['memberorders'][$oid]['products'] = '';
                $rsp['memberorders'][$oid]['sequence'] = $order_sequence++;
                if( isset($order['items']) ) {
                    foreach($order['items'] as $item) {
                        $rsp['memberorders'][$oid]['products'] .= ($rsp['memberorders'][$oid]['products'] != '' ? ", \n" : '') 
                            . $item['description'];
                    }
                }
            }
        } else {
            $rsp['memberorders'] = array();
        }
    }

    return $rsp;
}
?>
