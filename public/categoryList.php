<?php
//
// Description
// -----------
// This method will return the list of Categorys for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Category for.
//
// Returns
// -------
//
function ciniki_foodmarket_categoryList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'subscriptions'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Subscriptions'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.categoryList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of categories
    //
    $strsql = "SELECT ciniki_foodmarket_categories.id, "
        . "ciniki_foodmarket_categories.name, "
        . "ciniki_foodmarket_categories.permalink "
        . "FROM ciniki_foodmarket_categories "
        . "WHERE ciniki_foodmarket_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_foodmarket_categories.parent_id = 0 "
        . "AND ctype = 0 "
        . "ORDER BY ciniki_foodmarket_categories.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'categories', 'fname'=>'id', 'fields'=>array('id', 'name', 'permalink')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['categories']) ) {
        $categories = $rc['categories'];
    } else {
        $categories = array();
    }

    $rsp = array('stat'=>'ok', 'categories'=>$categories, 'subscriptions'=>array());

    //
    // Check if subscription list requested
    //
    if( isset($args['subscriptions']) && $args['subscriptions'] == 'yes' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'hooks', 'subscriptionList');
        $rc = ciniki_subscriptions_hooks_subscriptionList($ciniki, $args['business_id'], array());
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['subscriptions']) ) {
            $rsp['subscriptions'] = $rc['subscriptions'];
        }
    }

    return $rsp;
}
?>
