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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

    //
    // Select the products for a category
    //
    if( isset($args['category_id']) ) {
        $strsql = "SELECT ciniki_foodmarket_products.id, "
            . "ciniki_foodmarket_products.name, "
            . "ciniki_foodmarket_products.permalink, "
            . "ciniki_foodmarket_products.primary_image_id AS image_id, "
            . "ciniki_foodmarket_products.synopsis, "
            . "ciniki_foodmarket_product_outputs.id AS price_id, "
            . "ciniki_foodmarket_product_outputs.io_name, "
            . "ciniki_foodmarket_product_outputs.retail_price_text, "
            . "ciniki_foodmarket_product_outputs.retail_sprice_text "
            . "FROM ciniki_foodmarket_category_items, ciniki_foodmarket_products, ciniki_foodmarket_product_outputs "
            . "WHERE ciniki_foodmarket_category_items.category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
            . "AND ciniki_foodmarket_category_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_foodmarket_category_items.product_id = ciniki_foodmarket_products.id "
            . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_foodmarket_products.status = 40 " // Product visible on website
            . "AND ciniki_foodmarket_products.id = ciniki_foodmarket_product_outputs.product_id "
            . "AND ciniki_foodmarket_product_outputs.status = 40 "  // Output visible on website
            . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "";
    } 
    //
    // Select the products from the sub-categories
    //
    elseif( isset($args['parent_id']) ) {
        $strsql = "SELECT ciniki_foodmarket_products.id, "
            . "ciniki_foodmarket_products.name, "
            . "ciniki_foodmarket_products.permalink, "
            . "ciniki_foodmarket_products.primary_image_id AS image_id, "
            . "ciniki_foodmarket_products.synopsis, "
            . "ciniki_foodmarket_product_outputs.id AS price_id, "
            . "ciniki_foodmarket_product_outputs.io_name, "
            . "ciniki_foodmarket_product_outputs.retail_price_text, "
            . "ciniki_foodmarket_product_outputs.retail_sprice_text "
            . "FROM ciniki_foodmarket_categories, ciniki_foodmarket_category_items, ciniki_foodmarket_products, ciniki_foodmarket_product_outputs "
            . "WHERE ciniki_foodmarket_categories.parent_id  = '" . ciniki_core_dbQuote($ciniki, $args['parent_id']) . "' "
            . "AND ciniki_foodmarket_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_foodmarket_categories.id = ciniki_foodmarket_category_items.category_id "
            . "AND ciniki_foodmarket_category_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_foodmarket_category_items.product_id = ciniki_foodmarket_products.id "
            . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_foodmarket_products.status = 40 " // Product visible on website
            . "AND ciniki_foodmarket_products.id = ciniki_foodmarket_product_outputs.product_id "   
            . "AND ciniki_foodmarket_product_outputs.status = 40 " // output visible on website
            . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "";
    } 
    //
    // Select products from any category
    //
    else {
        $strsql = "SELECT ciniki_foodmarket_products.id, "
            . "ciniki_foodmarket_products.name, "
            . "ciniki_foodmarket_products.permalink, "
            . "ciniki_foodmarket_products.primary_image_id AS image_id, "
            . "ciniki_foodmarket_products.synopsis, "
            . "ciniki_foodmarket_product_outputs.id AS price_id, "
            . "ciniki_foodmarket_product_outputs.io_name, "
            . "ciniki_foodmarket_product_outputs.retail_price_text, "
            . "ciniki_foodmarket_product_outputs.retail_sprice_text "
            . "FROM ciniki_foodmarket_products, ciniki_foodmarket_product_outputs "
            . "WHERE ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_foodmarket_products.status = 40 " // Product visible on website
            . "AND ciniki_foodmarket_products.id = ciniki_foodmarket_product_outputs.product_id "
            . "AND ciniki_foodmarket_product_outputs.status = 40 " // output visible on website
            . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "";
    }

    //
    // Get the specials
    //
    if( isset($args['type']) && $args['type'] == 'specials' ) {
        // Make sure it's an integer we're passing into the sql string.
        $strsql .= "AND ciniki_foodmarket_product_outputs.retail_sdiscount_percent > 0 ";
    }

    //
    // Get the new products
    //
    elseif( isset($args['type']) && $args['type'] == 'newproducts' ) {
        // FIXME: Implement new product listings
        $strsql .= "AND (ciniki_foodmarket_products.flags&0x01) = 0x01 ";
    }

    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'products', 'fname'=>'id', 'fields'=>array('id', 'name', 'permalink', 'image_id', 'synopsis')),
        array('container'=>'options', 'fname'=>'price_id', 'fields'=>array('id'=>'price_id', 'name'=>'io_name', 
            'price_display'=>'retail_price_text', 'price'=>'retail_price', 'sale_price_display'=>'retail_sprice_text')),
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
