<?php
//
// Description
// -----------
// This function will prepare the supplier input/outputs into an order for the supplier.
//
// Arguments
// ---------
//
function ciniki_foodmarket_prepareSuppliedOrderInputs($ciniki, $tnid, $inputs) {

    $rsp = array('stat'=>'ok', 'inputs'=>array());

    foreach($inputs as $input) {
        $input['cost_suffix'] = '';
        $input['requested_quantity'] = 0;
        $input['order_quantity'] = 0;
        if( $input['input_name'] != '' ) {
            $input['name'] .= ' - ' . $input['input_name'];
        }
        $input['weight_quantity'] = 0;
        $input['unit_quantity'] = 0;
        $input['case_quantity'] = 0;
        foreach($input['outputs'] as $output) {
            if( $output['otype'] == 10 || $output['otype'] == 71 ) {
                $input['weight_quantity'] = bcadd($input['weight_quantity'], $output['weight_quantity'], 2);
            } elseif( $output['otype'] == 20 || $output['otype'] == 30 || $output['otype'] == 72 ) {
                $input['unit_quantity'] = bcadd($input['unit_quantity'], $output['unit_quantity'], 2);
            } elseif( $output['otype'] == 50 ) {
                $input['unit_quantity'] = bcadd($input['unit_quantity'], bcmul($output['unit_quantity'], $input['case_units'], 2), 2);
            } elseif( $output['otype'] == 52 ) {
                $input['unit_quantity'] = bcadd($input['unit_quantity'], bcmul($output['unit_quantity'], bcdiv($input['case_units'], 2, 2), 2), 2);
            } elseif( $output['otype'] == 53 ) {
                $input['unit_quantity'] = bcadd($input['unit_quantity'], bcmul($output['unit_quantity'], bcdiv($input['case_units'], 3, 2), 2), 2);
            } elseif( $output['otype'] == 54 ) {
                $input['unit_quantity'] = bcadd($input['unit_quantity'], bcmul($output['unit_quantity'], bcdiv($input['case_units'], 4, 2), 2), 2);
            } elseif( $output['otype'] == 55 ) {
                $input['unit_quantity'] = bcadd($input['unit_quantity'], bcmul($output['unit_quantity'], bcdiv($input['case_units'], 5, 2), 2), 2);
            } elseif( $output['otype'] == 56 ) {
                $input['unit_quantity'] = bcadd($input['unit_quantity'], bcmul($output['unit_quantity'], bcdiv($input['case_units'], 6, 2), 2), 2);
            } elseif( $output['otype'] == 58 ) {
                $input['unit_quantity'] = bcadd($input['unit_quantity'], bcmul($output['unit_quantity'], bcdiv($input['case_units'], 8, 2), 2), 2);
            } elseif( $output['otype'] == 59 ) {
                $input['unit_quantity'] = bcadd($input['unit_quantity'], bcmul($output['unit_quantity'], bcdiv($input['case_units'], 9, 2), 2), 2);
            } elseif( $output['otype'] == 60 ) {
                $input['unit_quantity'] = bcadd($input['unit_quantity'], bcmul($output['unit_quantity'], bcdiv($input['case_units'], 10, 2), 2), 2);
            }
        }
        //
        // Skip items with no quantity
        //
        if( $input['weight_quantity'] == 0 && $input['unit_quantity'] == 0 ) {
            continue;
        }
        //
        // Decide the quantity that should be ordered
        //
        if( $input['itype'] == 10 ) {
            $input['sizetext'] = 'Single';
            $input['required_quantity'] = (float)$input['weight_quantity'];
            if( $input['min_quantity'] <= 0 ) {
                $input['min_quantity'] = 1;
            }
            if( $input['required_quantity'] <= $input['min_quantity'] ) {
                $input['order_quantity'] = (float)$input['min_quantity'];
            } else {
                $extra_amount = bcsub($input['required_quantity'], $input['min_quantity'], 6);
                if( $input['inc_quantity'] == 0 ) {
                    $input['inc_quantity'] = $input['min_quantity'];
                }
                $multiples = ceil(bcdiv($extra_amount, $input['inc_quantity'], 6));
                $input['order_quantity'] = (float)bcadd($input['min_quantity'], bcmul($input['inc_quantity'], $multiples, 2), 2);
            }
            if( ($input['units']&0x02) == 0x02 ) {
                $stext = 'lb';
                $ptext = 'lbs';
            } elseif( ($input['units']&0x04) == 0x04 ) {
                $stext = 'oz';
                $ptext = 'ozs';
            } elseif( ($input['units']&0x20) == 0x20 ) {
                $stext = 'kg';
                $ptext = 'kgs';
            } elseif( ($input['units']&0x40) == 0x40 ) {
                $stext = 'g';
                $ptext = 'gs';
            }
            $input['required_quantity_text'] = $input['required_quantity'] . ($input['required_quantity'] > 1 ? ' ' . $ptext : ' ' . $stext);
            $input['order_quantity_text'] = $input['order_quantity'] . ($input['order_quantity'] > 1 ? ' ' . $ptext : ' ' . $stext);
            if( $input['min_quantity'] > 1 ) {
                $input['cost_text'] = '$' . number_format(bcmul($input['unit_cost'], $input['min_quantity'], 2), 2) . '/' . (float)$input['min_quantity'] . '' . $stext;
                $input['cost_suffix'] = '/' . (float)$input['min_quantity'] . $stext;
            } else {
                $input['cost_text'] = '$' . number_format($input['unit_cost'], 2) . '/' . $stext;
                $input['cost_suffix'] = '/' . $stext;
            }
        } elseif( $input['itype'] == 20 || $input['itype'] == 30 ) {
            $input['sizetext'] = 'Single';
            $stext = '';
            $ptext = '';
            if( ($input['units']&0x0200) == 0x0200 ) {
                $stext = ' pair';
                $ptext = ' pairs';
            } elseif( ($input['units']&0x0400) == 0x0400 ) {
                $stext = ' bunch';
                $ptext = ' bunches';
            } elseif( ($input['units']&0x0800) == 0x0800 ) {
                $stext = ' bag';
                $ptext = ' bags';
            }
            $input['required_quantity'] = (float)$input['unit_quantity'];
            $input['required_quantity_text'] = $input['required_quantity'] . ($input['required_quantity'] > 1 ? $ptext : $stext);
            $input['order_quantity'] = (float)$input['unit_quantity'];
            $input['order_quantity_text'] = $input['order_quantity'] . ($input['order_quantity'] > 1 ? $ptext : $stext);
            if( $input['itype'] == 20 ) {
                if( ($input['units']&0x02) == 0x02 ) {
                    $input['cost_text'] = '$' . number_format($input['unit_cost'], 2) . '/lb';
                    $input['cost_suffix'] = '/lb';
                } elseif( ($input['units']&0x04) == 0x04 ) {
                    $input['cost_text'] = '$' . number_format($input['unit_cost'], 2) . '/oz';
                    $input['cost_suffix'] = '/oz';
                } elseif( ($input['units']&0x20) == 0x20 ) {
                    $input['cost_text'] = '$' . number_format($input['unit_cost'], 2) . '/kg';
                    $input['cost_suffix'] = '/kg';
                } elseif( ($input['units']&0x40) == 0x40 ) {
                    $input['cost_text'] = '$' . number_format($input['unit_cost'], 2) . '/g';
                    $input['cost_suffix'] = '/g';
                }
            } else {
                if( ($input['units']&0x0200) == 0x0200 ) {
                    $input['cost_text'] = '$' . number_format($input['unit_cost'], 2) . '/pair';
                    $input['cost_suffix'] = '/pair';
                } elseif( ($input['units']&0x0400) == 0x0400 ) {
                    $input['cost_text'] = '$' . number_format($input['unit_cost'], 2) . '/bunch';
                    $input['cost_suffix'] = '/bunch';
                } elseif( ($input['units']&0x0800) == 0x0800 ) {
                    $input['cost_text'] = '$' . number_format($input['unit_cost'], 2) . '/bag';
                    $input['cost_suffix'] = '/bag';
                } else {
                    $input['cost_text'] = '$' . number_format($input['unit_cost'], 2) . '';
                    $input['cost_suffix'] = '';
                }
            }
        } elseif( $input['itype'] == 50 ) {
            $input['required_quantity'] = (float)bcdiv($input['unit_quantity'], $input['case_units'], 2);
            $input['sizetext'] = 'Case';
            $stext = 'case';
            $ptext = 'cases';
            if( ($input['units']&0x020000) == 0x020000 ) {
                $stext = 'bushel';
                $ptext = 'bushels';
            }
            if( $input['half_cost'] > 0 ) {
                $cases = bcdiv($input['unit_quantity'], $input['case_units'], 2);
                $full_cases = floor($cases);
                $partial_cases = $cases - $full_cases;
                if( $partial_cases > 0 && $partial_cases <= 0.5 ) {
                    $input['order_quantity'] = $full_cases + 0.5;
                } elseif( $partial_cases > 0 && $partial_cases > 0.5 ) {
                    $input['order_quantity'] = $full_cases + 1;
                } else {
                    $input['order_quantity'] = $full_cases;
                }
            } else {
                $input['order_quantity'] = ceil($input['required_quantity']);
            }
            $input['required_quantity_text'] = $input['required_quantity'] . ($input['order_quantity'] > 1 ? ' ' . $ptext : ' ' . $stext) 
                . ' (' . (float)$input['unit_quantity'] . ')';
            $input['order_quantity_text'] = $input['order_quantity'] . ($input['order_quantity'] > 1 ? ' ' . $ptext : ' ' . $stext);
            $input['cost_text'] = '$' . number_format($input['case_cost'], 2) . '/' . $stext;
            $input['cost_suffix'] = '/' . $stext;
        }
        $rsp['inputs'][] = $input;
    }

    return $rsp;
}
?>
