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
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'maps');
    $rc = ciniki_foodmarket_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Get the list of products
    //
    $strsql = "SELECT products.id, "
        . "products.name, "
        . "products.permalink, "
        . "products.status, "
        . "products.status AS status_text, "
        . "products.flags, "
        . "products.supplier_id, "
        . "suppliers.code AS supplier_code "
        . "FROM ciniki_foodmarket_products AS products "
        . "LEFT JOIN ciniki_foodmarket_suppliers AS suppliers ON ("
            . "products.supplier_id = suppliers.id "
            . "AND suppliers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ("
            . "products.name like '" . ciniki_core_dbQuote($ciniki, $args['search_str']) . "%' "
            . "OR products.name like '% " . ciniki_core_dbQuote($ciniki, $args['search_str']) . "%' "
            . ") "
        . "ORDER BY products.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'products', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'supplier_code', 'permalink', 'status', 'status_text', 'flags', 'supplier_id'),
            'maps'=>array('status_text'=>$maps['product']['status']),
            ),
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
