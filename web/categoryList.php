<?php
//
// Description
// -----------
// This function will return a list of categories for the web product page.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// business_id:     The ID of the business to get events for.
//
// Returns
// -------
//
function ciniki_foodmarket_web_categoryList($ciniki, $settings, $business_id, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

    $strsql = "SELECT ciniki_foodmarket_categories.id, "
        . "ciniki_foodmarket_categories.name, "
        . "ciniki_foodmarket_categories.permalink, "
        . "ciniki_foodmarket_categories.ctype, "
        . "ciniki_foodmarket_categories.image_id "
        . "FROM ciniki_foodmarket_categories "
        . "WHERE ciniki_foodmarket_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND (ciniki_foodmarket_categories.flags&0x01) = 0x01 "
        . "";
    if( isset($args['parent_id']) && $args['parent_id'] > 0 ) {
        $strsql .= "AND parent_id = '" . ciniki_core_dbQuote($ciniki, $args['parent_id']) . "' ";
    } else {
        $strsql .= "AND parent_id = 0 ";
    }
    $strsql .= "ORDER BY sequence, name ";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'categories', 'fname'=>'id', 'fields'=>array('id', 'name', 'permalink', 'ctype', 'image_id', 'num_products')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['categories']) ) {
        return array('stat'=>'ok', 'categories'=>array());
    }
    $categories = $rc['categories'];
    foreach($categories as $cid => $category) {
        if( $category['ctype'] == 10 ) {
            if( !isset($ciniki['session']['customer']['id']) || $ciniki['session']['customer']['id'] < 1 ) {
                unset($categories[$cid]);
            }
        }
    }

    return array('stat'=>'ok', 'categories'=>$categories);
}
?>
