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
// business_id:     The ID of the business to get events for.
//
// Returns
// -------
//
function ciniki_foodmarket_web_productList($ciniki, $settings, $business_id, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

    //
    // Select the products for a category
    //
    if( isset($args['category_id']) ) {
        $strsql = "SELECT ciniki_foodmarket_products.id, "
            . "ciniki_foodmarket_products.name, "
            . "ciniki_foodmarket_products.permalink, "
            . "ciniki_foodmarket_products.synopsis "
        . "FROM ciniki_foodmarket_category_items, ciniki_foodmarket_products "
        . "WHERE ciniki_foodmarket_category_items.category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
        . "AND ciniki_foodmarket_category_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_foodmarket_category_items.product_id = ciniki_foodmarket_products.id "
        . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_foodmarket_products.status = 40 "
        . "";
    } 
    //
    // Select the products from the sub-categories
    //
    elseif( isset($args['parent_id']) ) {
        $strsql = "SELECT ciniki_foodmarket_products.id, "
            . "ciniki_foodmarket_products.name, "
            . "ciniki_foodmarket_products.permalink, "
            . "ciniki_foodmarket_products.synopsis "
        . "FROM ciniki_foodmarket_category_items, ciniki_foodmarket_products "
        . "WHERE ciniki_foodmarket_category_items.parent_id  = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
        . "AND ciniki_foodmarket_category_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_foodmarket_category_items.product_id = ciniki_foodmarket_products.id "
        . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_foodmarket_products.status = 40 "
        . "";
    } 
    //
    // Select products from any category
    //
    else {
        $strsql = "SELECT ciniki_foodmarket_products.id, "
            . "ciniki_foodmarket_products.name, "
            . "ciniki_foodmarket_products.permalink, "
            . "ciniki_foodmarket_products.synopsis "
        . "FROM ciniki_foodmarket_products "
        . "WHERE ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_foodmarket_products.status = 40 "
        . "";
    }

    //
    // Get the specials
    //
    if( isset($args['flags']) && is_int($args['flags']) ) ( {
        // Make sure it's an integer we're passing into the sql string.
        $flags = $args['flags']&0xFFFFFFFF;
        $strsql .= "AND ciniki_foodmarket_products.flags&$flags = $flags ";
    }

    //
    // Get the new products
    //
    elseif( isset($args['type']) && $args['type'] == 'newproducts' ) {
        // FIXME: Implement new product listings
        return array('stat'=>'ok', 'products'=>array());    
    }

    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'products', 'fname'=>'id', 'fields'=>array('id', 'name', 'permalink', 'synopsis')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['products']) ) {
        return array('stat'=>'ok', 'products'=>array());
    }
    $products = $rc['products'];

    return array('stat'=>'ok', 'products'=>$products);
}
?>
