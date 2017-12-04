<?php
//
// Description
// ===========
// This method will return all the information about an category.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the category is attached to.
// category_id:          The ID of the category to get the details for.
//
// Returns
// -------
//
function ciniki_foodmarket_categoryGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'category_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Category'),
        'children'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Children'),
        'parents'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Parents'),
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
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['tnid'], 'ciniki.foodmarket.categoryGet');
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Return default for new Category
    //
    if( $args['category_id'] == 0 ) {
        $category = array('id'=>0,
            'name'=>'',
            'permalink'=>'',
            'parent_id'=>'0',
            'ctype'=>'0',
            'sequence'=>'1',
            'image_id'=>'0',
            'synopsis'=>'',
            'description'=>'',
            'children'=>array(),
        );
    }

    //
    // Get the details for an existing Category
    //
    else {
        $strsql = "SELECT ciniki_foodmarket_categories.id, "
            . "ciniki_foodmarket_categories.name, "
            . "ciniki_foodmarket_categories.permalink, "
            . "ciniki_foodmarket_categories.parent_id, "
            . "ciniki_foodmarket_categories.ctype, "
            . "ciniki_foodmarket_categories.sequence, "
            . "ciniki_foodmarket_categories.image_id, "
            . "ciniki_foodmarket_categories.synopsis, "
            . "ciniki_foodmarket_categories.description "
            . "FROM ciniki_foodmarket_categories "
            . "WHERE ciniki_foodmarket_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_foodmarket_categories.id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'category');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.8', 'msg'=>'Category not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['category']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.9', 'msg'=>'Unable to find Category'));
        }
        $category = $rc['category'];

        //
        // Get the list of children
        //
        if( isset($args['children']) && $args['children'] == 'yes' ) {
            $strsql = "SELECT id, name "
                . "FROM ciniki_foodmarket_categories "
                . "WHERE ciniki_foodmarket_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_foodmarket_categories.parent_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(array('container'=>'children', 'fname'=>'id', 'fields'=>array('id', 'name'))));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.10', 'msg'=>'Children not found', 'err'=>$rc['err']));
            }
            if( isset($rc['children']) ) {
                $category['children'] = $rc['children'];
            } else {
                $category['children'] = array();
            }
        }
    }

    $rsp = array('stat'=>'ok', 'category'=>$category);

    //
    // Get the list of parent categories
    //
    if( isset($args['parents']) && $args['parents'] == 'yes' ) {
        $strsql = "SELECT id, name "
            . "FROM ciniki_foodmarket_categories "
            . "WHERE ciniki_foodmarket_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_foodmarket_categories.parent_id = 0 "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(array('container'=>'parents', 'fname'=>'id', 'fields'=>array('id', 'name'))));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.11', 'msg'=>'Parents not found', 'err'=>$rc['err']));
        }
        if( isset($rc['parents']) ) {
            $rsp['parents'] = $rc['parents'];
        } else {
            $rsp['parents'] = array();
        }
        array_unshift($rsp['parents'], array('id'=>'0', 'name'=>''));
    }

    return $rsp;
}
?>
