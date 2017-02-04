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
function ciniki_foodmarket_poma_itemSearch($ciniki, $business_id, $args) {

    if( !isset($args['start_needle']) || $args['start_needle'] == '' ) {
        return array('stat'=>'ok', 'items'=>array());
    }

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

    //
    // Get the list of product outputs which match the search
    //
    $strsql = "SELECT ciniki_foodmarket_product_outputs.id AS object_id, "
        . "ciniki_foodmarket_product_outputs.otype, "
        . "ciniki_foodmarket_product_outputs.units, "
        . "ciniki_foodmarket_product_outputs.pio_name AS description, "
        . "ciniki_foodmarket_product_outputs.retail_price AS unit_amount, "
        . "ciniki_foodmarket_product_outputs.retail_price_text AS price_text, "
        . "ciniki_foodmarket_product_outputs.retail_taxtype_id AS taxtype_id, "
        . "ciniki_foodmarket_products.packing_order "
        . "FROM ciniki_foodmarket_product_outputs, ciniki_foodmarket_products "
        . "WHERE ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' " 
        . "AND (ciniki_foodmarket_product_outputs.pio_name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR ciniki_foodmarket_product_outputs.pio_name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") "
        . "AND ciniki_foodmarket_product_outputs.product_id = ciniki_foodmarket_products.id "
        . "AND ciniki_foodmarket_product_outputs.status > 5 "
        . "AND ciniki_foodmarket_product_outputs.otype < 71 "
        . "AND ciniki_foodmarket_products.status > 5 "
        . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' " 
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
        $items[$iid]['object'] = 'ciniki.foodmarket.output';
        $items[$iid]['itype'] = $item['otype'];
        if( $item['otype'] > 30 ) {
            $items[$iid]['itype'] = 30;
        }
        if( $item['units'] == 0x02 ) {
            $items[$iid]['weight_units'] = 20;
        } elseif( $item['units'] == 0x04 ) {
            $items[$iid]['weight_units'] = 25;
        } elseif( $item['units'] == 0x20 ) {
            $items[$iid]['weight_units'] = 60;
        } elseif( $item['units'] == 0x40 ) {
            $items[$iid]['weight_units'] = 65;
        }
    }

    return array('stat'=>'ok', 'items'=>$items);        
}
?>
