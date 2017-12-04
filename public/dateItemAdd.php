<?php
//
// Description
// -----------
// This method will add a new order date item for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Order Date Item to.
//
// Returns
// -------
//
function ciniki_foodmarket_dateItemAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'date_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Category'),
        'output_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product'),
        'quantity'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Quantity'),
        'date_products'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Date Products'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['tnid'], 'ciniki.foodmarket.dateItemAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
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
    // Add the order date item to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.foodmarket.dateitem', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.foodmarket');
        return $rc;
    }
    $dateitem_id = $rc['id'];

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

    $rsp = array('stat'=>'ok', 'id'=>$dateitem_id);

    //
    // Get the products for the current date
    //
    if( isset($args['date_outputs']) && $args['date_outputs'] == 'yes' ) {
        $strsql = "SELECT ciniki_foodmarket_product_outputs.id, "
            . "ciniki_foodmarket_products.supplier_id, "
            . "ciniki_foodmarket_suppliers.code AS supplier_code, "
            . "ciniki_foodmarket_product_outputs.pio_name "
            . "FROM ciniki_foodmarket_date_items "
            . "LEFT JOIN ciniki_foodmarket_product_outputs ON ("
                . "ciniki_foodmarket_date_items.output_id  = ciniki_foodmarket_product_outputs.id "
                . "AND ciniki_foodmarket_product_outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_products ON ("
                . "ciniki_foodmarket_product_outputs.product_id = ciniki_foodmarket_products.id "
                . "AND ciniki_foodmarket_products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_suppliers ON ("
                . "ciniki_foodmarket_products.supplier_id = ciniki_foodmarket_suppliers.id "
                . "AND ciniki_foodmarket_suppliers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_foodmarket_date_items.date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
            . "AND ciniki_foodmarket_date_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY supplier_code, pio_name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.poma', array(
            array('container'=>'products', 'fname'=>'id', 'fields'=>array('id', 'supplier_id', 'supplier_code', 'name'=>'pio_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['products']) ) {
            $rsp['date_products'] = $rc['products'];
        } else {
            $rsp['date_products'] = array();
        }
    }

    return $rsp;
}
?>
