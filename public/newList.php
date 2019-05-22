<?php
//
// Description
// -----------
// This method will return the list of Products for a tenant.
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
function ciniki_foodmarket_newList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'product_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Product'),
        'action'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Action'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['tnid'], 'ciniki.foodmarket.newList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current output
    //
    if( isset($args['product_id']) && $args['product_id'] > 0 ) {
        $strsql = "SELECT id, flags "
            . "FROM ciniki_foodmarket_products "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'product');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['product']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.143', 'msg'=>'Could not find that product.'));
        }
        $product = $rc['product'];

        //
        // Check if an update should occur before sending back the list
        //
        if( isset($args['action']) && $args['action'] == 'add' ) {
            $new_flags = $product['flags'] | 0x01;
        }
        if( isset($args['action']) && $args['action'] == 'remove' ) {
            $new_flags = $product['flags'] &~ 0x01;
        }
        if( isset($new_flags) && $new_flags != $product['flags'] ) {
            //
            // Start transaction
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.foodmarket');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }

            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.foodmarket.product', $args['product_id'], array('flags'=>$new_flags), 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.foodmarket');
                return $rc;
            }

            //
            // Update the categories
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'categoriesUpdate');
            $rc = ciniki_foodmarket_categoriesUpdate($ciniki, $args['tnid']);
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
        }
    }

    $rsp = array('stat'=>'ok', 'new_products'=>array());

    //
    // Get the list of products with the new flag
    //
    $strsql = "SELECT products.id, "
        . "suppliers.code AS supplier_code, "
        . "products.name "
        . "FROM ciniki_foodmarket_products AS products "
        . "LEFT JOIN ciniki_foodmarket_suppliers AS suppliers ON ("
            . "products.supplier_id = suppliers.id "
            . "AND suppliers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND (products.flags&0x01) = 0x01 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'products', 'fname'=>'id', 'fields'=>array('id', 'supplier_code', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['products']) ) {
        $rsp['new_products'] = $rc['products'];
    }

    return $rsp;
}
?>
