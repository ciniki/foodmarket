<?php
//
// Description
// ===========
// This function will convert a product output to a poma order item.
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
function ciniki_foodmarket_convertOutputItem($ciniki, $business_id, $output) {

    $item = array(
        'object'=>'ciniki.foodmarket.output',
        'object_id'=>$output['id'],
        'description'=>$output['pio_name'],
        'name'=>isset($output['product_name']) ? $output['product_name'] : '',
        'size'=>isset($output['io_name']) ? $output['io_name'] : (isset($output['input_name']) ? $output['input_name'] : ''),
        'flags'=>0,
        'itype'=>(isset($output['otype']) ? $output['otype'] : 30),
        'units'=>(isset($output['units']) ? $output['units'] : 0),
        'unit_amount'=>$output['retail_price'],
        'taxtype_id'=>(isset($output['retail_taxtype_id']) ? $output['retail_taxtype_id'] : 0),
        'packing_order'=>(isset($output['packing_order']) ? $output['packing_order'] : 10),
        );

    if( isset($output['retail_sdiscount_percent']) && $output['retail_sdiscount_percent'] > 0 ) {
        $item['unit_discount_percentage'] = bcmul($output['retail_sdiscount_percent'], 100, 2);
    }

    //
    // Adjust output type to be inline with poma order item types
    //
    if( $item['itype'] == 71 ) {
        $item['itype'] = 10;
    } elseif( $item['itype'] > 30 ) {
        $item['itype'] = 30;
    }

    //
    // Check if container deposit on item
    //
    if( isset($output['input_flags']) && ($output['input_flags']&0x01) == 0x01 && isset($output['cdeposit_amount']) && $output['cdeposit_amount'] > 0 ) {
        $item['flags'] |= 0x80;
        $item['cdeposit_description'] = $output['cdeposit_name'];
        $item['cdeposit_amount'] = $output['cdeposit_amount'];
    }

    //
    // Check if deposit required for queued item
    //
    if( isset($output['flags']) && ($output['flags']&0x0400) && isset($output['retail_deposit']) && $output['retail_deposit'] > 0 ) {
        $item['qdeposit_amount'] = $output['retail_deposit'];
    }

    //
    // Set the substitution flag
    //
    if( $output['otype'] == 70 ) {
        $item['flags'] |= 0x02;
        //
        // Check if the product charges a modification fee
        //
        if( isset($output['product_flags']) && ($output['product_flags']&0x02) ) {
            $item['flags'] |= 0x0100;
        }
    }

    //
    // Check if price should be visible on orders/invoices. This is used for CSA/prepaid baskets currently.
    //
    if( isset($output['product_flags']) && ($output['product_flags']&0x04) == 0x04 ) {
        error_log('prepaid');
        $item['flags'] |= 0x0200;
    }

    //
    // Setup weight units if applicable
    //
    if( $item['itype'] < 30 ) {
        if( ($item['units']&0x02) == 0x02 ) {
            $item['weight_units'] = 20;
        } elseif( ($item['units']&0x04) == 0x04 ) {
            $item['weight_units'] = 25;
        } elseif( ($item['units']&0x20) == 0x20 ) {
            $item['weight_units'] = 60;
        } elseif( ($item['units']&0x40) == 0x40 ) {
            $item['weight_units'] = 65;
        } else {
            $item['weight_units'] = 0;
        }
    } else {
        if( ($item['units']&0x0100) == 0x0100 ) {
            $item['unit_suffix'] = 'each';
        } elseif( ($item['units']&0x0200) == 0x0200 ) {
            $item['unit_suffix'] = 'pair';
        } elseif( ($item['units']&0x0400) == 0x0400 ) {
            $item['unit_suffix'] = 'bunch';
        } elseif( ($item['units']&0x0800) == 0x0800 ) {
            $item['unit_suffix'] = 'bag';
        }
    }

    return array('stat'=>'ok', 'item'=>$item);
}
?>
