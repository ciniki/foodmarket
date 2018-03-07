<?php
//
// Description
// ===========
// This function calculates the prices for outputs based on specified percents. It also sets up the
// display name used when the outputs are pulled for standing orders, queued and favourites.
//
// Arguments
// ---------
// ciniki:
// tnid:         The ID of the tenant the product is attached to.
// product_id:          The ID of the product to get the details for.
// args:                Typically the $ciniki['request']['args'] variable should be passed here.
//
// Returns
// -------
//
function ciniki_foodmarket_productUpdateFields(&$ciniki, $tnid, $product_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makeKeywords');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'queryList');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'convertWeightPrice');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'unitsText');

    //
    // Get the product details from the products table
    //
    $strsql = "SELECT ciniki_foodmarket_products.id, "
        . "ciniki_foodmarket_products.name, "
        . "ciniki_foodmarket_products.permalink, "
        . "ciniki_foodmarket_products.status, "
        . "ciniki_foodmarket_products.ptype, "
        . "ciniki_foodmarket_products.flags, "
        . "ciniki_foodmarket_products.legend_codes, "
        . "ciniki_foodmarket_products.legend_names, "
        . "ciniki_foodmarket_products.category, "
        . "ciniki_foodmarket_products.primary_image_id, "
        . "ciniki_foodmarket_products.synopsis, "
        . "ciniki_foodmarket_products.description, "
        . "ciniki_foodmarket_products.ingredients, "
        . "ciniki_foodmarket_products.supplier_id "
        . "FROM ciniki_foodmarket_products "
        . "WHERE ciniki_foodmarket_products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_foodmarket_products.id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'product');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.36', 'msg'=>'Product not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['product']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.37', 'msg'=>'Unable to find Product'));
    }
    $product = $rc['product'];

    //
    // Get the legends for the product
    //
    $strsql = "SELECT code, name "
        . "FROM ciniki_foodmarket_legend_items AS items, ciniki_foodmarket_legends AS legends "
        . "WHERE items.product_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
        . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND items.legend_id = legends.id "
        . "AND legends.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY name, code "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $legend_codes = '';
    $legend_names = '';
    if( isset($rc['rows']) ) {
        foreach($rc['rows'] as $row) {
            $legend_codes .= ($legend_codes != '' ? ' ' : '') . $row['code'];
            $legend_names .= ($legend_names != '' ? ' ' : '') . $row['name'];
        }
    }

    //
    // Load the inputs and their outputs
    //
    $strsql = "SELECT ciniki_foodmarket_product_inputs.id, "
        . "ciniki_foodmarket_product_inputs.name, "
        . "ciniki_foodmarket_product_inputs.sequence, "
        . "ciniki_foodmarket_product_inputs.itype, "
        . "ciniki_foodmarket_product_inputs.units, "
        . "ciniki_foodmarket_product_inputs.flags, "
        . "ciniki_foodmarket_product_inputs.case_cost, "
        . "ciniki_foodmarket_product_inputs.half_cost, "
        . "ciniki_foodmarket_product_inputs.unit_cost, "
        . "ciniki_foodmarket_product_inputs.case_units "
        . "FROM ciniki_foodmarket_product_inputs "
        . "WHERE ciniki_foodmarket_product_inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_foodmarket_product_inputs.product_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
        . "ORDER BY sequence, name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'inputs', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'sequence', 'itype', 'units', 'flags', 'case_cost', 'half_cost', 'unit_cost', 'case_units')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['inputs']) ) {
        $inputs = $rc['inputs'];
    } else {
        $inputs = array();
    }

    //
    // Get the outputs for the product
    //
    $strsql = "SELECT ciniki_foodmarket_product_outputs.id, "
        . "ciniki_foodmarket_product_outputs.input_id, "
        . "ciniki_foodmarket_product_outputs.name, "
        . "ciniki_foodmarket_product_outputs.keywords, "
        . "ciniki_foodmarket_product_outputs.pio_name, "
        . "ciniki_foodmarket_product_outputs.io_name, "
        . "ciniki_foodmarket_product_outputs.sequence, "
        . "ciniki_foodmarket_product_outputs.io_sequence, "
        . "ciniki_foodmarket_product_outputs.otype, "
        . "ciniki_foodmarket_product_outputs.units, "
        . "ciniki_foodmarket_product_outputs.flags, "
        . "ciniki_foodmarket_product_outputs.wholesale_percent, "
        . "ciniki_foodmarket_product_outputs.wholesale_price, "
        . "ciniki_foodmarket_product_outputs.retail_percent, "
        . "ciniki_foodmarket_product_outputs.retail_price, "
        . "ciniki_foodmarket_product_outputs.retail_price_text, "
        . "ciniki_foodmarket_product_outputs.retail_sdiscount_percent, "
        . "ciniki_foodmarket_product_outputs.retail_sprice, "
        . "ciniki_foodmarket_product_outputs.retail_sprice_text, "
        . "ciniki_foodmarket_product_outputs.retail_mdiscount_percent, "
        . "ciniki_foodmarket_product_outputs.retail_mprice, "
        . "ciniki_foodmarket_product_outputs.retail_mprice_text "
        . "FROM ciniki_foodmarket_product_outputs "
        . "WHERE ciniki_foodmarket_product_outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_foodmarket_product_outputs.product_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
        . "ORDER BY sequence, name "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'outputs', 'fname'=>'id', 
            'fields'=>array('id', 'input_id', 'name', 'pio_name', 'io_name', 'sequence', 'io_sequence', 'keywords', 'otype', 'units', 'flags', 
                'wholesale_percent', 'wholesale_price', 'retail_percent', 'retail_price', 'retail_price_text', 
                'retail_sdiscount_percent', 'retail_sprice', 'retail_sprice_text',
                'retail_mdiscount_percent', 'retail_mprice', 'retail_mprice_text')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['outputs']) ) {
        $outputs = $rc['outputs'];
    } else {
        $outputs = array();
    }

    //
    // Run the calculations for the inputs
    //
    foreach($inputs as $input_id => $input) {
        if( $input['itype'] == 50 ) {
            if( $input['case_cost'] != 0 && $input['case_units'] != 0 ) {
                // Round the number to 4 digits, same as database storage
                $input['unit_cost'] = number_format(bcdiv($input['case_cost'], $input['case_units'], 6), 4);
            } else {
                $input['unit_cost'] = 0;
            }
        } else {
            $input['case_cost'] = 0;
            $input['half_cost'] = 0;
        }
        //
        // Check for changed fields and build array of fields to update, and update the $inputs array
        //
        $update_args = array();
        foreach(['case_cost', 'unit_cost'] as $field) {
            if( $input[$field] != $inputs[$input_id][$field] ) {
                $update_args[$field] = $input[$field];
                $inputs[$input_id][$field] = $input[$field];
            }
        }
        if( count($update_args) > 0 ) {
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.foodmarket.input', $input_id, $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    //
    // Run the calculations for outputs
    //
    foreach($outputs as $output_id => $output) {
        if( $output['input_id'] > 0 && isset($inputs[$output['input_id']]) ) {
            $input = $inputs[$output['input_id']];
            $output['io_sequence'] = ($input['sequence'] * 1000) + $output['sequence'];
        } else {
            $input = array();
        }

        $case_text = 'case';
        if( isset($input['units']) && ($input['units']&0x020000) == 0x020000) {
            $case_text = 'bushel';
        }
        $output['io_name'] = '';
        if( isset($input['name']) && $input['name'] != '' ) {
            if( $output['otype'] == 50 ) {
                $output['io_name'] .= ($output['io_name'] != '' ? ' - ' : '') . ($output['name'] != '' ? $output['name'] : $case_text) . ' (' . $input['case_units'] . 'x' . $input['name'] . ')';
            } elseif( $output['otype'] == 52 ) {
                $output['io_name'] .= ($output['io_name'] != '' ? ' - ' : '') . ($output['name'] != '' ? $output['name'] : '1/2 ' . $case_text) . ' (' . bcdiv($input['case_units'], 2, 0) . 'x' . $input['name'] . ')';
            } elseif( $output['otype'] == 53 ) {
                $output['io_name'] .= ($output['io_name'] != '' ? ' - ' : '') . ($output['name'] != '' ? $output['name'] : '1/3 ' . $case_text) . ' (' . bcdiv($input['case_units'], 3, 0) . 'x' . $input['name'] . ')';
            } elseif( $output['otype'] == 54 ) {
                $output['io_name'] .= ($output['io_name'] != '' ? ' - ' : '') . ($output['name'] != '' ? $output['name'] : '1/4 ' . $case_text) . ' (' . bcdiv($input['case_units'], 4, 0) . 'x' . $input['name'] . ')';
            } elseif( $output['otype'] == 55 ) {
                $output['io_name'] .= ($output['io_name'] != '' ? ' - ' : '') . ($output['name'] != '' ? $output['name'] : '1/5 ' . $case_text) . ' (' . bcdiv($input['case_units'], 5, 0) . 'x' . $input['name'] . ')';
            } elseif( $output['otype'] == 56 ) {
                $output['io_name'] .= ($output['io_name'] != '' ? ' - ' : '') . ($output['name'] != '' ? $output['name'] : '1/6 ' . $case_text) . ' (' . bcdiv($input['case_units'], 6, 0) . 'x' . $input['name'] . ')';
            } else {
                $output['io_name'] .= ($output['io_name'] != '' ? ' - ' : '') . $input['name'];
            }
        } else {
            if( $output['otype'] == 50 ) {
                $output['io_name'] .= ($output['io_name'] != '' ? ' - ' : '') . ($output['name'] != '' ? $output['name'] : $case_text) 
                    . ($output['name'] == '' ? ' (' . $input['case_units'] . ')' : '');
            } elseif( $output['otype'] == 52 ) {
                $output['io_name'] .= ($output['io_name'] != '' ? ' - ' : '') . ($output['name'] != '' ? $output['name'] : '1/2 ' . $case_text) 
                    . ($output['name'] == '' ? ' (' . bcdiv($input['case_units'], 2, 0) . ')' : '');
            } elseif( $output['otype'] == 53 ) {
                $output['io_name'] .= ($output['io_name'] != '' ? ' - ' : '') . ($output['name'] != '' ? $output['name'] : '1/3 ' . $case_text) 
                    . ($output['name'] == '' ? ' (' . bcdiv($input['case_units'], 3, 0) . ')' : '');
            } elseif( $output['otype'] == 54 ) {
                $output['io_name'] .= ($output['io_name'] != '' ? ' - ' : '') . ($output['name'] != '' ? $output['name'] : '1/4 ' . $case_text) 
                    . ($output['name'] == '' ? ' (' . bcdiv($input['case_units'], 4, 0) . ')' : '');
            } elseif( $output['otype'] == 55 ) {
                $output['io_name'] .= ($output['io_name'] != '' ? ' - ' : '') . ($output['name'] != '' ? $output['name'] : '1/5 ' . $case_text) 
                    . ($output['name'] == '' ? ' (' . bcdiv($input['case_units'], 5, 0) . ')' : '');
            } elseif( $output['otype'] == 56 ) {
                $output['io_name'] .= ($output['io_name'] != '' ? ' - ' : '') . ($output['name'] != '' ? $output['name'] : '1/6 ' . $case_text) 
                    . ($output['name'] == '' ? ' (' . bcdiv($input['case_units'], 6, 0) . ')' : '');
            } 
        }
        if( $output['io_name'] == '' ) {
            $output['pio_name'] = $product['name'] . ($legend_codes != '' ? ' ' . $legend_codes : '');
        } else {
            $output['pio_name'] = $product['name']  . ($legend_codes != '' ? ' ' . $legend_codes : '') . ' - ' . $output['io_name'];
        }

        //
        // Create the keywords string for fast searching
        //
        $output['keywords'] = ciniki_core_makeKeywords($ciniki, $output['pio_name']);

        //
        // Calculate for supplied products
        //
        $price = 0;
        $unitstext = '';
        if( ($output['otype'] == 10 || $output['otype'] == 20 || $output['otype'] == '71' ) && isset($input['unit_cost']) && isset($input['units']) ) {
            $rc = ciniki_foodmarket_convertWeightPrice($ciniki, $tnid, $input['unit_cost'], ($input['units']&0xff), ($output['units']&0xff));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $auc = $rc['price'];
            $price = bcmul($auc, bcadd(1, $output['retail_percent'], 6), 6);
            $unitstext = ciniki_foodmarket_unitsText($ciniki, $tnid, ($output['units']&0xff));
        } 
        elseif( ($output['otype'] == 30 || $output['otype'] == 72 ) && isset($input['unit_cost']) && isset($input['units']) ) {
            $price = bcmul($input['unit_cost'], bcadd(1, $output['retail_percent'], 6), 6);
            $unitstext = ciniki_foodmarket_unitsText($ciniki, $tnid, ($output['units']&0xff00));
        } 
        elseif( $output['otype'] == 50 && isset($input['case_cost']) ) {
            $price = bcmul($input['case_cost'], bcadd(1, $output['retail_percent'], 6), 6);
//            $output['retail_price_text'] = '$' . number_format($output['retail_price'], 2, '.', ','); //  . '/' . $case_text;
        }
        elseif( $output['otype'] == 52 && isset($input['case_cost']) ) {
            $price = bcmul(bcdiv($input['case_cost'], 2, 6), bcadd(1, $output['retail_percent'], 6), 6);
//            $output['retail_price_text'] = '$' . number_format($output['retail_price'], 2, '.', ','); // . ' per 1/2 ' . $case_text;
        }
        elseif( $output['otype'] == 53 && isset($input['case_cost']) ) {
            $price = bcmul(bcdiv($input['case_cost'], 3, 6), bcadd(1, $output['retail_percent'], 6), 6);
//            $output['retail_price_text'] = '$' . number_format($output['retail_price'], 2, '.', ','); // . ' per 1/3 ' . $case_text;
        }
        elseif( $output['otype'] == 54 && isset($input['case_cost']) ) {
            $price = bcmul(bcdiv($input['case_cost'], 4, 6), bcadd(1, $output['retail_percent'], 6), 6);
//            $output['retail_price_text'] = '$' . number_format($output['retail_price'], 2, '.', ','); // . ' per 1/4 ' . $case_text;
        }
        elseif( $output['otype'] == 55 && isset($input['case_cost']) ) {
            $price = bcmul(bcdiv($input['case_cost'], 5, 6), bcadd(1, $output['retail_percent'], 6), 6);
//            $output['retail_price_text'] = '$' . number_format($output['retail_price'], 2, '.', ','); // . ' per 1/5 ' . $case_text;
        }
        elseif( $output['otype'] == 56 && isset($input['case_cost']) ) {
            $price = bcmul(bcdiv($input['case_cost'], 6, 6), bcadd(1, $output['retail_percent'], 6), 6);
//            $output['retail_price_text'] = '$' . number_format($output['retail_price'], 2, '.', ','); // . ' per 1/6 ' . $case_text;
        }
        elseif( $output['otype'] == 70 ) {
            $price = $output['retail_price'];
//            $output['retail_price_text'] = '$' . number_format($output['retail_price'], 2, '.', ',');
        }

        //
        // Check for a discount and setup members pricing
        //
        $output['retail_price'] = $price;
        $output['retail_mprice'] = $price;
        if( $output['retail_mdiscount_percent'] > 0 ) {
            $output['retail_mprice'] = bcsub($output['retail_price'], round(bcmul($output['retail_price'], $output['retail_mdiscount_percent'], 6), 2), 6);
        }
        if( $output['retail_sdiscount_percent'] > 0 ) {
            $output['retail_price_text'] = '$' . number_format($price, 2, '.', ',');
            $discount = bcmul($price, $output['retail_sdiscount_percent'], 6);
            $output['retail_sprice'] = bcsub($price, $discount, 6);
            $output['retail_sprice_text'] = '$' . number_format($output['retail_sprice'], 2) . $unitstext;
            //
            // If a mdiscount_percent specified, add that discount to sale discount percent, 
            // recalculate the member price
            //
            if( $output['retail_mdiscount_percent'] > 0 ) {
                error_log($output['retail_mdiscount_percent']);
                error_log($output['retail_sdiscount_percent']);
                $discount = bcmul($output['retail_price'], bcadd($output['retail_sdiscount_percent'], $output['retail_mdiscount_percent'], 2), 6);
                error_log($discount);
                $output['retail_mprice'] = bcsub($output['retail_price'], $discount, 6);
            }
        } else {
            $output['retail_price'] = $price;
            $output['retail_price_text'] = '$' . number_format($price, 2, '.', ',') . $unitstext;
            $output['retail_sprice'] = 0;
            $output['retail_sprice_text'] = '';
        }
        $output['retail_mprice_text'] = '$' . number_format($output['retail_mprice'], 2) . $unitstext;

        //
        // Check for changed fields and build array of fields to update, and update the $outputs array
        //
        $update_args = array();
        foreach(['io_sequence', 'pio_name', 'io_name', 'keywords', 'wholesale_price', 'retail_price', 'retail_price_text', 'retail_sprice', 'retail_sprice_text', 'retail_mprice', 'retail_mprice_text'] as $field) {
            if( isset($output[$field]) && $output[$field] != $outputs[$output_id][$field] ) {
                $update_args[$field] = $output[$field];
                $outputs[$output_id][$field] = $output[$field];
            }
        }
        if( count($update_args) > 0 ) {
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.foodmarket.output', $output_id, $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
        if( isset($update_args['pio_name']) ) {
            //
            // FIXME: Check if product is in poma and update descriptions
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'poma', 'hooks', 'updateDescriptions');
            $rc = ciniki_poma_hooks_updateDescriptions($ciniki, $tnid, array(
                'object'=>'ciniki.foodmarket.output', 
                'object_id'=>$output_id,
                'description'=>$update_args['pio_name'],
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    //
    // Check if product needs to be updated
    //
    $update_args = array();
    if( $legend_codes != $product['legend_codes'] ) {
        $update_args['legend_codes'] = $legend_codes;
    }
    if( $legend_names != $product['legend_names'] ) {
        $update_args['legend_names'] = $legend_names;
    }
    if( count($update_args) > 0 ) {
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.foodmarket.product', $product_id, $update_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    return array('stat'=>'ok');
}
?>
