<?php
//
// Description
// ===========
// This method will return all the information about an product.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the product is attached to.
// product_id:          The ID of the product to get the details for.
//
// Returns
// -------
//
function ciniki_foodmarket_productGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'product_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product'),
        'suppliers'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Suppliers'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'),
        'category_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'legends'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Legends'),
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
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['tnid'], 'ciniki.foodmarket.productGet');
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
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'maps');
    $rc = ciniki_foodmarket_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');

    $defaults = array(
        'input1_id'=>'0',
        'input1_itype'=>'0',
        'input1_units'=>0x010102,
        'input1_case_cost'=>'',
        'input1_case_units'=>'',
        'input1_flags'=>0,
        'input1_cdeposit_name'=>'',
        'input1_cdeposit_amount'=>'',
        'input1_10_units'=>0x0002,
//        'input1_10_packing_order'=>'10',
        'input1_10_flags'=>0x0100,
        'input1_10_retail_percent'=>'0.50',
        'input1_10_retail_taxtype_id'=>'0',
        'input1_20_units'=>0x0102,
//        'input1_20_packing_order'=>'10',
        'input1_20_flags'=>0x0100,
        'input1_20_retail_percent'=>'0.50',
        'input1_20_retail_taxtype_id'=>'0',
        'input1_30_units'=>0x0100,
//        'input1_30_packing_order'=>'10',
        'input1_30_flags'=>0x0100,
        'input1_30_retail_percent'=>'0.50',
        'input1_30_retail_taxtype_id'=>'0',
//        'input1_50_packing_order'=>'10',
        'input1_50_flags'=>0x0100,
        'input1_50_retail_percent'=>'0.10',
        'input1_50_retail_taxtype_id'=>'0',
//        'input1_52_packing_order'=>'10',
        'input1_52_flags'=>0x0400,
        'input1_52_retail_percent'=>'0.20',
        'input1_52_retail_taxtype_id'=>'0',
//        'input1_53_packing_order'=>'10',
        'input1_53_flags'=>0x0400,
        'input1_53_retail_percent'=>'0.25',
        'input1_53_retail_taxtype_id'=>'0',
//        'input1_54_packing_order'=>'10',
        'input1_54_flags'=>0x0400,
        'input1_54_retail_percent'=>'0.30',
        'input1_54_retail_taxtype_id'=>'0',
//        'input1_55_packing_order'=>'10',
        'input1_55_flags'=>0x0400,
        'input1_55_retail_percent'=>'0.40',
        'input1_55_retail_taxtype_id'=>'0',
//        'input1_56_packing_order'=>'10',
        'input1_56_flags'=>0x0400,
        'input1_56_retail_percent'=>'0.50',
        'input1_56_retail_taxtype_id'=>'0',
//        'input1_71_packing_order'=>'10',
        'input1_71_retail_discount'=>'0.10',
        'input1_71_retail_taxtype_id'=>'0',
//        'input1_72_packing_order'=>'10',
        'input1_72_retail_discount'=>'0.10',
        'input1_72_retail_taxtype_id'=>'0',
        );
    //
    // Return default for new Product
    //
    if( $args['product_id'] == 0 ) {
        $product = array('id'=>0,
            'name'=>'',
            'permalink'=>'',
            'status'=>'10',
            'ptype'=>'0',
            'flags'=>'0',
            'category'=>'',
            'packing_order'=>'50',
            'primary_image_id'=>'0',
            'synopsis'=>'',
            'description'=>'',
            'supplier_id'=>'0',
            'categories'=>(isset($args['category_id']) && $args['category_id'] > 0 ? $args['category_id'] : ''),
            'legends'=>'',
        );
    }

    //
    // Get the details for an existing Product
    //
    else {
        //
        // Load the product
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'productLoad');
        $rc = ciniki_foodmarket_productLoad($ciniki, $args['tnid'], $args['product_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['product']) ) {
            $product = $rc['product'];
        } else {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.16', 'msg'=>'Unable to find product requested'));
        }

        //
        // Sort the suppliers
        //
        usort($product['inputs'], function($a, $b) {
            if( $a['sequence'] == $b['sequence'] ) {
                return strcasecmp($a['name'], $b['name']);
            }
            return ($a['sequence'] < $b['sequence']) ? -1 : 1;
        });
    }

    //
    // Merge defaults
    //
    $product = array_merge($defaults, $product);

    $rsp = array('stat'=>'ok', 'product'=>$product);

    //
    // Get the list of suppliers
    //
    if( isset($args['suppliers']) && $args['suppliers'] == 'yes' ) {
        $strsql = "SELECT id, code, name, CONCAT_WS(' - ', code, name) AS display_name "
            . "FROM ciniki_foodmarket_suppliers "
            . "WHERE ciniki_foodmarket_suppliers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(array('container'=>'suppliers', 'fname'=>'id', 'fields'=>array('id', 'name', 'display_name'))));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['suppliers']) ) {
            $rsp['suppliers'] = $rc['suppliers'];
        } else {
            $rsp['suppliers'] = array();
        }
        array_unshift($rsp['suppliers'], array('id'=>'0', 'name'=>'None'));
    }

    //
    // Get the list of categories
    //
    if( isset($args['categories']) && $args['categories'] == 'yes' ) {
        $strsql = "SELECT c1.id AS id, c1.name AS name, "
            . "c2.id AS sub_id, "
            . "c2.name AS sub_name "
            . "FROM ciniki_foodmarket_categories AS c1 "
            . "LEFT JOIN ciniki_foodmarket_categories AS c2 ON ("
                . "c1.id = c2.parent_id "
                . "AND c2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE c1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND c1.parent_id = 0 "
            . "AND (c1.ctype < 10 || c1.ctype = 90) "
            . "ORDER BY c1.name, c2.name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
            array('container'=>'parents', 'fname'=>'id', 'fields'=>array('id', 'name')),
            array('container'=>'children', 'fname'=>'sub_id', 'fields'=>array('id'=>'sub_id', 'name'=>'sub_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['categories'] = array();
        if( isset($rc['parents']) ) {
            //
            // Flatten the array
            //
            foreach($rc['parents'] as $parent) {
                $rsp['categories'][] = array(
                    'id'=>$parent['id'], 
                    'name'=>$parent['name'],
                    );
                if( isset($parent['children']) ) {
                    foreach($parent['children'] as $child) {
                        $rsp['categories'][] = array(
                            'id'=>$child['id'], 
                            'name'=>$parent['name'] . ' - ' . $child['name'],
                            );
                    }
                }
            }
        } 
    }

    //
    // Get the list of legends
    //
    if( isset($args['legends']) && $args['legends'] == 'yes' ) {
        $strsql = "SELECT legends.id, legends.name "
            . "FROM ciniki_foodmarket_legends AS legends "
            . "WHERE legends.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY legends.name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
            array('container'=>'legends', 'fname'=>'id', 'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['legends'] = array();
        if( isset($rc['legends']) ) {
            $rsp['legends'] = $rc['legends'];
        } 
    }

    return $rsp;
}
?>
