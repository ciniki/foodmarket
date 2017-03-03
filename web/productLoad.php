<?php
//
// Description
// ===========
// This function loads all the details for a product.
//
// Arguments
// ---------
// ciniki:
// business_id:         The ID of the business the product is attached to.
// product_id:          The ID of the product to get the details for.
//
// Returns
// -------
//
function ciniki_foodmarket_web_productLoad($ciniki, $settings, $business_id, $args) {

    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'maps');
    $rc = ciniki_foodmarket_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');

    //
    // Get the product details from the products table
    //
    $strsql = "SELECT ciniki_foodmarket_products.id, "
        . "ciniki_foodmarket_products.name, "
        . "ciniki_foodmarket_products.permalink, "
        . "ciniki_foodmarket_products.status, "
        . "ciniki_foodmarket_products.ptype, "
        . "ciniki_foodmarket_products.flags, "
        . "ciniki_foodmarket_products.category, "
        . "ciniki_foodmarket_products.primary_image_id AS image_id, "
        . "ciniki_foodmarket_products.legend_codes, "
        . "ciniki_foodmarket_products.legend_names, "
        . "ciniki_foodmarket_products.synopsis, "
        . "ciniki_foodmarket_products.description, "
        . "ciniki_foodmarket_products.ingredients, "
        . "ciniki_foodmarket_products.supplier_id "
        . "FROM ciniki_foodmarket_products "
        . "WHERE ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_foodmarket_products.status = 40 "
        . "";
    if( isset($args['permalink']) && $args['permalink'] != '' ) {
        $strsql .= "AND ciniki_foodmarket_products.permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' ";
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.39', 'msg'=>'No product specified.'));
    }
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'product');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.55', 'msg'=>'Product not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['product']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.foodmarket.56', 'msg'=>"I'm sorry, but we were unable to find that product."));
    }
    $product = $rc['product'];

    if( isset($product['legend_codes']) && $product['legend_codes'] != '' ) {
        $product['name'] .= ' ' . $product['legend_codes'];
    }

    //
    // Get the outputs for the product
    //
    $strsql = "SELECT ciniki_foodmarket_product_outputs.id, "
        . "ciniki_foodmarket_product_outputs.product_id, "
        . "ciniki_foodmarket_product_outputs.input_id, "
        . "ciniki_foodmarket_product_outputs.io_name AS name, "
        . "ciniki_foodmarket_product_outputs.permalink, "
        . "ciniki_foodmarket_product_outputs.status, "
        . "ciniki_foodmarket_product_outputs.otype, "
        . "ciniki_foodmarket_product_outputs.units, "
        . "ciniki_foodmarket_product_outputs.flags, "
        . "ciniki_foodmarket_product_outputs.sequence, "
        . "ciniki_foodmarket_product_outputs.start_date, "
        . "ciniki_foodmarket_product_outputs.end_date, "
        . "ciniki_foodmarket_product_outputs.retail_price AS price, "
        . "ciniki_foodmarket_product_outputs.retail_price_text AS price_text, "
        . "ciniki_foodmarket_product_outputs.retail_sprice AS sale_price, "
        . "ciniki_foodmarket_product_outputs.retail_sprice_text AS sale_price_text, "
        . "ciniki_foodmarket_product_outputs.retail_taxtype_id AS taxtype_id, "
        . "IFNULL(ciniki_foodmarket_product_inputs.inventory, 0) AS inventory "
        . "FROM ciniki_foodmarket_product_outputs "
        . "LEFT JOIN ciniki_foodmarket_product_inputs ON ("
            . "ciniki_foodmarket_product_outputs.input_id = ciniki_foodmarket_product_inputs.id "
            . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "WHERE ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_foodmarket_product_outputs.product_id = '" . ciniki_core_dbQuote($ciniki, $product['id']) . "' "
        . "AND ciniki_foodmarket_product_outputs.status = 40 "
        . "ORDER BY sequence, name "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'outputs', 'fname'=>'id', 
            'fields'=>array('id', 'product_id', 'input_id', 'name', 'permalink', 'status', 'status_text'=>'status', 'otype', 'otype_text'=>'otype', 
                'units', 'units_text'=>'units', 'flags', 'flags_text'=>'flags', 'sequence', 'start_date', 'end_date', 
                'price', 'price_text', 'sale_price', 'sale_price_text', 'taxtype_id', 'inventory',
                ),
            'maps'=>array(
                'status_text'=>$maps['output']['status'],
                'otype_text'=>$maps['output']['otype'],
                'units_text'=>$maps['output']['units'],
                ),
            'flags'=>array(
                'flags_text'=>$maps['output']['flags'],
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['outputs']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'web', 'prepareOutputs');
        $rc = ciniki_foodmarket_web_prepareOutputs($ciniki, $settings, $business_id, array('outputs'=>$rc['outputs']));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $product['outputs'] = $rc['outputs'];
    } else {
        $product['outputs'] = array();
    }

    //
    // Get the list of items in the basket
    //
    if( $product['ptype'] == 70 && ciniki_core_checkModuleFlags($ciniki, 'ciniki.foodmarket', 0x1000) ) {
        //
        // Get the basket output id
        //
        foreach($product['outputs'] as $output) {
            if( $output['otype'] == 70 ) {
                $basket_output_id = $output['id'];
            }
        }

        //
        // Get the details of the order date
        //
        if( isset($ciniki['session']['ciniki.poma']['date']['id']) && $ciniki['session']['ciniki.poma']['date']['id'] > 0 ) {
            //
            // Get the order date
            //
            $strsql = "SELECT id, status, display_name, ABS(DATEDIFF(NOW(), order_date)) AS age "
                . "FROM ciniki_poma_order_dates "
                . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['ciniki.poma']['date']['id']) . "' "
                . "ORDER BY age ASC "
                . "LIMIT 1 "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.poma', 'date');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['date']['id']) ) {
                $date_id = $rc['date']['id'];
                $product['subitems_date_text'] = $rc['date']['display_name'];
            }
        }
        if( !isset($date_id) ) {
            //
            // Get the next order date
            //
            $dt = new DateTime('now', new DateTimezone('UTC'));
            $strsql = "SELECT id, status, display_name "
                . "FROM ciniki_poma_order_dates "
                . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND order_date >= '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' "
                . "ORDER BY order_date ASC "
                . "LIMIT 1 "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.poma', 'date');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['date']['id']) ) {
                $date_id = $rc['date']['id'];
                $product['subitems_date_text'] = $rc['date']['display_name'];
            }
        }

        if( isset($basket_output_id) && isset($date_id) ) {
            
            //
            // Get the subitems for this order date
            //
            $strsql = "SELECT "
                . "ciniki_foodmarket_basket_items.item_output_id AS id, "
                . "ciniki_foodmarket_product_outputs.product_id, "
                . "ciniki_foodmarket_product_inputs.itype, "
                . "IFNULL(ciniki_foodmarket_product_inputs.units, 0) AS units, "
                . "IFNULL(ciniki_foodmarket_product_inputs.case_units, 1) AS case_units, "
                . "IFNULL(ciniki_foodmarket_product_inputs.min_quantity, 1) AS min_quantity, "
                . "ciniki_foodmarket_product_outputs.pio_name AS name, "
//                . "ciniki_foodmarket_products.name, "
                . "ciniki_foodmarket_product_outputs.otype, "
                . "ciniki_foodmarket_product_outputs.retail_price AS price, "
                . "ciniki_foodmarket_product_outputs.retail_price_text AS price_text, "
                . "ciniki_foodmarket_product_outputs.retail_sprice AS sale_price, "
                . "ciniki_foodmarket_product_outputs.retail_sprice_text AS sale_price_text, "
                . "ciniki_foodmarket_basket_items.basket_output_id, "
                . "ciniki_foodmarket_basket_items.quantity "
                . "FROM ciniki_foodmarket_basket_items "
                . "LEFT JOIN ciniki_foodmarket_product_outputs ON ("
                    . "ciniki_foodmarket_basket_items.item_output_id = ciniki_foodmarket_product_outputs.id "
                    . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                    . ") "
                . "LEFT JOIN ciniki_foodmarket_product_inputs ON ("
                    . "ciniki_foodmarket_product_outputs.input_id = ciniki_foodmarket_product_inputs.id "
                    . "AND ciniki_foodmarket_product_inputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                    . ") "
                . "LEFT JOIN ciniki_foodmarket_products ON ("
                    . "ciniki_foodmarket_product_outputs.product_id = ciniki_foodmarket_products.id "
                    . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                    . ") "
                . "WHERE ciniki_foodmarket_basket_items.date_id = '" . ciniki_core_dbQuote($ciniki, $date_id) . "' "
                . "AND ciniki_foodmarket_basket_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND ciniki_foodmarket_basket_items.basket_output_id = '" . ciniki_core_dbQuote($ciniki, $basket_output_id) . "' "
                . "ORDER BY pio_name, ciniki_foodmarket_basket_items.item_output_id "
                . "";
            $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
                array('container'=>'basket_items', 'fname'=>'id', 
                    'fields'=>array('id', 'product_id', 'itype', 'units', 'case_units', 'min_quantity', 'name', 'otype', 'price', 'price_text', 'sale_price', 'sale_price_text', 'quantity')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['basket_items']) ) {
                $product['subitems'] = array();
                foreach($rc['basket_items'] as $iid => $item) {
                    if( $item['quantity'] <= 0 ) {
                        continue;
                    }
                    $stext = '';
                    $ptext = '';
                    switch($item['units']) {
                        case 0x02: $stext = ' lb'; $ptext = ' lbs'; break;
                        case 0x04: $stext = ' oz'; $ptext = ' ozs'; break;
                        case 0x20: $stext = ' kg'; $ptext = ' kgs'; break;
                        case 0x40: $stext = ' g'; $ptext = ' gs'; break;
                        case 0x0100: $stext = ''; $ptext = ''; break;
                        case 0x0200: $stext = ' pair'; $ptext = ' pairs'; break;
                        case 0x0400: $stext = '  bunch'; $ptext = ' bunches'; break;
                        case 0x0800: $stext = ' bag'; $ptext = ' bags'; break;
                        case 0x010000: $stext = ' case'; $ptext = ' cases'; break;
                        case 0x020000: $stext = ' bushel'; $ptext = ' bushels'; break;
                    }
                    $product['subitems'][] = array(
                        'name'=>$item['name'],
                        'quantity_text'=>(float)$item['quantity'] . ($item['quantity'] != 1 ? $ptext : $stext),
                        );
//                    $content .= ($content != '' ? "\n" : '') . (float)$item['quantity'] . ($item['quantity'] != 1 ? $ptext : $stext) . ' - ' . $item['name'];
//                    if( $item['otype'] == 71 ) {
//                        $content .= ($content != '' ? "\n" : '') . (float)$item['quantity'] . ($item['quantity'] != 1 ? $ptext : $stext) . ' - ' . $item['name'];
//                    } else {
//                        $content .= ($content != '' ? "\n" : '') . (float)$item['quantity'] . ' ' . $item['name'];
//                    }
                }
//                $content .= "<pre>" . print_r($rc['basket_items'], true) . "</pre>";
            }
//            $product['description'] .= ($product['description'] != '' ? "\n\n" : '') . $content;
        }
    }

    return array('stat'=>'ok', 'product'=>$product);
}
?>
