<?php
//
// Description
// ===========
// This function will search the products for the ciniki.sapos module.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_foodmarket_poma_itemSubstitutions($ciniki, $tnid, $args) {

    if( !isset($args['date_id']) || $args['date_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.48', 'msg'=>'No date specified for substitutions'));
    }
    if( !isset($args['object']) || $args['object'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.49', 'msg'=>'No object specified for substitutions'));
    }
    if( !isset($args['object_id']) || $args['object_id'] == '' || $args['object_id'] < 1 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.50', 'msg'=>'No object ID specified for substitutions'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'convertOutputItem');

    //
    // Load the status maps for the text description of each type
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'poma', 'private', 'maps');
    $rc = ciniki_poma_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Set the default taxtype for the item
    //
    $taxtype_id = 0;
    $substitutions = array();

    //
    // Get the list of items that are available for substitutions
    //
    $strsql = "SELECT DISTINCT ciniki_foodmarket_product_outputs.id, "
        . "ciniki_foodmarket_product_outputs.otype, "
        . "ciniki_foodmarket_product_outputs.units, "
        . "ciniki_foodmarket_product_outputs.flags, "
        . "ciniki_foodmarket_product_outputs.pio_name, "
        . "ciniki_foodmarket_product_outputs.retail_price, "
        . "ciniki_foodmarket_product_outputs.retail_taxtype_id, "
        . "ciniki_foodmarket_products.packing_order, "
        . "ciniki_foodmarket_products.flags AS product_flags "
        . "FROM ciniki_foodmarket_basket_items, ciniki_foodmarket_product_outputs, ciniki_foodmarket_products "
        . "WHERE ciniki_foodmarket_basket_items.date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' " 
        . "AND ciniki_foodmarket_basket_items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' " 
        . "AND ciniki_foodmarket_basket_items.item_output_id = ciniki_foodmarket_product_outputs.id "
        . "AND ciniki_foodmarket_product_outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' " 
        . "AND ciniki_foodmarket_product_outputs.product_id = ciniki_foodmarket_products.id "
        . "AND ciniki_foodmarket_product_outputs.status > 5 "
        . "AND ciniki_foodmarket_product_outputs.otype IN (71, 72) "
        . "AND ciniki_foodmarket_products.status > 5 "
        . "AND ciniki_foodmarket_products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' " 
        . "ORDER BY ciniki_foodmarket_product_outputs.pio_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.poma', 'output');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['rows']) || count($rc['rows']) == 0 ) {
        return array('stat'=>'ok', 'items'=>array());
    }
    $substitute_items = $rc['rows'];

    $substitutions = array();
    foreach($substitute_items as $iid => $output) {
        $rc = ciniki_foodmarket_convertOutputItem($ciniki, $tnid, $output);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $substitutions[] = $rc['item'];
    }

    return array('stat'=>'ok', 'substitutions'=>$substitutions);        
}
?>
