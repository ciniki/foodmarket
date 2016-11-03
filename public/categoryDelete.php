<?php
//
// Description
// -----------
// This method will delete an category.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:            The ID of the business the category is attached to.
// category_id:            The ID of the category to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_foodmarket_categoryDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'category_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Category'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.categoryDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the category
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_foodmarket_categories "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'category');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['category']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.5', 'msg'=>'Category does not exist.'));
    }
    $category = $rc['category'];

    // 
    // Check for any child categories
    //
    $strsql = "SELECT COUNT(id) AS children "
        . "FROM ciniki_foodmarket_categories "
        . "WHERE ciniki_foodmarket_categories.parent_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
    $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.foodmarket', 'num');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.6', 'msg'=>'You still have ' . $rc['num'] . ' child categor' . ($rc['num']>1?'ies':'y') . '.'));
    }

    //
    // Check for items already in the category
    //
    $strsql = "SELECT COUNT(id) AS items "
        . "FROM ciniki_foodmarket_category_items "
        . "WHERE ciniki_foodmarket_category_items.category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
    $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.foodmarket', 'num');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.7', 'msg'=>'You still have ' . $rc['num'] . ' item' . ($rc['num']>1?'s':'') . ' in this category.'));
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
    // Remove the category
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.foodmarket.category', $args['category_id'], $category['uuid'], 0x04);
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
