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
// args:                Typically the $ciniki['request']['args'] variable should be passed here.
//
// Returns
// -------
//
function ciniki_foodmarket_productSuppliedUpdate(&$ciniki, $business_id, $product_id, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'productLoad');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'productUpdateFields');

    //
    // Load the product
    //
    $rc = ciniki_foodmarket_productLoad($ciniki, $business_id, $product_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $product = $rc['product'];

    //
    // Check if args where passed in to parse
    //
    if( $args == null ) {
        if( isset($ciniki['request']['args']) ) {
            $args = $ciniki['request']['args'];
        } else {
            return array('stat'=>'ok');
        }
    }

    //
    // Scan the arguments and build the inputs to parse
    //
    $input_idxs = array();
    $otypes = array();
    foreach($args as $arg_key => $arg_value) {
        if( preg_match("/^input([0-9]+)_([0-9]+)_/", $arg_key, $matches) ) {
            $input_idxs[$matches[1]] = array();
            $otypes[$matches[2]] = array();
        } elseif( preg_match("/^input([0-9]+)_/", $arg_key, $matches) ) {
            $input_idxs[$matches[1]] = array();
        }
    }

    //
    // Check for any inputs from the UI
    //
    $inputs = array();
    foreach($input_idxs as $idx => $input_index) {
        //
        // Find all the required and optional arguments
        //
        $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
            'input' . $idx . '_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' ID'),
            'input' . $idx . '_name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Name'),
            'input' . $idx . '_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Status'),
            'input' . $idx . '_itype'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Type'),
            'input' . $idx . '_units'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Units'),
            'input' . $idx . '_flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Options'),
            'input' . $idx . '_sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Sequence'),
            'input' . $idx . '_case_cost'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Input ' . $idx . ' Case Cost'),
            'input' . $idx . '_half_cost'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Input ' . $idx . ' Half Cost'),
            'input' . $idx . '_unit_cost'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Input ' . $idx . ' Unit Cost'),
            'input' . $idx . '_case_units'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Case Units'),
            'input' . $idx . '_min_quantity'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Minimum Quantity'),
            'input' . $idx . '_inc_quantity'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Incremental Quantity'),
            'input' . $idx . '_cdeposit_name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Container Deposit Name'),
            'input' . $idx . '_cdeposit_amount'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Container Deposit Amount'),
            'input' . $idx . '_sku'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Sku/Code'),
            'input' . $idx . '_inventory'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Inventory'),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Check if an input ID was passed
        //
        $input_id = 0;
        if( isset($rc['args']['input' . $idx . '_id']) ) {
            $input_id = $rc['args']['input' . $idx . '_id'];
        }
        //
        // Build the new input object
        //
        $new_input = array();
        foreach($rc['args'] as $arg_key => $arg_value) {
            $field = str_replace('input' . $idx . '_', '', $arg_key);
            $new_input[$field] = $arg_value;
        }
        $valid_otypes = array();

        //
        // If a new input, add the object
        //
        if( $input_id == 0 ) {
            if( !isset($new_input['itype']) ) {
                // No itype,then skip product
                continue;
            }
            $new_input['product_id'] = $product_id;
            //
            // Run calcs
            //
            if( $new_input['itype'] == 50 ) {
                //
                // Check that case_cost and case_units is specified
                //
                if( !isset($new_input['case_cost']) ) {
                    $args['case_cost'] = 0;
                }
                if( !isset($new_input['case_units']) || $new_input['case_units'] == 0 ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.22', 'msg'=>'You must specified the number of units in a case.'));
                }
                if( $new_input['case_cost'] != 0 && $new_input['case_cost'] != '' && $new_input['case_units'] != 0 && $new_input['case_units'] != '' ) {
                    // Round the number to 4 digits, same as database storage
                    $new_input['unit_cost'] = number_format(bcdiv($new_input['case_cost'], $new_input['case_units'], 6), 4);
                } else {
                    $new_input['unit_cost'] = 0;
                }
            } else {
                $new_input['case_cost'] = 0;
                $new_input['half_cost'] = 0;
            }

            //
            // Setup the required args
            //
            if( !isset($new_input['name']) ) {
                $new_input['name'] = '';
            }
            if( !isset($new_input['permalink']) ) {
                $new_input['permalink'] = ciniki_core_makePermalink($ciniki, $new_input['name']);
            }
            if( !isset($new_input['sequence']) ) {
                $new_input['sequence'] = $idx;
            }

            //
            // Add the input
            //
            $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.foodmarket.input', $new_input, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $input_id = $rc['id'];

            //
            // Add the input to the products inputs incase and output is attached
            //
            $product['inputs'][$input_id] = $new_input;
        }
        //
        // Update existing input
        //
        elseif( $input_id > 0 && count($new_input) > 0 ) {
            //
            // Get the existing input information
            //
            if( !isset($product['inputs'][$input_id]) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.23', 'msg'=>'Unable to update, existing input not found.'));
            }
            $old_input = $product['inputs'][$input_id];

            //
            // Check args that are changing for rules
            //
            if( isset($new_input['case_units']) && ($new_input['case_units'] == 0 || $new_input['case_units'] == '') ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.24', 'msg'=>'You must specified the number of units in a case.'));
            }

            //
            // Make sure new input object has required fields, if they are not changed they will not be updated either
            //
            foreach(array('itype', 'units', 'flags', 'case_cost', 'half_cost', 'unit_cost', 'case_units') as $field) {
                if( !isset($new_input[$field]) ) {
                    $new_input[$field] = $old_input[$field];
                }
            }

            //
            // Run calcs
            //
            if( $new_input['itype'] == 50 ) {
                if( $new_input['case_cost'] != 0 && $new_input['case_cost'] != '' && $new_input['case_units'] != 0 && $new_input['case_units'] != '' ) {
                    // Round the number to 4 digits, same as database storage
                    $new_input['unit_cost'] = number_format(bcdiv($new_input['case_cost'], $new_input['case_units'], 6), 4);
                } else {
                    $new_input['unit_cost'] = 0;
                }
            }

            //
            // Check for new name
            //
            if( isset($new_input['name']) ) {
                $new_input['permalink'] = ciniki_core_makePermalink($ciniki, $new_input['name']);
            }

            //
            // Build the list of fields that need updating, and update
            //
            $update_args = array();
            foreach($new_input as $arg_key => $arg_value) {
                if( !isset($old_input[$arg_key]) || $old_input[$arg_key] != $arg_value ) {
                    $update_args[$arg_key] = $arg_value;
                }
            }
            if( count($update_args) > 0 ) {
                $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.foodmarket.input', $input_id, $update_args, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
        $input_idxs[$idx] = $new_input;

        //
        // Check for any outputs for this input
        //
        foreach($otypes as $type => $type_details) {
            //
            // Parse the args
            //
            $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
                'input' . $idx . '_' . $type . '_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Output ID'),
                'input' . $idx . '_' . $type . '_name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Output Name'),
                'input' . $idx . '_' . $type . '_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Output Status'),
//                'input' . $idx . '_' . $type . '_itype'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Output Type'),
                'input' . $idx . '_' . $type . '_units'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Output Units'),
                'input' . $idx . '_' . $type . '_flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Output Options'),
                'input' . $idx . '_' . $type . '_sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Output Sequence'),
                'input' . $idx . '_' . $type . '_packing_order'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Packing Order'),
                'input' . $idx . '_' . $type . '_wholesale_percent'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Wholesale Percent'),
                'input' . $idx . '_' . $type . '_wholesale_price'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Input ' . $idx . ' Wholesale Price'),
                'input' . $idx . '_' . $type . '_wholesale_taxtype_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Wholesale Tax'),
                'input' . $idx . '_' . $type . '_retail_percent'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Retail Percent'),
                'input' . $idx . '_' . $type . '_retail_price'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Input ' . $idx . ' Retail Price'),
                'input' . $idx . '_' . $type . '_retail_taxtype_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input ' . $idx . ' Retail Tax'),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }

            //
            // Check if an output ID was passed
            //
            $output_id = 0;
            if( isset($rc['args']['input' . $idx . '_' . $type . '_id']) ) {
                $output_id = $rc['args']['input' . $idx . '_' . $type . '_id'];
            }
            //
            // Build the new output object
            //
            $new_output = array();
            foreach($rc['args'] as $arg_key => $arg_value) {
                $field = str_replace('input' . $idx . '_' . $type . '_', '', $arg_key);
                $new_output[$field] = $arg_value;
            }

            //
            // Add New output
            //
            if( $output_id == 0 && isset($new_output['status']) && $new_output['status'] > 5 ) {
                $new_output['product_id'] = $product_id;
                $new_output['input_id'] = $input_id;
                $new_output['otype'] = $type;

                //
                // Setup the required args
                //
                if( !isset($new_output['name']) ) {
                    $new_output['name'] = '';
                }
                if( !isset($new_output['permalink']) ) {
                    $new_output['permalink'] = ciniki_core_makePermalink($ciniki, $new_output['name']);
                }
                if( !isset($new_output['sequence']) ) {
                    $new_output['sequence'] = $type;
                }
                if( !isset($new_output['units']) ) {
                    $new_output['units'] = 0;
                }

                //
                // Add the output
                //
                $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.foodmarket.output', $new_output, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $output_id = $rc['id'];

                //
                // Add the output to the products outputs incase and output is attached
                //
                $product['outputs'][$output_id] = $new_output;
            } 

            //
            // Update existing output
            //
            elseif( $output_id > 0 && count($new_output) > 0 ) {
                //
                // Get the existing output information
                //
                if( !isset($product['outputs'][$output_id]) ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.31', 'msg'=>'Unable to update, existing output not found.'));
                }
                $old_output = $product['outputs'][$output_id];

                //
                // Make sure new output object has required fields, if they are not changed they will not be updated either
                //
                foreach(array('otype', 'units', 'flags') as $field) {
                    if( !isset($new_output[$field]) ) {
                        $new_output[$field] = $old_output[$field];
                    }
                }

                //
                // Check for new name
                //
                if( isset($new_output['name']) ) {
                    $new_output['permalink'] = ciniki_core_makePermalink($ciniki, $new_output['name']);
                }

                //
                // Build the list of fields that need updating, and update
                //
                $update_args = array();
                foreach($new_output as $arg_key => $arg_value) {
                    if( !isset($old_output[$arg_key]) || $old_output[$arg_key] != $arg_value ) {
                        $update_args[$arg_key] = $arg_value;
                    }
                }
                if( count($update_args) > 0 ) {
                    $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.foodmarket.output', $output_id, $update_args, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                }
            }
        }

        //
        // Build the list of valid_otypes 
        //
        $valid_otypes = array();
        if( $new_input['itype'] == 10 ) {
            $valid_otypes = array('10', '71');
        } elseif( $new_input['itype'] == 20 ) {
            $valid_otypes = array('20');
        } elseif( $new_input['itype'] == 30 ) {
            $valid_otypes = array('30', '72');
        } elseif( $new_input['itype'] == 50 ) {
            $valid_otypes = array('30', '72', '50');
            if( ($new_input['case_units']%2) == 0 ) {
                $valid_otypes[] = '52';
            } 
            if( ($new_input['case_units']%3) == 0 ) {
                $valid_otypes[] = '53';
            } 
            if( ($new_input['case_units']%4) == 0 ) {
                $valid_otypes[] = '54';
            } 
            if( ($new_input['case_units']%5) == 0 ) {
                $valid_otypes[] = '55';
            } 
            if( ($new_input['case_units']%6) == 0 ) {
                $valid_otypes[] = '56';
            }
        }

        //
        // Check if there are any outputs that should be removed
        //
        if( count($valid_otypes) > 0 ) {
            $strsql = "SELECT id, uuid, product_id, status "
                . "FROM ciniki_foodmarket_product_outputs "
                . "WHERE product_id = '" . ciniki_core_dbQuote($ciniki, $product_id) . "' "
                . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND status > 5 "
                . "AND otype NOT IN (" . ciniki_core_dbQuoteIDs($ciniki, $valid_otypes) . ") "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'output');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
                foreach($rc['rows'] as $row) {  
                    $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.foodmarket.output', $row['id'], array('status'=>5), 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                }
            }
        }
    }

    //
    // Update the pricing and output names
    //
    $rc = ciniki_foodmarket_productUpdateFields($ciniki, $business_id, $product_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
