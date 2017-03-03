<?php
//
// Description
// -----------
// This function will check for products in each legend/sublegend and set the visible flag.
//
// Arguments
// ---------
//
function ciniki_foodmarket_legendsUpdate(&$ciniki, $business_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    //
    // Get the list of legends
    //
    $strsql = "SELECT id, flags, 'no' AS visible "
        . "FROM ciniki_foodmarket_legends "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'legends', 'fname'=>'id', 'fields'=>array('id', 'flags', 'visible')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['legends']) ) {
        $legends = $rc['legends'];
    } else {
        $legends = array();
    }

    //
    // Get the list of legends and the number of products in each
    //
    $strsql = "SELECT ciniki_foodmarket_legend_items.legend_id, "
        . "COUNT(ciniki_foodmarket_legend_items.product_id) AS num_products "
        . "FROM ciniki_foodmarket_legend_items, ciniki_foodmarket_products, ciniki_foodmarket_product_outputs "
        . "WHERE ciniki_foodmarket_legend_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_foodmarket_legend_items.product_id = ciniki_foodmarket_products.id "
        . "AND ciniki_foodmarket_products.status = 40 "
        . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_foodmarket_products.id = ciniki_foodmarket_product_outputs.product_id "
        . "AND ciniki_foodmarket_product_outputs.status = 40 "
        . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "GROUP BY ciniki_foodmarket_legend_items.legend_id "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'legends', 'fname'=>'legend_id', 'fields'=>array('legend_id', 'num_products')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['legends']) ) {
        foreach($rc['legends'] as $legend) {
            if( isset($legends[$legend['legend_id']]) ) {
                if( $legend['num_products'] > 0 ) {
                    $legends[$legend['legend_id']]['visible'] = 'yes';
                }
            }
        }
    }

    foreach($legends as $legend) {
        //
        // If legend should be visible and isn't then set visible flag
        //
        $update_args = array();
        if( $legend['visible'] == 'yes' && ($legend['flags']&0x01) == 0 ) {
            $update_args['flags'] = $legend['flags'] | 0x01;
        } 
        //
        // If legend is not visible and flags is set to visible, remove
        //
        elseif( $legend['visible'] == 'no' && ($legend['flags']&0x01) == 0x01 ) {
            $update_args['flags'] = $legend['flags'] | 0x01;
            $update_args['flags'] = ((int)$legend['flags']) &~ 0x01;
        }

        //
        // Update the legend
        //
        if( count($update_args) > 0 ) {
            $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.foodmarket.legend', $legend['id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>
