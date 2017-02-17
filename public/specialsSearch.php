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
function ciniki_foodmarket_specialsSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'search_str'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makeKeywords');
    $keywords = ciniki_core_makeKeywords($ciniki, $args['search_str'], true);
    $sqlwords = implode('% ', $keywords);

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
        . "AND ("
            . "outputs.keywords LIKE '" . $sqlwords . "%' "
            . "OR outputs.keywords LIKE '% " . $sqlwords . "%' "
            . ") "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'outputs', 'fname'=>'id', 'fields'=>array('id', 'product_id', 'supplier_code', 'pio_name', 'retail_sdiscount_percent', 'retail_price_text', 'retail_sprice_text')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['outputs']) ) {
    return array('stat'=>'ok', 'outputs'=>$rc['outputs']);
    }

    return array('stat'=>'ok', 'outputs'=>array());
}
?>
