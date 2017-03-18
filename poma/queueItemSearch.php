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
function ciniki_foodmarket_poma_queueItemSearch($ciniki, $business_id, $args) {

    if( !isset($args['keywords']) || $args['keywords'] == '' ) {
        return array('stat'=>'ok', 'items'=>array());
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'convertOutputItem');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

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

    $args['keywords'] = str_replace(' ', '%', $args['keywords']);

    //
    // Get the list of product outputs which match the search
    //
    $strsql = "SELECT ciniki_foodmarket_product_outputs.id, "
        . "ciniki_foodmarket_product_outputs.flags, "
        . "ciniki_foodmarket_product_outputs.otype, "
        . "ciniki_foodmarket_product_outputs.units, "
        . "ciniki_foodmarket_product_outputs.io_name, "
        . "ciniki_foodmarket_product_outputs.pio_name, "
        . "ciniki_foodmarket_product_inputs.name AS input_name, "
        . "ciniki_foodmarket_products.name AS product_name, "
        . "ciniki_foodmarket_product_outputs.retail_price, "
        . "ciniki_foodmarket_product_outputs.retail_price_text, "
        . "ciniki_foodmarket_product_outputs.retail_taxtype_id, "
        . "ciniki_foodmarket_products.packing_order "
        . "FROM ciniki_foodmarket_product_outputs "
        . "LEFT JOIN ciniki_foodmarket_products ON ("
            . "ciniki_foodmarket_product_outputs.product_id = ciniki_foodmarket_products.id "
            . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' " 
            . ") "
        . "LEFT JOIN ciniki_foodmarket_product_inputs ON ("
            . "ciniki_foodmarket_product_outputs.input_id = ciniki_foodmarket_product_inputs.id "
            . "AND ciniki_foodmarket_product_inputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' " 
            . ") "
        . "WHERE ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' " 
        . "AND (ciniki_foodmarket_product_outputs.keywords LIKE '" . ciniki_core_dbQuote($ciniki, $args['keywords']) . "%' "
            . "OR ciniki_foodmarket_product_outputs.keywords LIKE '% " . ciniki_core_dbQuote($ciniki, $args['keywords']) . "%' "
            . ") "
        . "AND ciniki_foodmarket_product_outputs.status > 5 "
        . "AND ciniki_foodmarket_product_outputs.otype < 71 "
        . "AND (ciniki_foodmarket_product_outputs.flags&0x0400) = 0x0400 "
        . "AND ciniki_foodmarket_products.status > 5 "
        . "AND ciniki_foodmarket_products.status < 90 "
        . "";
    if( isset($args['limit']) && $args['limit'] != '' && preg_match("/^[0-9]+$/", $args['limit']) ) {
        $strsql .= "LIMIT " . $args['limit'];
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.poma', 'output');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['rows']) || count($rc['rows']) == 0 ) {
        return array('stat'=>'ok', 'items'=>array());
    }
    $items = $rc['rows'];

    foreach($items as $iid => $item) {
        $rc = ciniki_foodmarket_convertOutputItem($ciniki, $business_id, $item);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $items[$iid] = $rc['item'];
    }

    return array('stat'=>'ok', 'items'=>$items);        
}
?>
