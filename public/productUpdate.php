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
// <rsp stat='ok' />
//
function ciniki_foodmarket_productUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'product_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'permalink'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permalink'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'ptype'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'packing_order'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Packing Order'),
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Primary Image'),
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Synopsis'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
        'ingredients'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Ingredients'),
        'supplier_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Supplier'),
        'basket_wholesale_price'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Basket Wholesale Price'),
        'basket_wholesale_taxtype_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Basket Wholesale Tax'),
        'basket_retail_price'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Basket Retail Price'),
        'basket_retail_taxtype_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Basket Retail Tax'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Categories'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.productUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current product
    //
    $strsql = "SELECT id, ptype, name, permalink "
        . "FROM ciniki_foodmarket_products "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'product');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( !isset($rc['product']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.30', 'msg'=>'Product does not exist'));
    }
    $product = $rc['product'];

    if( isset($args['name']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
        //
        // Make sure the permalink is unique
        //
        $strsql = "SELECT id, name, permalink "
            . "FROM ciniki_foodmarket_products "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.19', 'msg'=>'You already have an product with this name, please choose another.'));
        }
    }

    $ptype = (isset($args['ptype']) ? $args['ptype'] : $product['ptype']);

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
    // Update the Product in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.foodmarket.product', $args['product_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.foodmarket');
        return $rc;
    }

    //
    // Update the product categories
    //
    if( isset($args['categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'productCategoriesUpdate');
        $rc = ciniki_foodmarket_productCategoriesUpdate($ciniki, $args['business_id'], $args['product_id'], $args['categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.foodmarket');
            return $rc;
        }
    }

    //
    // Update the inputs and outputs for the product
    //
    if( $ptype == '10' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'productSuppliedUpdate');
        $rc = ciniki_foodmarket_productSuppliedUpdate($ciniki, $args['business_id'], $args['product_id'], $ciniki['request']['args']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.foodmarket');
            return $rc;
        }
    }

    //
    // Add just an output for a basket
    //
    elseif( $ptype == '70' ) {
        //
        // Load the current basket settings
        //
        $strsql = "SELECT id, status, wholesale_price, wholesale_taxtype_id, retail_price, retail_taxtype_id "
            . "FROM ciniki_foodmarket_product_outputs "
            . "WHERE product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND otype = 70 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'output');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['output']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.38', 'msg'=>'Unable to find product'));
        }
        $output = $rc['output'];
    
        $update_args = array();
        foreach(['wholesale_price', 'wholesale_taxtype_id', 'retail_price', 'retail_taxtype_id'] as $field) {
            if( isset($args['basket_' . $field]) && $args['basket_' . $field] != $output[$field] ) {
                $update_args[$field] = $args['basket_' . $field];
            }
        }
        if( isset($args['status']) && $args['status'] != $output['status'] ) {
            $update_args['status'] = $args['status'];
        }
        if( count($update_args) > 0 ) {
            $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.foodmarket.output', $output['id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.foodmarket');
                return $rc;
            }
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'productUpdateFields');
        $rc = ciniki_foodmarket_productUpdateFields($ciniki, $args['business_id'], $args['product_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    //
    // Update the categories
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'categoriesUpdate');
    $rc = ciniki_foodmarket_categoriesUpdate($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update any orders with this products
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'updateOrderPrices');
    $rc = ciniki_foodmarket_productPricePush($ciniki, $args['business_id'], $args['product_id']);
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
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'foodmarket');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['business_id'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.foodmarket.product', 'object_id'=>$args['product_id']));

    return array('stat'=>'ok');
}
?>
