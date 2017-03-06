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
function ciniki_foodmarket_productLoad($ciniki, $business_id, $product_id) {

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
        . "ciniki_foodmarket_products.packing_order, "
        . "ciniki_foodmarket_products.primary_image_id, "
        . "ciniki_foodmarket_products.synopsis, "
        . "ciniki_foodmarket_products.description, "
        . "ciniki_foodmarket_products.ingredients, "
        . "ciniki_foodmarket_products.supplier_id "
        . "FROM ciniki_foodmarket_products "
        . "WHERE ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_foodmarket_products.id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'product');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.20', 'msg'=>'Product not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['product']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.21', 'msg'=>'Unable to find Product'));
    }
    $product = $rc['product'];

    //
    // Get the list of categories the product is in
    //
    $strsql = "SELECT category_id "
        . "FROM ciniki_foodmarket_category_items "
        . "WHERE ciniki_foodmarket_category_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_foodmarket_category_items.product_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
        . "";
    $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.foodmarket', 'categories', 'category_id');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['categories']) ) {
        $product['categories'] = $rc['categories'];
    } else {
        $product['categories'] = array();
    }

    //
    // Get the list of legends the product is in
    //
    $strsql = "SELECT legend_id "
        . "FROM ciniki_foodmarket_legend_items "
        . "WHERE ciniki_foodmarket_legend_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_foodmarket_legend_items.product_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
        . "";
    $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.foodmarket', 'legends', 'legend_id');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['legends']) ) {
        $product['legends'] = $rc['legends'];
    } else {
        $product['legends'] = array();
    }

    //
    // Get the inputs of the product
    //
    $strsql = "SELECT ciniki_foodmarket_product_inputs.id, "
        . "ciniki_foodmarket_product_inputs.product_id, "
        . "ciniki_foodmarket_product_inputs.name, "
        . "ciniki_foodmarket_product_inputs.permalink, "
        . "ciniki_foodmarket_product_inputs.status, "
        . "ciniki_foodmarket_product_inputs.status AS status_text, "
        . "ciniki_foodmarket_product_inputs.itype, "
        . "ciniki_foodmarket_product_inputs.itype AS itype_text, "
        . "ciniki_foodmarket_product_inputs.units, "
        . "ciniki_foodmarket_product_inputs.units AS units_text, "
        . "ciniki_foodmarket_product_inputs.flags, "
        . "ciniki_foodmarket_product_inputs.flags AS flags_text, "
        . "ciniki_foodmarket_product_inputs.sequence, "
        . "ciniki_foodmarket_product_inputs.case_cost, "
        . "ciniki_foodmarket_product_inputs.half_cost, "
        . "ciniki_foodmarket_product_inputs.unit_cost, "
        . "ciniki_foodmarket_product_inputs.case_units, "
        . "ciniki_foodmarket_product_inputs.min_quantity, "
        . "ciniki_foodmarket_product_inputs.inc_quantity, "
        . "ciniki_foodmarket_product_inputs.cdeposit_name, "
        . "ciniki_foodmarket_product_inputs.cdeposit_amount, "
        . "ciniki_foodmarket_product_inputs.sku, "
        . "ciniki_foodmarket_product_inputs.inventory, "
        . "ciniki_foodmarket_product_inputs.recipe_id, "
        . "ciniki_foodmarket_product_inputs.recipe_quantity, "
        . "ciniki_foodmarket_product_inputs.container_id, "
        . "ciniki_foodmarket_product_inputs.materials_cost_per_container, "
        . "ciniki_foodmarket_product_inputs.time_cost_per_container, "
        . "ciniki_foodmarket_product_inputs.total_cost_per_container, "
        . "ciniki_foodmarket_product_inputs.total_time_per_container "
        . "FROM ciniki_foodmarket_product_inputs "
        . "WHERE ciniki_foodmarket_product_inputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_foodmarket_product_inputs.product_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
        . "ORDER BY sequence, name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'inputs', 'fname'=>'id', 
            'fields'=>array('id', 'product_id', 'name', 'permalink', 'status', 'status_text', 'itype', 'itype_text', 
                'units', 'units_text', 'flags', 'flags_text', 'sequence', 'case_cost', 'half_cost', 'unit_cost', 'case_units',
                'min_quantity', 'inc_quantity', 'cdeposit_name', 'cdeposit_amount', 'sku', 'inventory', 'recipe_id', 'recipe_quantity', 'container_id', 
                'materials_cost_per_container', 'time_cost_per_container', 'total_cost_per_container', 'total_time_per_container', 
                ),
//            'currency'=>array(
//                'materials_cost_per_container'=>array('fmt'=>$intl_currency_fmt, 'currency'=>$intl_currency),
//                'time_cost_per_container'=>array('fmt'=>$intl_currency_fmt, 'currency'=>$intl_currency),
//                'total_cost_per_container'=>array('fmt'=>$intl_currency_fmt, 'currency'=>$intl_currency),
//                ),
            'maps'=>array(
                'status_text'=>$maps['input']['status'],
                'itype_text'=>$maps['input']['itype'],
                ),
            'flags'=>array(
                'units_text'=>$maps['input']['units'],
                'flags_text'=>$maps['input']['flags'],
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['inputs']) ) {
        $product['inputs'] = $rc['inputs'];
    } else {
        $product['inputs'] = array();
    }

    //
    // Get the outputs for the product
    //
    $strsql = "SELECT ciniki_foodmarket_product_outputs.id, "
        . "ciniki_foodmarket_product_outputs.product_id, "
        . "ciniki_foodmarket_product_outputs.input_id, "
        . "ciniki_foodmarket_product_outputs.name, "
        . "ciniki_foodmarket_product_outputs.permalink, "
        . "ciniki_foodmarket_product_outputs.status, "
        . "ciniki_foodmarket_product_outputs.otype, "
        . "ciniki_foodmarket_product_outputs.units, "
        . "ciniki_foodmarket_product_outputs.flags, "
        . "ciniki_foodmarket_product_outputs.sequence, "
//        . "ciniki_foodmarket_product_outputs.packing_order, "
        . "ciniki_foodmarket_product_outputs.start_date, "
        . "ciniki_foodmarket_product_outputs.end_date, "
        . "ciniki_foodmarket_product_outputs.wholesale_percent, "
        . "ciniki_foodmarket_product_outputs.wholesale_price, "
        . "ciniki_foodmarket_product_outputs.wholesale_taxtype_id, "
        . "ciniki_foodmarket_product_outputs.retail_percent, "
        . "ciniki_foodmarket_product_outputs.retail_sdiscount_percent, "
        . "ciniki_foodmarket_product_outputs.retail_price, "
        . "ciniki_foodmarket_product_outputs.retail_taxtype_id "
        . "FROM ciniki_foodmarket_product_outputs "
        . "WHERE ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_foodmarket_product_outputs.product_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
        . "ORDER BY sequence, name "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'outputs', 'fname'=>'id', 
            'fields'=>array('id', 'product_id', 'input_id', 'name', 'permalink', 'status', 'status_text'=>'status', 'otype', 'otype_text'=>'otype', 
                'units', 'units_text'=>'units', 'flags', 'flags_text'=>'flags', 'sequence', 'start_date', 'end_date', 
                'wholesale_percent', 'wholesale_price', 'wholesale_taxtype_id', 'retail_percent', 'retail_sdiscount_percent', 'retail_price', 'retail_taxtype_id', 
                ),
            'currency'=>array(
                'wholesale_price'=>array('fmt'=>$intl_currency_fmt, 'currency'=>$intl_currency),
                'retail_price'=>array('fmt'=>$intl_currency_fmt, 'currency'=>$intl_currency),
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
        $product['outputs'] = $rc['outputs'];
    } else {
        $product['outputs'] = array();
    }

    //
    // Flatten the inputs and outputs so they are usuable in the UI and used to compare what the UI submits
    //

    // 
    // Supplied Products or product baskets
    //
    if( $product['ptype'] == 10 || $product['ptype'] == 70 ) {
        $idx = 1;
        foreach($product['inputs'] as $iid => $input) {
            $product['inputs'][$iid]['idx'] = $idx;
            $product['input' . $idx . '_id'] = $input['id'];
            $product['input' . $idx . '_name'] = $input['name'];
            $product['input' . $idx . '_status'] = $input['status'];
            $product['input' . $idx . '_itype'] = $input['itype'];
            $product['input' . $idx . '_units'] = $input['units'];
            $product['input' . $idx . '_flags'] = $input['flags'];
            $product['input' . $idx . '_sequence'] = $input['sequence'];
            $product['input' . $idx . '_case_cost'] = number_format($input['case_cost'], 2);
            $product['input' . $idx . '_half_cost'] = number_format($input['half_cost'], 2);
            if( $input['unit_cost'] < 1 ) {
                $product['input' . $idx . '_unit_cost'] = number_format($input['unit_cost'], 4);
                $product['input' . $idx . '_unit_cost_calc'] = number_format($input['unit_cost'], 4);
            } else {
                $product['input' . $idx . '_unit_cost'] = number_format($input['unit_cost'], 2);
                $product['input' . $idx . '_unit_cost_calc'] = number_format($input['unit_cost'], 2);
            }
            $product['input' . $idx . '_case_units'] = number_format($input['case_units'], 2);
            $product['input' . $idx . '_min_quantity'] = $input['min_quantity'] + 0;
            $product['input' . $idx . '_inc_quantity'] = $input['inc_quantity'] + 0;
            $product['input' . $idx . '_cdeposit_name'] = $input['cdeposit_name'];
            $product['input' . $idx . '_cdeposit_amount'] = number_format($input['cdeposit_amount'], 2);
            $product['input' . $idx . '_sku'] = $input['sku'];
            $product['input' . $idx . '_inventory'] = $input['inventory'] + 0;
            
            $idx++;
        }

        //
        // Flatten the outputs
        //
        foreach($product['outputs'] as $oid => $output) {
            //
            // Deal the output for manufactured products, which may be tied to inputs
            //
            if( $product['ptype'] == 10 ) {
                if( $output['input_id'] > 0 && isset($product['inputs'][$output['input_id']]['idx']) ) {
                    $idx = $product['inputs'][$output['input_id']]['idx'];
                } else {
                    error_log("No input for supplied product_id: " . $output['product_id'] . ", output_id: " . $output['id']);
                    $idx = 0;
                }

                $product['input' . $idx . '_' . $output['otype'] . '_id'] = $output['id'];
                $product['input' . $idx . '_' . $output['otype'] . '_input_id'] = $output['input_id'];
                $product['input' . $idx . '_' . $output['otype'] . '_name'] = $output['name'];
                $product['input' . $idx . '_' . $output['otype'] . '_status'] = $output['status'];
                $product['input' . $idx . '_' . $output['otype'] . '_otype'] = $output['otype'];
                $product['input' . $idx . '_' . $output['otype'] . '_units'] = $output['units'];
                $product['input' . $idx . '_' . $output['otype'] . '_flags'] = $output['flags'];
                $product['input' . $idx . '_' . $output['otype'] . '_sequence'] = $output['sequence'];
//                $product['input' . $idx . '_' . $output['otype'] . '_packing_order'] = $output['packing_order'];
                $product['input' . $idx . '_' . $output['otype'] . '_start_date'] = $output['start_date'];
                $product['input' . $idx . '_' . $output['otype'] . '_end_date'] = $output['end_date'];
                $product['input' . $idx . '_' . $output['otype'] . '_wholesale_percent'] = number_format($output['wholesale_percent'], 2);
                $product['input' . $idx . '_' . $output['otype'] . '_wholesale_price'] = $output['wholesale_price'];
                $product['input' . $idx . '_' . $output['otype'] . '_wholesale_taxtype_id'] = $output['wholesale_taxtype_id'];
                $product['input' . $idx . '_' . $output['otype'] . '_retail_percent'] = number_format($output['retail_percent'], 2);
                $product['input' . $idx . '_' . $output['otype'] . '_retail_sdiscount_percent'] = number_format($output['retail_sdiscount_percent'], 2);
                $product['input' . $idx . '_' . $output['otype'] . '_retail_price'] = $output['retail_price'];
                $product['input' . $idx . '_' . $output['otype'] . '_retail_taxtype_id'] = $output['retail_taxtype_id'];
                
                //
                // For baskets calculate the discount
                //
                if( $output['otype'] == 71 || $output['otype'] == 72 ) {
                    foreach($product['outputs'] as $output_id => $o) {
                        if( $output['input_id'] == $o['input_id'] 
                            && (($output['otype'] == 71 && $o['otype'] == 10) || ($output['otype'] == 72 && $o['otype'] == 30))
                            ) {
                            $output['retail_discount'] = bcsub(1, 
                                bcdiv(bcadd($output['retail_percent'], 1, 6), bcadd($o['retail_percent'], 1, 6), 6)
                                , 6);
                            $product['input' . $idx . '_' . $output['otype'] . '_retail_discount'] = number_format($output['retail_discount'], 2, '.', '');
                        }
                    }
                }
            }

            //
            // Deal with output for product baskets, there should only be one output
            //
            elseif( $product['ptype'] == 70 && $output['otype'] == 70 ) {
                $product['basket_output_id'] = $output['id'];
                $product['basket_retail_price'] = number_format($output['retail_price'], 2, '.', '');
                $product['basket_retail_taxtype_id'] = $output['retail_taxtype_id'];
            }
        }
    }

    return array('stat'=>'ok', 'product'=>$product);
}
?>
