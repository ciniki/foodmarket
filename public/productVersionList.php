<?php
//
// Description
// -----------
// This method will return the list of Product Versions for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Product Version for.
//
// Returns
// -------
//
function ciniki_foodmarket_productVersionList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.productVersionList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of versions
    //
    $strsql = "SELECT ciniki_foodmarket_product_versions.id, "
        . "ciniki_foodmarket_product_versions.product_id, "
        . "ciniki_foodmarket_product_versions.name, "
        . "ciniki_foodmarket_product_versions.permalink, "
        . "ciniki_foodmarket_product_versions.status, "
        . "ciniki_foodmarket_product_versions.flags, "
        . "ciniki_foodmarket_product_versions.sequence, "
        . "ciniki_foodmarket_product_versions.recipe_id, "
        . "ciniki_foodmarket_product_versions.recipe_quantity, "
        . "ciniki_foodmarket_product_versions.container_id, "
        . "ciniki_foodmarket_product_versions.materials_cost_per_container, "
        . "ciniki_foodmarket_product_versions.time_cost_per_container, "
        . "ciniki_foodmarket_product_versions.total_cost_per_container, "
        . "ciniki_foodmarket_product_versions.total_time_per_container, "
        . "ciniki_foodmarket_product_versions.inventory, "
        . "ciniki_foodmarket_product_versions.supplier_price, "
        . "ciniki_foodmarket_product_versions.wholesale_price, "
        . "ciniki_foodmarket_product_versions.basket_price, "
        . "ciniki_foodmarket_product_versions.retail_price "
        . "FROM ciniki_foodmarket_product_versions "
        . "WHERE ciniki_foodmarket_product_versions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'versions', 'fname'=>'id', 
            'fields'=>array('id', 'product_id', 'name', 'permalink', 'status', 'flags', 'sequence', 'recipe_id', 'recipe_quantity', 'container_id', 'materials_cost_per_container', 'time_cost_per_container', 'total_cost_per_container', 'total_time_per_container', 'inventory', 'supplier_price', 'wholesale_price', 'basket_price', 'retail_price')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['versions']) ) {
        $versions = $rc['versions'];
    } else {
        $versions = array();
    }

    return array('stat'=>'ok', 'versions'=>$versions);
}
?>
