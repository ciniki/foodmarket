<?php
//
// Description
// ===========
// This functions converts the units bit field into text string.
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
function ciniki_foodmarket_unitsText($ciniki, $tnid, $units, $sep='/') {
    
    switch($units) {
        case 0x02: return $sep . 'lb';
        case 0x04: return $sep . 'oz';
        case 0x20: return $sep . 'kg';
        case 0x40: return $sep . 'g';
        case 0x0100: return ($sep == '/' ? ' ':$sep) . 'each';
        case 0x0200: return $sep . 'pair';
        case 0x0400: return $sep . 'bunch';
        case 0x0800: return $sep . 'bag';
        case 0x010000: return $sep . 'case';
        case 0x020000: return $sep . 'bushel';
    }

    return '';
}
?>
