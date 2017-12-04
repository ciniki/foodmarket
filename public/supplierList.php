<?php
//
// Description
// -----------
// This method will return the list of Suppliers for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Supplier for.
//
// Returns
// -------
//
function ciniki_foodmarket_supplierList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['tnid'], 'ciniki.foodmarket.supplierList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of suppliers
    //
    $strsql = "SELECT ciniki_foodmarket_suppliers.id, "
        . "ciniki_foodmarket_suppliers.name, "
        . "ciniki_foodmarket_suppliers.permalink, "
        . "ciniki_foodmarket_suppliers.code, "
        . "ciniki_foodmarket_suppliers.flags, "
        . "ciniki_foodmarket_suppliers.category, "
        . "ciniki_foodmarket_suppliers.contact_name, "
        . "ciniki_foodmarket_suppliers.contact_email, "
        . "ciniki_foodmarket_suppliers.contact_phone, "
        . "ciniki_foodmarket_suppliers.contact_cell, "
        . "COUNT(ciniki_foodmarket_products.supplier_id) AS num_products "
        . "FROM ciniki_foodmarket_suppliers "
        . "LEFT JOIN ciniki_foodmarket_products ON ("
            . "ciniki_foodmarket_suppliers.id = ciniki_foodmarket_products.supplier_id "
            . "AND ciniki_foodmarket_products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_foodmarket_suppliers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "GROUP BY ciniki_foodmarket_suppliers.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'suppliers', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'code', 'flags', 'category', 'contact_name', 'contact_email', 'contact_phone', 'contact_cell', 'num_products')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['suppliers']) ) {
        $suppliers = $rc['suppliers'];
    } else {
        $suppliers = array();
    }

    return array('stat'=>'ok', 'suppliers'=>$suppliers);
}
?>
