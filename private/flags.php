<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_foodmarket_flags($ciniki, $modules) {
    $flags = array(
        // 0x01
        array('flag'=>array('bit'=>'1', 'name'=>'Seasons')),
        array('flag'=>array('bit'=>'2', 'name'=>'Inventory')),
//        array('flag'=>array('bit'=>'3', 'name'=>'')),
//        array('flag'=>array('bit'=>'4', 'name'=>'')),
        // 0x10
        array('flag'=>array('bit'=>'5', 'name'=>'Products')),
        array('flag'=>array('bit'=>'6', 'name'=>'Product Categories')),
        array('flag'=>array('bit'=>'7', 'name'=>'Member Prices')),
//        array('flag'=>array('bit'=>'8', 'name'=>'')),
        // 0x0100
        array('flag'=>array('bit'=>'9', 'name'=>'Suppliers')),
//        array('flag'=>array('bit'=>'10', 'name'=>'')),
//        array('flag'=>array('bit'=>'11', 'name'=>'')),
//        array('flag'=>array('bit'=>'12', 'name'=>'')),
        // 0x1000
        array('flag'=>array('bit'=>'13', 'name'=>'Produce Baskets')),
//        array('flag'=>array('bit'=>'14', 'name'=>'CSA Baskets')),
//        array('flag'=>array('bit'=>'15', 'name'=>'')),
//        array('flag'=>array('bit'=>'16', 'name'=>'')),
        );

    return array('stat'=>'ok', 'flags'=>$flags);
}
?>
