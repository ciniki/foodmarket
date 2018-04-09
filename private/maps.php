<?php
//
// Description
// -----------
// This function returns the int to text mappings for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_foodmarket_maps(&$ciniki) {
    //
    // Build the maps object
    //
    $maps = array();
    $maps['seasonproduct'] = array(
        'repeat_days'=>array(
            '7'=>'Weekly',
            '14'=>'Bi-Weekly',
        ),
    );
    $maps['product'] = array(
        'status'=>array(
            '10'=>'Active',
            '40'=>'Public',
            '90'=>'Archived',
        ),
        'ptype'=>array(
            '10'=>'Produce Basket',
            '40'=>'Supplied',
            '70'=>'Manufactured',
            '100'=>'Grown',
            '130'=>'Raised',
        ),
        'flags'=>array(
            '0x01'=>'Visible',
        ),
    );
    $maps['input'] = array(
        'status'=>array(
            '10'=>'Active',
            '90'=>'Archived',
        ),
        'itype'=>array(
            '10'=>'Weight',
            '20'=>'Weighted Unit',
            '30'=>'Unit',
            '50'=>'Case',
            '52'=>'1/2 Case',
            '70'=>'Produce Basket',
            '90'=>'Animal',
        ),
        'units'=>array(
            '0x02'=>'lb',
            '0x04'=>'oz',
            '0x20'=>'kg',
            '0x40'=>'g',
            '0x0100'=>'each',
            '0x0200'=>'pair',
            '0x0400'=>'bunch',
            '0x0800'=>'bag',
            '0x010000'=>'case',
            '0x020000'=>'bushel',
        ),
        'flags'=>array(
            '0x01'=>'Visible',
            '0x10'=>'Inventory by Weight',
            '0x20'=>'Inventory by Units',
        ),
    );
    $maps['output'] = array(
        'status'=>array(
            '5'=>'Inactive',
            '10'=>'Private',
            '40'=>'Public',
            '90'=>'Archived',
        ),
        'otype'=>array(
            '10'=>'Weight',
            '20'=>'Weighted Unit',
            '30'=>'Unit',
            '50'=>'Case',
            '52'=>'1/2 Case',
            '53'=>'1/3 Case',
            '54'=>'1/4 Case',
            '70'=>'Produce Basket',
        ),
        'units'=>array(
            '0x02'=>'lb',
            '0x04'=>'oz',
            '0x20'=>'kg',
            '0x40'=>'g',
            '0x0100'=>'each',
            '0x0200'=>'pair',
            '0x0400'=>'bunch',
            '0x0800'=>'bag',
        ),
        'flags'=>array(
            '0x10'=>'Specials',
            '0x0100'=>'Always Available',
            '0x0200'=>'Date Specific',
            '0x0400'=>'Queued Item',
            '0x0800'=>'Limited Quantity',
        ),
    );
    //
    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
