<?php
//
// Description
// ===========
// This function will be a callback when an item is added to ciniki.sapos.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_foodmarket_poma_itemLookup($ciniki, $business_id, $args) {

    if( !isset($args['object']) || $args['object'] == '' || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.41', 'msg'=>'No product specified.'));
    }

    //
    // An event was added to an invoice item, get the details and see if we need to 
    // create a registration for this event
    //
    if( $args['object'] == 'ciniki.foodmarket.output' ) {
        $strsql = "SELECT "
            . "ciniki_foodmarket_product_outputs.id, "
            . "ciniki_foodmarket_product_outputs.pio_name AS description, "
            . "ciniki_foodmarket_product_outputs.otype AS itype, "      // Item type
            . "ciniki_foodmarket_product_outputs.units, "
            . "ciniki_foodmarket_product_outputs.retail_price AS unit_amount, "
            . "ciniki_foodmarket_product_outputs.retail_taxtype_id AS taxtype_id, "
            . "IFNULL(ciniki_foodmarket_product_inputs.inventory, 0) AS inventory "
            . "FROM ciniki_foodmarket_product_outputs "
            . "LEFT JOIN ciniki_foodmarket_product_inputs ON ("
                . "ciniki_foodmarket_product_outputs.input_id = ciniki_foodmarket_product_inputs.id "
                . "AND ciniki_foodmarket_product_inputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_products ON ("
                . "ciniki_foodmarket_product_outputs.product_id = ciniki_foodmarket_products.id "
                . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . ") "
            . "WHERE ciniki_foodmarket_product_outputs.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'output');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['output']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.42', 'msg'=>'Unable to find item'));
        }
        $item = $rc['output'];

        //
        // Adjust output type to be inline with poma order item types
        //
        if( $item['itype'] > 30 ) {
            $item['itype'] = 30;
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
        $item['flags'] = 0;
        $item['object'] = 'ciniki.foodmarket.output';
        $item['object_id'] = $item['id'];

        return array('stat'=>'ok', 'item'=>$item);
    }

    return array('stat'=>'ok');
}
?>
