<?php
//
// Description
// ===========
// This function loads all the details for a product.
//
// Arguments
// ---------
// ciniki:
// tnid:         The ID of the tenant the product is attached to.
// product_id:          The ID of the product to get the details for.
//
// Returns
// -------
//
function ciniki_foodmarket_convertWeightPrice($ciniki, $tnid, $price, $from_units, $to_units) {
    
    $precision = 10;

    // $/lb -> $/oz
    if( $from_units == 0x02 && $to_units == 0x04 ) {
        $price = bcmul($price, 0.0625, $precision);
    }
    // $/lb -> $/kg
    elseif( $from_units == 0x02 && $to_units == 0x20 ) {
        $price = bcmul($price, 2.20462, $precision);
    }
    // $/lb -> $/g
    elseif( $from_units == 0x02 && $to_units == 0x40 ) {
        $price = bcmul($price, 0.00220462, $precision);
    }

    // $/oz -> $/lb
    elseif( $from_units == 0x04 && $to_units == 0x02 ) {
        $price = bcmul($price, 16, $precision);
    }
    // $/oz -> $/kg
    elseif( $from_units == 0x04 && $to_units == 0x20 ) {
        $price = bcmul($price, 35.274, $precision);
    }
    // $/oz -> $/g
    elseif( $from_units == 0x04 && $to_units == 0x40 ) {
        $price = bcmul($price, 0.035274, $precision);
    }

    // $/kg -> $/lb
    elseif( $from_units == 0x20 && $to_units == 0x02 ) {
        $price = bcmul($price, 0.453592, $precision);
    }
    // $/kg -> $/oz
    elseif( $from_units == 0x20 && $to_units == 0x04 ) {
        $price = bcmul($price, 0.0283495, $precision);
    }
    // $/kg -> $/g
    elseif( $from_units == 0x20 && $to_units == 0x40 ) {
        $price = bcmul($price, 0.001, $precision);
    }

    // $/g -> $/lb
    elseif( $from_units == 0x40 && $to_units == 0x02 ) {
        $price = bcmul($price, 453.592, $precision);
    }
    // $/g -> $/oz
    elseif( $from_units == 0x40 && $to_units == 0x04 ) {
        $price = bcmul($price, 28.3495, $precision);
    }
    // $/g -> $/kg
    elseif( $from_units == 0x40 && $to_units == 0x20 ) {
        $price = bcmul($price, 1000, $precision);
    }
    // error
    elseif( $from_units != $to_units ) {
        error_log("Unsupported price conversion: $from_units -> $to_units");
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.17', 'msg'=>'Unsupported price conversion.'));
    }

    return array('stat'=>'ok', 'price'=>$price);
}
?>
