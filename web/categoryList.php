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
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
//
function ciniki_foodmarket_web_categoryList($ciniki, $settings, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

    $strsql = "SELECT parents.id, "
        . "parents.name, "
        . "parents.permalink, "
        . "parents.ctype, "
        . "parents.image_id, "
        . "children.id AS child_id, "
        . "children.name AS child_name, "
        . "children.permalink AS child_permalink, "
        . "children.ctype AS child_ctype, "
        . "children.image_id AS child_image_id "
        . "FROM ciniki_foodmarket_categories AS parents "
        . "LEFT JOIN ciniki_foodmarket_categories AS children ON ("
            . "parents.id = children.parent_id "
            . "AND children.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE parents.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (parents.flags&0x01) = 0x01 "
        . "";
    if( isset($args['parent_id']) && $args['parent_id'] > 0 ) {
        $strsql .= "AND parents.parent_id = '" . ciniki_core_dbQuote($ciniki, $args['parent_id']) . "' ";
    } else {
        $strsql .= "AND parents.parent_id = 0 ";
    }
    $strsql .= "ORDER BY parents.sequence, parents.name, children.sequence, children.name ";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'categories', 'fname'=>'id', 'fields'=>array('id', 'name', 'permalink', 'ctype', 'image_id')),
        array('container'=>'subcategories', 'fname'=>'child_id', 'fields'=>array('id'=>'child_id', 'name'=>'child_name', 
            'permalink'=>'child_permalink', 'ctype'=>'child_ctype', 'image_id'=>'child_image_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['categories']) ) {
        return array('stat'=>'ok', 'categories'=>array());
    }
    $categories = $rc['categories'];
    foreach($categories as $cid => $category) {
        if( $category['ctype'] == 10 || $category['ctype'] == 60 ) {
            if( !isset($ciniki['session']['customer']['id']) || $ciniki['session']['customer']['id'] < 1 ) {
                unset($categories[$cid]);
            }
        }
    }

    return array('stat'=>'ok', 'categories'=>$categories);
}
?>
