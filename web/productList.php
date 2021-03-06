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
    // Select the queued products
    //
    if( isset($args['type']) && $args['type'] == 'queued' ) {
        // Fixed to only show partial case items
        $strsql = "SELECT products.id, "
            . "products.name, "
            . "products.permalink, "
            . "products.primary_image_id AS image_id, "
            . "products.legend_codes, "
            . "products.legend_names, "
            . "products.synopsis, "
            . "outputs.id AS price_id, "
            . "outputs.flags, "
            . "outputs.io_name, "
            . "outputs.retail_price, "
            . "outputs.retail_price_text, "
            . "outputs.retail_sprice_text, "
            . "outputs.retail_mdiscount_percent, "
            . "outputs.retail_mprice, "
            . "outputs.retail_mprice_text, "
            . "inputs.inventory "
            . "FROM ciniki_poma_queued_items AS items "
            . "INNER JOIN ciniki_foodmarket_product_outputs AS outputs ON ("
                . "items.object_id = outputs.id "
                . "AND (output.otype = 30 || (outputs.otype > 50 AND outputs.otype <= 60)) " // Only partial case items
                . "AND outputs.status = 40 "  // Output visible on website
                . "AND outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_foodmarket_product_inputs AS inputs ON ("
                . "outputs.input_id = inputs.id "
                . "AND inputs.itype = 50 "
                . "AND inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_foodmarket_products AS products ON ("
                . "inputs.product_id = products.id "
                . "AND products.status = 40 " // Product visible on website
                . "AND products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "

/*            
            IF there is a need to list all product options in the queued product list
            this SQl will work. Otherwise it only lists the outputs that are in the queue
            so the full case purchase option won't be shown

            . "INNER JOIN ciniki_foodmarket_product_outputs AS qitems ON ("
                . "items.object_id = qitems.id "
                . "AND qitems.status = 40 "  // Output visible on website
                . "AND qitems.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_foodmarket_products AS products ON ("
                . "qitems.product_id = products.id "
                . "AND products.status = 40 " // Product visible on website
                . "AND products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_foodmarket_product_inputs AS inputs ON ("
                . "products.id = inputs.product_id "
                . "AND inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_foodmarket_product_outputs AS outputs ON ("
                . "outputs.input_id = inputs.id "
                . "AND outputs.status = 40 "  // Output visible on website
                . "AND outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") " */
            . "WHERE items.object = 'ciniki.foodmarket.output' "
            . "AND items.status < 40 " // Active item in queue but not yet ordered
            . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
            
    }
    //
    // Select the products for a category
    //
    elseif( isset($args['category_id']) ) {
        $strsql = "SELECT products.id, "
            . "products.name, "
            . "products.permalink, "
            . "products.primary_image_id AS image_id, "
            . "products.legend_codes, "
            . "products.legend_names, "
            . "products.synopsis, "
            . "outputs.id AS price_id, "
            . "outputs.flags, "
            . "outputs.io_name, "
            . "outputs.retail_price, "
            . "outputs.retail_price_text, "
            . "outputs.retail_sprice_text, "
            . "outputs.retail_mdiscount_percent, "
            . "outputs.retail_mprice, "
            . "outputs.retail_mprice_text, "
            . "inputs.inventory "
            . "FROM ciniki_foodmarket_category_items AS items "
            . "INNER JOIN ciniki_foodmarket_products AS products ON ("
                . "items.product_id = products.id "
                . "AND products.status = 40 " // Product visible on website
                . "AND products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_foodmarket_product_outputs AS outputs ON ("
                . "products.id = outputs.product_id "
                . "AND outputs.status = 40 "  // Output visible on website
                . "AND outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_inputs AS inputs ON ("
                . "products.id = inputs.product_id "
                . "AND inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE items.category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
            . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
    } 
    //
    // Select the products from the sub-categories
    //
    elseif( isset($args['parent_id']) ) {
        $strsql = "SELECT products.id, "
            . "products.name, "
            . "products.permalink, "
            . "products.primary_image_id AS image_id, "
            . "products.legend_codes, "
            . "products.legend_names, "
            . "products.synopsis, "
            . "outputs.id AS price_id, "
            . "outputs.flags, "
            . "outputs.io_name, "
            . "outputs.retail_price, "
            . "outputs.retail_price_text, "
            . "outputs.retail_sprice_text, "
            . "outputs.retail_mdiscount_percent, "
            . "outputs.retail_mprice, "
            . "outputs.retail_mprice_text, "
            . "inputs.inventory "
            . "FROM ciniki_foodmarket_categories AS categories "
            . "INNER JOIN ciniki_foodmarket_category_items AS items ON ("
                . "categories.id = items.category_id "
                . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND items.product_id = products.id "
                . ") "
            . "INNER JOIN ciniki_foodmarket_products AS products ON ("
                . "products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND products.status = 40 " // Product visible on website
                . ") "
            . "INNER JOIN ciniki_foodmarket_product_outputs AS outputs ON ("
                . "products.id = outputs.product_id "   
                . "AND outputs.status = 40 " // output visible on website
                . "AND outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_inputs AS inputs ON ("
                . "products.id = inputs.product_id "
                . "AND inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE categories.parent_id  = '" . ciniki_core_dbQuote($ciniki, $args['parent_id']) . "' "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
    } 
    //
    // Select products from any category
    //
    else {
        $strsql = "SELECT products.id, "
            . "products.name, "
            . "products.permalink, "
            . "products.primary_image_id AS image_id, "
            . "products.legend_codes, "
            . "products.legend_names, "
            . "products.synopsis, "
            . "outputs.id AS price_id, "
            . "outputs.flags, "
            . "outputs.io_name, "
            . "outputs.retail_price, "
            . "outputs.retail_price_text, "
            . "outputs.retail_sprice_text, "
            . "outputs.retail_mdiscount_percent, "
            . "outputs.retail_mprice, "
            . "outputs.retail_mprice_text, "
            . "inputs.inventory "
            . "FROM ciniki_foodmarket_products AS products "
            . "INNER JOIN ciniki_foodmarket_product_outputs AS outputs ON ("
                . "products.id = outputs.product_id "
                . "AND outputs.status = 40 " // output visible on website
                . "AND outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_inputs AS inputs ON ("
                . "products.id = inputs.product_id "
                . "AND inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND products.status = 40 " // Product visible on website
            . "";
    }

    //
    // Get the specials
    //
    if( isset($args['type']) && $args['type'] == 'specials' ) {
        // Make sure it's an integer we're passing into the sql string.
        $strsql .= "AND outputs.retail_sdiscount_percent > 0 ";
    }

    //
    // Get the new products
    //
    elseif( isset($args['type']) && $args['type'] == 'newproducts' ) {
        $strsql .= "AND (products.flags&0x01) = 0x01 ";
    }

    $strsql .= "ORDER BY pio_name, io_sequence ";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'products', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'image_id', 'legend_codes', 'legend_names', 'synopsis')),
        array('container'=>'options', 'fname'=>'price_id', 
            'fields'=>array('id'=>'price_id', 'flags', 'name'=>'io_name', 
                'price_display'=>'retail_price_text', 'price'=>'retail_price', 'sale_price_display'=>'retail_sprice_text', 
                'retail_mdiscount_percent', 'member_price'=>'retail_mprice', 'member_price_display'=>'retail_mprice_text', 'inventory')),
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
        if( isset($ciniki['session']['customer']['foodmarket.member']) 
            && $ciniki['session']['customer']['foodmarket.member'] = 'yes' 
            && isset($product['options']) 
            ) {
            foreach($product['options'] as $oid => $option) {
                if( $option['retail_mdiscount_percent'] > 0 && $option['member_price_display'] != '' ) {
                    $products[$pid]['options'][$oid]['sale_price'] = $option['member_price'];
                    $products[$pid]['options'][$oid]['sale_price_display'] = $option['member_price_display'];
                }
            }
        }
    }

    return array('stat'=>'ok', 'products'=>$products);
}
?>
