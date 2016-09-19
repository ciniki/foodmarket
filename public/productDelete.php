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
// business_id:            The ID of the business the product is attached to.
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
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'product_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Product'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.productDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the product
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_foodmarket_products "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'product');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['product']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3612', 'msg'=>'Product does not exist.'));
    }
    $product = $rc['product'];

    // 
    // Check for any product versions
    //
    $strsql = "SELECT COUNT(id) AS versions "
        . "FROM ciniki_foodmarket_product_versions "
        . "WHERE ciniki_foodmarket_product_versions.product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
    $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.foodmarket', 'num');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3628', 'msg'=>'You still have ' . $rc['num'] . ' child categor' . ($rc['num']>1?'ies':'y') . '.'));
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
    // Remove the product
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.foodmarket.product',
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
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'foodmarket');

    return array('stat'=>'ok');
}
?>
