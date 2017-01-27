<?php
//
// Description
// -----------
// This method will add a new product for the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to add the Product to.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_foodmarket_productAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'),
        'permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Permalink'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'10', 'validlist'=>array('10', '40', '90'), 'name'=>'Status'),
        'ptype'=>array('required'=>'yes', 'blank'=>'no', 'validlist'=>array('10', '70'), 'name'=>'Type'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'packing_order'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Packing Order'),
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Primary Image'),
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Synopsis'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
        'ingredients'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Ingredients'),
        'supplier_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Supplier'),
        'basket_wholesale_price'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Basket Wholesale Price'),
        'basket_wholesale_taxtype_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Basket Wholesale Tax'),
        'basket_retail_price'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Basket Retail Price'),
        'basket_retail_taxtype_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Basket Retail Tax'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Categories'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.productAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Setup permalink
    //
    if( !isset($args['permalink']) || $args['permalink'] == '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
    }

    //
    // Make sure the permalink is unique
    //
    $strsql = "SELECT id, name, permalink "
        . "FROM ciniki_foodmarket_products "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.13', 'msg'=>'You already have a product with that name, please choose another.'));
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.foodmarket');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the product to the database
    //
    $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.foodmarket.product', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.foodmarket');
        return $rc;
    }
    $product_id = $rc['id'];

    //
    // Add the categories
    //
    if( isset($args['categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'productCategoriesUpdate');
        $rc = ciniki_foodmarket_productCategoriesUpdate($ciniki, $args['business_id'], $product_id, $args['categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.foodmarket');
            return $rc;
        }
    }

    //
    // Add the inputs and outputs for the product
    //
    if( $args['ptype'] == '10' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'productSuppliedUpdate');
        $rc = ciniki_foodmarket_productSuppliedUpdate($ciniki, $args['business_id'], $product_id, $ciniki['request']['args']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.foodmarket');
            return $rc;
        }
    }

    //
    // Add just an output for a basket
    //
    elseif( $args['ptype'] == '70' ) {
        //
        // Setup the basket output args
        //
        $basket_output = array(
            'product_id'=>$product_id,
            'input_id'=>0,
            'name'=>$args['name'],
            'pio_name'=>$args['name'],
            'permalink'=>$args['permalink'],
            'status'=>(isset($args['status']) ? $args['status'] : 10),
            'otype'=>70,
            'units'=>'30',
            'flags'=>0x901,
            'sequence'=>1,
            'packing_order'=>10,
            'wholesale_price'=>(isset($args['basket_wholesale_price']) ? $args['basket_wholesale_price'] : 0),
            'wholesale_taxtype_id'=>(isset($args['basket_wholesale_taxtype_id']) ? $args['basket_wholesale_taxtype_id'] : 0),
            'retail_price'=>(isset($args['basket_retail_price']) ? $args['basket_retail_price'] : 0),
            'retail_taxtype_id'=>(isset($args['basket_retail_taxtype_id']) ? $args['basket_retail_taxtype_id'] : 0),
            );
        $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.foodmarket.output', $basket_output, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.foodmarket');
            return $rc;
        }
        $output_id = $rc['id'];
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
    ciniki_core_hookExec($ciniki, $args['business_id'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.foodmarket.product', 'object_id'=>$product_id));

    return array('stat'=>'ok', 'id'=>$product_id);
}
?>
