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
function ciniki_foodmarket_web_queued($ciniki, $settings, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

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
        . "outputs.pio_name, "
        . "outputs.otype, "
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
            . "AND outputs.otype > 50 AND outputs.otype <= 60 " // Only partial case items
            . "AND outputs.status = 40 "  // Output visible on website
            . "AND outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_foodmarket_product_inputs AS inputs ON ("
            . "outputs.input_id = inputs.id "
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
    $strsql .= "ORDER BY pio_name, io_sequence ";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
//        array('container'=>'products', 'fname'=>'id', 
//            'fields'=>array('id', 'name', 'permalink', 'image_id', 'legend_codes', 'legend_names', 'synopsis')),
        array('container'=>'options', 'fname'=>'price_id', 
            'fields'=>array('id'=>'price_id', 'flags', 'name'=>'pio_name', 'otype', 'permalink',
                'price_text'=>'retail_price_text', 'price'=>'retail_price', 'sale_price_display'=>'retail_sprice_text', 
                'retail_mdiscount_percent', 'member_price'=>'retail_mprice', 'member_price_display'=>'retail_mprice_text', 'inventory')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $options = isset($rc['options']) ? $rc['options'] : array();

    foreach($options as $oid => $option) {
        if( $option['retail_mdiscount_percent'] > 0 && $option['member_price_display'] != '' ) {
            $options[$oid]['sale_price'] = $option['member_price'];
            $options[$oid]['sale_price_display'] = $option['member_price_display'];
        }
    }

    return array('stat'=>'ok', 'products'=>$options);
}
?>
