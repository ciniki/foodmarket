<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business to get foodmarket for.
//
// Returns
// -------
//
function ciniki_foodmarket_hooks_uiSettings($ciniki, $business_id, $args) {

    //
    // Setup the default response
    //
    $rsp = array('stat'=>'ok', 'menu_items'=>array());

    //
    // Check permissions for what menu items should be available
    //
    if( isset($ciniki['business']['modules']['ciniki.foodmarket'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>6500,
            'label'=>'Products', 
            'edit'=>array('app'=>'ciniki.foodmarket.main', 'args'=>array('menu'=>'"\'products\'"')),
            );
        $rsp['menu_items'][] = $menu_item;
    } 

    //
    // Add the menu option for suppliers
    // 
/*    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.foodmarket', 0x0100)
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>6500,
            'label'=>'Suppliers', 
            'edit'=>array('app'=>'ciniki.foodmarket.main', 'args'=>array()),
            );
        $rsp['menu_items'][] = $menu_item;
    }  */

    return $rsp;
}
?>
