<?php
//
// Description
// -----------
// This method will return the list of Products for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Product for.
//
// Returns
// -------
//
function ciniki_foodmarket_productSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'search_str'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Search'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.productSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $args['search_str'] = preg_replace("/ /", '%', $args['search_str']);

    //
    // Get the list of products
    //
    $strsql = "SELECT ciniki_foodmarket_products.id, "
        . "ciniki_foodmarket_products.name, "
        . "ciniki_foodmarket_products.permalink, "
        . "ciniki_foodmarket_products.status, "
        . "ciniki_foodmarket_products.flags, "
        . "ciniki_foodmarket_products.supplier_id "
        . "FROM ciniki_foodmarket_products "
        . "WHERE ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ("
            . "name like '" . ciniki_core_dbQuote($ciniki, $args['search_str']) . "%' "
            . "OR name like '% " . ciniki_core_dbQuote($ciniki, $args['search_str']) . "%' "
            . ") "
        . "ORDER BY name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'products', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'status', 'flags', 'supplier_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['products']) ) {
        $products = $rc['products'];
    } else {
        $products = array();
    }

    return array('stat'=>'ok', 'products'=>$products);
}
?>
