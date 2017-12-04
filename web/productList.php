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
function ciniki_foodmarket_web_productList($ciniki, $settings, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

    //
    // Select the products for a category
    //
    if( isset($args['category_id']) ) {
        $strsql = "SELECT ciniki_foodmarket_products.id, "
            . "ciniki_foodmarket_products.name, "
            . "ciniki_foodmarket_products.permalink, "
            . "ciniki_foodmarket_products.primary_image_id AS image_id, "
            . "ciniki_foodmarket_products.legend_codes, "
            . "ciniki_foodmarket_products.legend_names, "
            . "ciniki_foodmarket_products.synopsis, "
            . "ciniki_foodmarket_product_outputs.id AS price_id, "
            . "ciniki_foodmarket_product_outputs.flags, "
            . "ciniki_foodmarket_product_outputs.io_name, "
            . "ciniki_foodmarket_product_outputs.retail_price, "
            . "ciniki_foodmarket_product_outputs.retail_price_text, "
            . "ciniki_foodmarket_product_outputs.retail_sprice_text "
            . "FROM ciniki_foodmarket_category_items, ciniki_foodmarket_products, ciniki_foodmarket_product_outputs "
            . "WHERE ciniki_foodmarket_category_items.category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
            . "AND ciniki_foodmarket_category_items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_foodmarket_category_items.product_id = ciniki_foodmarket_products.id "
            . "AND ciniki_foodmarket_products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_foodmarket_products.status = 40 " // Product visible on website
            . "AND ciniki_foodmarket_products.id = ciniki_foodmarket_product_outputs.product_id "
            . "AND ciniki_foodmarket_product_outputs.status = 40 "  // Output visible on website
            . "AND ciniki_foodmarket_product_outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
            . "ciniki_foodmarket_products.legend_codes, "
            . "ciniki_foodmarket_products.legend_names, "
            . "ciniki_foodmarket_products.synopsis, "
            . "ciniki_foodmarket_product_outputs.id AS price_id, "
            . "ciniki_foodmarket_product_outputs.flags, "
            . "ciniki_foodmarket_product_outputs.io_name, "
            . "ciniki_foodmarket_product_outputs.retail_price, "
            . "ciniki_foodmarket_product_outputs.retail_price_text, "
            . "ciniki_foodmarket_product_outputs.retail_sprice_text "
            . "FROM ciniki_foodmarket_categories, ciniki_foodmarket_category_items, ciniki_foodmarket_products, ciniki_foodmarket_product_outputs "
            . "WHERE ciniki_foodmarket_categories.parent_id  = '" . ciniki_core_dbQuote($ciniki, $args['parent_id']) . "' "
            . "AND ciniki_foodmarket_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_foodmarket_categories.id = ciniki_foodmarket_category_items.category_id "
            . "AND ciniki_foodmarket_category_items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_foodmarket_category_items.product_id = ciniki_foodmarket_products.id "
            . "AND ciniki_foodmarket_products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_foodmarket_products.status = 40 " // Product visible on website
            . "AND ciniki_foodmarket_products.id = ciniki_foodmarket_product_outputs.product_id "   
            . "AND ciniki_foodmarket_product_outputs.status = 40 " // output visible on website
            . "AND ciniki_foodmarket_product_outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
            . "ciniki_foodmarket_products.legend_codes, "
            . "ciniki_foodmarket_products.legend_names, "
            . "ciniki_foodmarket_products.synopsis, "
            . "ciniki_foodmarket_product_outputs.id AS price_id, "
            . "ciniki_foodmarket_product_outputs.flags, "
            . "ciniki_foodmarket_product_outputs.io_name, "
            . "ciniki_foodmarket_product_outputs.retail_price, "
            . "ciniki_foodmarket_product_outputs.retail_price_text, "
            . "ciniki_foodmarket_product_outputs.retail_sprice_text "
            . "FROM ciniki_foodmarket_products, ciniki_foodmarket_product_outputs "
            . "WHERE ciniki_foodmarket_products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_foodmarket_products.status = 40 " // Product visible on website
            . "AND ciniki_foodmarket_products.id = ciniki_foodmarket_product_outputs.product_id "
            . "AND ciniki_foodmarket_product_outputs.status = 40 " // output visible on website
            . "AND ciniki_foodmarket_product_outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
        $strsql .= "AND (ciniki_foodmarket_products.flags&0x01) = 0x01 ";
    }

    $strsql .= "ORDER BY pio_name, io_sequence ";

    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'products', 'fname'=>'id', 'fields'=>array('id', 'name', 'permalink', 'image_id', 'legend_codes', 'legend_names', 'synopsis')),
        array('container'=>'options', 'fname'=>'price_id', 'fields'=>array('id'=>'price_id', 'flags', 'name'=>'io_name', 
            'price_display'=>'retail_price_text', 'price'=>'retail_price', 'sale_price_display'=>'retail_sprice_text')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['products']) ) {
        return array('stat'=>'ok', 'products'=>array());
    }
    $products = $rc['products'];

    foreach($products as $pid => $product) {
        if( $product['legend_codes'] != '' ) {
            $products[$pid]['name'] .= ' ' . $product['legend_codes'];
        }
    }

    return array('stat'=>'ok', 'products'=>$products);
}
?>
