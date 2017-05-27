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
function ciniki_foodmarket_web_prepareOutputs($ciniki, $settings, $business_id, $args) {

    //
    // Load the list of items for a date
    //
    $date_items = array();
    if( isset($ciniki['session']['ciniki.poma']['date']['id']) && $ciniki['session']['ciniki.poma']['date']['id'] > 0 ) {
        $strsql = "SELECT output_id "
            . "FROM ciniki_foodmarket_date_items "
            . "WHERE date_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['ciniki.poma']['date']['id']) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.foodmarket', 'outputs', 'output_id');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['outputs']) ) {
            $date_items = $rc['outputs'];
        } 
    }

    if( !isset($ciniki['session']['customer']['id']) || $ciniki['session']['customer']['id'] < 1 ) {
        //
        // Remove unavailable items
        //
        foreach($args['outputs'] as $oid => $o) {
            if( isset($o['ctype']) && $o['ctype'] == '90' && ($o['flags']&0x0200) > 0 && !in_array($o['id'], $date_items) ) {
                unset($args['outputs'][$oid]);
            }
        }

        return array('stat'=>'ok', 'outputs'=>$args['outputs']);
    }

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
    // Get the object_ids
    //
    $output_ids = array();
    foreach($args['outputs'] as $output) {
        $output_ids[] = $output['id'];
    }

    //
    // Load the items from the current order
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'poma', 'web', 'orderItemsByObjectID');
    $rc = ciniki_poma_web_orderItemsByObjectID($ciniki, $business_id, array(
        'customer_id'=>$ciniki['session']['customer']['id'],
        'object'=>'ciniki.foodmarket.output',
        'object_ids'=>$output_ids,
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['items']) ) {
        $order_items = $rc['items'];
    } else {
        $order_items = array();
    }

    //
    // Load the items the customer has favourited, repeat order, or queued
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'poma', 'hooks', 'customerItemsByType');
    $rc = ciniki_poma_hooks_customerItemsByType($ciniki, $business_id, array(
        'customer_id'=>$ciniki['session']['customer']['id'],
        'object'=>'ciniki.foodmarket.output',
        'object_ids'=>$output_ids,
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['types']) ) {
        $item_types = $rc['types'];
    } else {
        $item_types = array();
    }

    $outputs = array();
    foreach($args['outputs'] as $oid => $output) {
        //
        // Check for sale pricing
        //
        if( isset($output['sale_price']) && $output['sale_price'] > 0 ) {
            $output['price_text'] = '$' . number_format($output['price'], 2);
        }

        //
        // Check if repeating order is available for this output
        //
        if( ($output['flags']&0x0100) == 0x0100 ) {
            $output['repeat'] = 'yes';
            $output['available'] = 'yes';
        } else {
            $output['repeat'] = 'no';
            $output['available'] = 'no';
        }

        //
        // Check if date specific
        //
        if( ($output['flags']&0x0200) == 0x0200 ) {
            $output['available'] = 'no';
            $output['repeat'] = 'no';
            if( in_array($output['id'], $date_items) ) {
                $output['available'] = 'yes';
            }

/*            ** This code may be useful when implementing date range availablity
            if( $output['start_date'] != '' && $output['start_date'] != '0000-00-00' 
                && $output['end_date'] != '' && $output['end_date'] != '0000-00-00' 
                && isset($ciniki['session']['ciniki.poma']['date']['order_dt'])
                ) {
                $sdt = new DateTime($output['start_date'] + ' 00:00:00', new DateTimezone($intl_timezone));
                $edt = new DateTime($output['end_date'] + ' 23:59:59', new DateTimezone($intl_timezone));
                //
                // Check if start date is before current order date and end 
                //
                if( $sdt < $ciniki['session']['ciniki.poma']['date']['order_dt'] && $edt > $ciniki['session']['ciniki.poma']['date']['order_dt'] ) {
                    $output['available'] = 'yes';
                }
            } */
        
            //
            // Check if category type is Available products, and reject unavailable ones
            //
            if( isset($output['ctype']) && $output['ctype'] == '90' && $output['available'] == 'no' ) {
                continue;
            }
        }

        //
        // Check if queue is available for this output
        //
        if( ($output['flags']&0x0400) == 0x0400 ) {
            $output['queue'] = 'yes';
            if( isset($item_types['queueactive']['items'][$output['id']]) ) {
                $output['queue_quantity'] = $item_types['queueactive']['items'][$output['id']]['quantity'];
            } else {
                $output['queue_quantity'] = 0;
            }
            if( isset($item_types['queueordered']['items'][$output['id']]) ) {
                $output['queue_ordered_quantity'] = $item_types['queueordered']['items'][$output['id']]['quantity'];
            } else {
                $output['queue_ordered_quantity'] = 0;
            }
        } else {
            $output['queue'] = 'no';
        }
//$output['queue'] = 'no';

        //
        // Check if limited
        //
        if( ($output['flags']&0x0800) == 0x0800 ) {
            $output['repeat'] = 'no';
            if( isset($output['inventory']) && $output['inventory'] > 0 ) {
                $output['available'] = 'yes';
                $output['quantity_limit'] = $output['inventory'];
            }
        }

        //
        // Check if available and if already ordered
        //
        if( $output['available'] == 'yes' && isset($order_items[$output['id']]['quantity']) ) {
            $output['order_quantity'] = (float)$order_items[$output['id']]['quantity'];
        } else {
            $output['order_quantity'] = 0;
        }

        //
        // Check if already in list of repeating items
        //
        if( $output['repeat'] == 'yes' && isset($item_types['repeat']['items'][$output['id']]['repeat_text']) ) {
            $output['repeat_value'] = 'on';
            $output['repeat_text'] = $item_types['repeat']['items'][$output['id']]['repeat_text'];
            $output['repeat_quantity'] = (float)$item_types['repeat']['items'][$output['id']]['quantity'];
            $output['repeat_days'] = $item_types['repeat']['items'][$output['id']]['repeat_days'];
            $output['repeat_next_date'] = $item_types['repeat']['items'][$output['id']]['next_order_date'];
        } else {
            $output['repeat_value'] = 'off';
            $output['repeat_quantity'] = 0;
            $output['repeat_days'] = 7;
            $output['repeat_next_date'] = '';
        }

        //
        // Always available as a favourite
        //
        $output['favourite'] = 'yes';
        if( isset($item_types['favourite']['items'][$output['id']]) ) {
            $output['favourite_value'] = 'on';
        } else {
            $output['favourite_value'] = 'off';
        }

        $outputs[] = $output;
    }

    return array('stat'=>'ok', 'outputs'=>$outputs);
}
?>
