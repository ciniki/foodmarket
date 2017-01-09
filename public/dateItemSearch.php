<?php
//
// Description
// -----------
// This method searchs for a Order Date Items for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to get Order Date Item for.
// search_str:          The search string to search for.
// limit:               The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_foodmarket_dateItemSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'search_str'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.dateItemSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Search the outputs that are date limited
    //
    $strsql = "SELECT ciniki_foodmarket_product_outputs.id, "
        . "ciniki_foodmarket_products.supplier_id, "
        . "IFNULL(ciniki_foodmarket_suppliers.code, '') AS supplier_code, "
        . "ciniki_foodmarket_product_outputs.pio_name AS name, "
        . "IFNULL(MAX(ciniki_poma_order_dates.order_date), '') AS last_order_date, "
        . "DATEDIFF(NOW(), MAX(ciniki_poma_order_dates.order_date)) AS days "
        . "FROM ciniki_foodmarket_product_outputs "
        . "LEFT JOIN ciniki_foodmarket_date_items ON ("
            . "ciniki_foodmarket_product_outputs.id = ciniki_foodmarket_date_items.output_id "
            . "AND ciniki_foodmarket_date_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_poma_order_dates ON ("
            . "ciniki_foodmarket_date_items.date_id = ciniki_poma_order_dates.id "
            . "AND ciniki_poma_order_dates.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_foodmarket_products ON ("
            . "ciniki_foodmarket_product_outputs.product_id = ciniki_foodmarket_products.id "
            . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_foodmarket_suppliers ON ("
            . "ciniki_foodmarket_products.supplier_id = ciniki_foodmarket_suppliers.id "
            . "AND ciniki_foodmarket_suppliers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE (ciniki_foodmarket_product_outputs.flags&0x0200) = 0x0200 "
        . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ("
            . "ciniki_foodmarket_product_outputs.pio_name LIKE '" . ciniki_core_dbQuote($ciniki, $args['search_str']) . "%' "
            . "OR ciniki_foodmarket_product_outputs.pio_name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['search_str']) . "%' "
        . ") "
        . "GROUP BY ciniki_foodmarket_product_outputs.id "
        . "ORDER BY supplier_code, pio_name "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.poma', array(
        array('container'=>'products', 'fname'=>'id', 
            'fields'=>array('id', 'supplier_id', 'supplier_code', 'name'=>'name', 'last_order_date', 'days'),
            'utctotz'=>array('last_order_date'=>array('timezone'=>'UTC', 'format'=>$date_format)),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['products']) ) {
        $products = $rc['products'];
    }

    return array('stat'=>'ok', 'products'=>$products);
}
?>
