<?php
//
// Description
// -----------
// This method searchs for a Season Products for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Season Product for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_foodmarket_seasonProductSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['tnid'], 'ciniki.foodmarket.seasonProductSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of products
    //
    $strsql = "SELECT ciniki_foodmarket_season_products.id, "
        . "ciniki_foodmarket_season_products.season_id, "
        . "ciniki_foodmarket_season_products.output_id, "
        . "ciniki_foodmarket_season_products.repeat_days, "
        . "ciniki_foodmarket_season_products.repeat_weeks, "
        . "ciniki_foodmarket_season_products.price "
        . "FROM ciniki_foodmarket_season_products "
        . "WHERE ciniki_foodmarket_season_products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'products', 'fname'=>'id', 
            'fields'=>array('id', 'season_id', 'output_id', 'repeat_days', 'repeat_weeks', 'price')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['products']) ) {
        $products = $rc['products'];
        $product_ids = array();
        foreach($products as $iid => $product) {
            $product_ids[] = $product['id'];
        }
    } else {
        $products = array();
        $product_ids = array();
    }

    return array('stat'=>'ok', 'products'=>$products, 'nplist'=>$product_ids);
}
?>
