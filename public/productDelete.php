<?php
//
// Description
// -----------
// This method will delete an product.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the product is attached to.
// product_id:            The ID of the product to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_foodmarket_productDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'product_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Product'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['tnid'], 'ciniki.foodmarket.productDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    //
    // Get the current product
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_foodmarket_products "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'product');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['product']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.14', 'msg'=>'Product does not exist.'));
    }
    $product = $rc['product'];

    //
    // Load the product inputs
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_foodmarket_product_inputs "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'product');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $inputs = isset($rc['rows']) ?  $rc['rows'] : array();

    //
    // Load the product outputs
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_foodmarket_product_outputs "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'product');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $outputs = isset($rc['rows']) ?  $rc['rows'] : array();
    $output_ids = array();
    foreach($outputs as $output) {
        $output_ids[] = $output['id'];
    }

    //
    // Load the categories
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_foodmarket_category_items "
        . "WHERE product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $category_items = array();
    if( isset($rc['rows']) ) {
        $category_items = $rc['rows'];
    }

    //
    // Load the customer favourites or repeats/queue
    //
    $customer_items = array();
    if( count($output_ids) > 0 ) {
        $strsql = "SELECT id, uuid "
            . "FROM ciniki_poma_customer_items "
            . "WHERE object = 'ciniki.foodmarket.output' "
            . "AND object_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $output_ids) . ") "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows']) ) {
            $customer_items = $rc['rows'];
        }

        // 
        // Check for any orders with the product in the item list 
        //
        $strsql = "SELECT COUNT(DISTINCT ciniki_poma_order_items.order_id) AS orders "
            . "FROM ciniki_poma_order_items, ciniki_poma_orders "
            . "WHERE ciniki_poma_order_items.object = 'ciniki.foodmarket.output' "
            . "AND ciniki_poma_order_items.object_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $output_ids) . ") "
            . "AND ciniki_poma_order_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_poma_order_items.order_id = ciniki_poma_orders.id "
            . "AND ciniki_poma_orders.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_poma_orders.status < 70 "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.foodmarket', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.15', 'msg'=>'You still have ' . $rc['num'] . ' order' . ($rc['num']>1?'s':'') . '.'));
        }
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.foodmarket');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the customer items
    //
    foreach($customer_items as $item) {
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.poma.customeritem', $item['id'], $item['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.foodmarket');
            return $rc;
        }
    }

    //
    // Remove the customer items
    //
    foreach($category_items as $item) {
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.foodmarket.categoryitem', $item['id'], $item['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.foodmarket');
            return $rc;
        }
    }

    //
    // Remove the outputs
    //
    foreach($outputs as $item) {
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.foodmarket.output', $item['id'], $item['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.foodmarket');
            return $rc;
        }
    }

    //
    // Remove the inputs
    //
    foreach($inputs as $item) {
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.foodmarket.input', $item['id'], $item['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.foodmarket');
            return $rc;
        }
    }

    //
    // Remove the product
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.foodmarket.product',
        $args['product_id'], $product['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.foodmarket');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.foodmarket');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'foodmarket');

    return array('stat'=>'ok');
}
?>
