<?php
//
// Description
// -----------
// This method will return the list of Products for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Product for.
//
// Returns
// -------
//
function ciniki_foodmarket_specialsList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'output_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Output'),
        'retail_sdiscount_percent'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Retail Specials Discount'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.productList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check if an update should occur before sending back the list
    //
    if( isset($args['output_id']) && $args['output_id'] > 0 && isset($args['retail_sdiscount_percent']) && $args['retail_sdiscount_percent'] != '' ) {
        //
        // Get the current output
        //
        $strsql = "SELECT product_id, retail_sdiscount_percent "
            . "FROM ciniki_foodmarket_product_outputs "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['output_id']) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'output');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['output']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.69', 'msg'=>'Could not find that product.'));
        }
        $output = $rc['output'];
        
        //
        // Set the retail percent if different
        //
        if( $output['retail_sdiscount_percent'] != $args['retail_sdiscount_percent'] ) {
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

            $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.foodmarket.output', $args['output_id'], array('retail_sdiscount_percent'=>$args['retail_sdiscount_percent']), 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.foodmarket');
                return $rc;
            }

            //
            // Update the price fields
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'productUpdateFields');
            $rc = ciniki_foodmarket_productUpdateFields($ciniki, $args['business_id'], $output['product_id']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }

            //
            // Update the categories
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'categoriesUpdate');
            $rc = ciniki_foodmarket_categoriesUpdate($ciniki, $args['business_id']);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.foodmarket');
                return $rc;
            }

            //
            // Update any orders with this products
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'productPricePush');
            $rc = ciniki_foodmarket_productPricePush($ciniki, $args['business_id'], $output['product_id']);
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
        }
    }

    $rsp = array('stat'=>'ok', 'specials_outputs'=>array());

    $strsql = "SELECT outputs.id, "
        . "outputs.product_id, "
        . "suppliers.code AS supplier_code, "
        . "outputs.pio_name, "
        . "outputs.retail_price_text, "
        . "outputs.retail_sdiscount_percent, "
        . "outputs.retail_sprice_text "
        . "FROM ciniki_foodmarket_product_outputs AS outputs "
        . "LEFT JOIN ciniki_foodmarket_products AS products ON ("
            . "outputs.product_id = products.id "
            . "AND products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_foodmarket_suppliers AS suppliers ON ("
            . "products.supplier_id = suppliers.id "
            . "AND suppliers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND outputs.otype <= 70 "
        . "AND outputs.retail_sdiscount_percent > 0 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'outputs', 'fname'=>'id', 'fields'=>array('id', 'product_id', 'supplier_code', 'pio_name', 'retail_sdiscount_percent', 'retail_price_text', 'retail_sprice_text')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['outputs']) ) {
        $rsp['specials_outputs'] = $rc['outputs'];
    }

    return $rsp;
}
?>
