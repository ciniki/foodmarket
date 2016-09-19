<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
function ciniki_foodmarket_categoryItemsUpdate(&$ciniki, $business_id, $ref_object, $ref_id, $categories) {

    //
    // Get the existing list of categories for the ref
    //
    $strsql = "SELECT id, uuid, category_id "
        . "FROM ciniki_foodmarket_category_items "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ref_object = '" . ciniki_core_dbQuote($ciniki, $ref_object) . "' "
        . "AND ref_id = '" . ciniki_core_dbQuote($ciniki, $ref_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'categories', 'fname'=>'category_id', 'fields'=>array('id', 'uuid', 'category_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['categories']) ) {
        $existing_categories = $rc['categories'];
    } else {
        $existing_categories = array();
    }

    //
    // Check for any new categories that need to be added
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    foreach($categories as $category_id) {
        if( !isset($existing_categories[$category_id]) ) {
            $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.foodmarket.categoryitem', array(
                'category_id'=>$category_id,
                'ref_object'=>$ref_object,
                'ref_id'=>$ref_id,
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    //
    // Check for any categories that need to be removed
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    foreach($existing_categories as $category_id => $category) {
        if( !in_array($category_id, $categories) ) {
            $rc = ciniki_core_objectDelete($ciniki, $business_id, 'ciniki.foodmarket.categoryitem', $category['id'], $category['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>
