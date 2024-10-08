<?php
//
// Description
// ===========
// This method will return all the information about an supplier.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the supplier is attached to.
// supplier_id:          The ID of the supplier to get the details for.
//
// Returns
// -------
//
function ciniki_foodmarket_supplierGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'supplier_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Supplier'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['tnid'], 'ciniki.foodmarket.supplierGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Return default for new Supplier
    //
    if( $args['supplier_id'] == 0 ) {
        $supplier = array('id'=>0,
            'name'=>'',
            'permalink'=>'',
            'code'=>'',
            'flags'=>'0',
            'category'=>'',
            'primary_image_id'=>'0',
            'synopsis'=>'',
            'description'=>'',
            'contact_name'=>'',
            'contact_email'=>'',
            'contact_phone'=>'',
            'contact_cell'=>'',
        );
    }

    //
    // Get the details for an existing Supplier
    //
    else {
        $strsql = "SELECT ciniki_foodmarket_suppliers.id, "
            . "ciniki_foodmarket_suppliers.name, "
            . "ciniki_foodmarket_suppliers.permalink, "
            . "ciniki_foodmarket_suppliers.code, "
            . "ciniki_foodmarket_suppliers.flags, "
            . "ciniki_foodmarket_suppliers.category, "
            . "ciniki_foodmarket_suppliers.primary_image_id, "
            . "ciniki_foodmarket_suppliers.synopsis, "
            . "ciniki_foodmarket_suppliers.description, "
            . "ciniki_foodmarket_suppliers.contact_name, "
            . "ciniki_foodmarket_suppliers.contact_email, "
            . "ciniki_foodmarket_suppliers.contact_phone, "
            . "ciniki_foodmarket_suppliers.contact_cell "
            . "FROM ciniki_foodmarket_suppliers "
            . "WHERE ciniki_foodmarket_suppliers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_foodmarket_suppliers.id = '" . ciniki_core_dbQuote($ciniki, $args['supplier_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'supplier');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.27', 'msg'=>'Supplier not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['supplier']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.28', 'msg'=>'Unable to find Supplier'));
        }
        $supplier = $rc['supplier'];
    }

    return array('stat'=>'ok', 'supplier'=>$supplier);
}
?>
