<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_foodmarket_inputUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'input_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product Input'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'itype'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Type'),
        'units'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Units'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order'),
        'case_cost'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Case Cost'),
        'half_cost'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Half Cost'),
        'unit_cost'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Unit Cost'),
        'case_units'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Units/Case'),
        'min_quantity'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Minimum Order Quantity'),
        'inc_quantity'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Increment Order Quantity'),
        'cdeposit_name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Container Deposit Text'),
        'cdeposit_amount'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Container Deposit'),
        'sku'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sku/Code'),
        'inventory'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Inventory'),
        'recipe_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Recipe'),
        'recipe_quantity'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Recipe Quantity'),
        'container_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Container'),
        'materials_cost_per_container'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Materials Cost'),
        'time_cost_per_container'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Time Cost'),
        'total_cost_per_container'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Total Cost'),
        'total_time_per_container'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Total Time'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['tnid'], 'ciniki.foodmarket.inputUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current input
    //
    $strsql = "SELECT id, itype, name, product_id "
        . "FROM ciniki_foodmarket_product_inputs "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['input_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'input');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( !isset($rc['input']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.63', 'msg'=>'Input does not exist'));
    }
    $input = $rc['input'];

    //
    // Check permalink
    //
    if( isset($args['name']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
        //
        // Make sure the permalink is unique
        //
        $strsql = "SELECT id, name, permalink "
            . "FROM ciniki_foodmarket_product_inputs "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['input_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.76', 'msg'=>'You already have an product input with this name, please choose another.'));
        }
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.foodmarket');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the Product Input in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.foodmarket.input', $args['input_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.foodmarket');
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'productUpdateFields');
    $rc = ciniki_foodmarket_productUpdateFields($ciniki, $args['tnid'], $input['product_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the categories
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'categoriesUpdate');
    $rc = ciniki_foodmarket_categoriesUpdate($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update any orders with this products
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'productPricePush');
    $rc = ciniki_foodmarket_productPricePush($ciniki, $args['tnid'], $input['product_id']);
    if( $rc['stat'] != 'ok' ) {
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
    // Get the current input
    //
    $strsql = "SELECT id, itype, name, product_id, case_cost, unit_cost "
        . "FROM ciniki_foodmarket_product_inputs "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['input_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'input');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( !isset($rc['input']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.71', 'msg'=>'Input does not exist'));
    }
    $input = $rc['input'];
    $input['price_text'] = 

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'foodmarket');

    return array('stat'=>'ok', 'input'=>$input);
}
?>
