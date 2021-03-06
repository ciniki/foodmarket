<?php
//
// Description
// -----------
// This function will return a list of categories for the web product page.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
//
function ciniki_foodmarket_web_favourites($ciniki, $settings, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

    //
    // Select the products for a customers favourites
    //
    $strsql = "SELECT ciniki_foodmarket_products.id AS product_id, "
        . "IFNULL(ciniki_foodmarket_product_inputs.id, 0) AS input_id, "
        . "ciniki_foodmarket_product_outputs.id, "
        . "ciniki_foodmarket_product_outputs.pio_name AS name, "
        . "ciniki_foodmarket_product_outputs.status, "
        . "ciniki_foodmarket_product_outputs.otype, "
        . "ciniki_foodmarket_product_outputs.units, "
        . "ciniki_foodmarket_product_outputs.flags, "
        . "ciniki_foodmarket_product_outputs.sequence, "
        . "ciniki_foodmarket_product_outputs.start_date, "
        . "ciniki_foodmarket_product_outputs.end_date, "
        . "ciniki_foodmarket_product_outputs.retail_price AS price,  "
        . "ciniki_foodmarket_product_outputs.retail_price_text AS price_text, "
        . "ciniki_foodmarket_product_outputs.retail_sprice AS sale_price,  "
        . "ciniki_foodmarket_product_outputs.retail_sprice_text AS sale_price_text, "
        . "ciniki_foodmarket_product_outputs.retail_mdiscount_percent,  "
        . "ciniki_foodmarket_product_outputs.retail_mprice AS member_price,  "
        . "ciniki_foodmarket_product_outputs.retail_mprice_text AS member_price_text, "
        . "ciniki_foodmarket_product_outputs.retail_taxtype_id AS taxtype_id, "
        . "IFNULL(ciniki_foodmarket_product_inputs.inventory, 0) AS inventory "
    . "FROM ciniki_poma_customer_items "
    . "INNER JOIN ciniki_foodmarket_product_outputs ON ("
        . "ciniki_poma_customer_items.object_id = ciniki_foodmarket_product_outputs.id "
        . "AND ciniki_foodmarket_product_outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_foodmarket_product_outputs.status = 40 "                                          // Output visible on website
        . ") "
    . "INNER JOIN ciniki_foodmarket_product_inputs ON ("
        . "ciniki_foodmarket_product_outputs.input_id = ciniki_foodmarket_product_inputs.id "
        . "AND ciniki_foodmarket_product_inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . ") "
    . "INNER JOIN ciniki_foodmarket_products ON ("
        . "ciniki_foodmarket_product_outputs.product_id = ciniki_foodmarket_products.id "
        . "AND ciniki_foodmarket_products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_foodmarket_products.status = 40 "                                          // Output visible on website
        . ") "
    . "WHERE ciniki_poma_customer_items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
    . "AND ciniki_poma_customer_items.customer_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "' "
    . "AND ciniki_poma_customer_items.itype = 20 "
    . "AND ciniki_poma_customer_items.object = 'ciniki.foodmarket.output' "
    . "ORDER BY ciniki_foodmarket_product_outputs.pio_name "
    . "";

    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'outputs', 'fname'=>'id', 'fields'=>array('id', 'product_id', 'input_id', 'name', 'status', 'otype',
            'units', 'flags', 'sequence', 'start_date', 'end_date', 'price', 'price_text', 
            'sale_price', 'sale_price_text', 'retail_mdiscount_percent', 'member_price', 'member_price_text', 'taxtype_id', 'inventory')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['outputs']) ) {
        return array('stat'=>'ok', 'options'=>array());
    }
    //
    // Prepare the outputs so they can be properly displayed with integrated order options
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'web', 'prepareOutputs');
    $rc = ciniki_foodmarket_web_prepareOutputs($ciniki, $settings, $tnid, array('outputs'=>$rc['outputs']));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $options = $rc['outputs'];

    return array('stat'=>'ok', 'options'=>$options);
}
?>
