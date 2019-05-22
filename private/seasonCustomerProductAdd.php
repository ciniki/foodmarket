<?php
//
// Description
// -----------
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_foodmarket_seasonCustomerProductAdd(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'poma', 'private', 'orderCreateItemsAdd');
    
    //
    // Load the season
    //
    $strsql = "SELECT id, start_date, end_date, csa_start_date, csa_end_date, csa_days "
        . "FROM ciniki_foodmarket_seasons "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['season_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'season');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.116', 'msg'=>'Unable to load season', 'err'=>$rc['err']));
    }
    if( !isset($rc['season']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.117', 'msg'=>'No seasons setup', 'err'=>$rc['err']));
    }
    $season = $rc['season'];

    //
    // Load the season product
    //
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
            . "AND outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND products.id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'product');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.118', 'msg'=>'Unable to load product', 'err'=>$rc['err']));
    }
    if( !isset($rc['product']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.148', 'msg'=>'Unable to find requested product'));
    }
    $product = $rc['product'];

    //
    // Add to each week
    //
    $sdt = new DateTime($season['csa_start_date'], new DateTimezone('UTC'));
    if( $args['day'] < 1 || $args['day'] > 7 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.119', 'msg'=>'Invalid day of the week'));
    }
    while( $sdt->format('N') != $args['day'] ) {
        $sdt->add(new DateInterval('P1D'));
    }

    $week_number = 1;
    while( $week_number <= $product['repeat_weeks'] ) {
            
        //
        // Get the date id for this order date
        //
        $strsql = "SELECT id, order_date, DATEDIFF(order_date, UTC_TIMESTAMP()) AS days, status "
            . "FROM ciniki_poma_order_dates "
            . "WHERE order_date = '" . ciniki_core_dbQuote($ciniki, $sdt->format('Y-m-d')) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'date');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.120', 'msg'=>'Unable to load date', 'err'=>$rc['err']));
        }
        if( !isset($rc['date']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.121', 'msg'=>'Unable to find requested date'));
        }
        $date_id = $rc['date']['id'];
        $order_date = $rc['date']['order_date'];
        $date_status = $rc['date']['status'];
        $days_until_order = $rc['date']['days'];

        //
        // Check if order date is in the past, then ignore they get less weeks
        //
        if( $days_until_order > 0 ) {
            //
            // Add the item, creating order if necessary
            //
            $rc = ciniki_poma_orderCreateItemsAdd($ciniki, $tnid, array(
                'date' => array(
                    'id' => $date_id,
                    'order_date' => $order_date,
                    ),
                'customer_id' => $args['customer_id'],
                'items' => array(
                    array('object' => 'ciniki.foodmarket.seasonproduct', 'object_id' => $product['id'], 'quantity' => 1),
                    ),
                ));
            //
            // Ignore if date already closed.
            //
            if( $rc['stat'] != 'ok' && $rc['err']['code'] != 'ciniki.poma.47' ) {
                
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.122', 'msg'=>'Unable to create orders', 'err'=>$rc['err']));
            }
        }
        
        $week_number++;
        $sdt->add(new DateInterval('P' . $product['repeat_days'] . 'D'));
    }

    return array('stat'=>'ok');
}
?>
