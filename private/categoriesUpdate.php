<?php
//
// Description
// -----------
// This function will check for products in each category/subcategory and set the visible flag.
//
// Arguments
// ---------
//
function ciniki_foodmarket_categoriesUpdate(&$ciniki, $business_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    //
    // Get the list of categories
    //
    $strsql = "SELECT id, parent_id, ctype, flags, 'no' AS visible "
        . "FROM ciniki_foodmarket_categories "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'categories', 'fname'=>'id', 'fields'=>array('id', 'parent_id', 'ctype', 'flags', 'visible')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['categories']) ) {
        $categories = $rc['categories'];
    } else {
        $categories = array();
    }

    //
    // Get the list of categories and the number of products in each
    //
    $strsql = "SELECT ciniki_foodmarket_category_items.category_id, "
        . "COUNT(ciniki_foodmarket_category_items.product_id) AS num_products "
        . "FROM ciniki_foodmarket_category_items, ciniki_foodmarket_products, ciniki_foodmarket_product_outputs "
        . "WHERE ciniki_foodmarket_category_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_foodmarket_category_items.product_id = ciniki_foodmarket_products.id "
        . "AND ciniki_foodmarket_products.status = 40 "
        . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_foodmarket_products.id = ciniki_foodmarket_product_outputs.product_id "
        . "AND ciniki_foodmarket_product_outputs.status = 40 "
        . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "GROUP BY ciniki_foodmarket_category_items.category_id "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'categories', 'fname'=>'category_id', 'fields'=>array('category_id', 'num_products')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['categories']) ) {
        foreach($rc['categories'] as $cat) {
            if( isset($categories[$cat['category_id']]) ) {
                if( $cat['num_products'] > 0 ) {
                    $categories[$cat['category_id']]['visible'] = 'yes';
                    $parent_id = $categories[$cat['category_id']]['parent_id'];
                    while($parent_id != 0) {
                        if( isset($categories[$parent_id]) ) {
                            $categories[$parent_id]['visible'] = 'yes';
                            $parent_id = $categories[$parent_id]['parent_id'];
                        }
                    }
                }
            }
        }
    }

    foreach($categories as $cat) {
        if( $cat['ctype'] == 10 ) {
            $cat['visible'] = 'yes';
        }

        //
        // Check for specials
        //
        if( $cat['ctype'] == 30 ) {
            $strsql = "SELECT COUNT(ciniki_foodmarket_products.id) AS num_products "
                . "FROM ciniki_foodmarket_products, ciniki_foodmarket_product_outputs "
                . "WHERE ciniki_foodmarket_products.status = 40 "
                . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND ciniki_foodmarket_products.id = ciniki_foodmarket_product_outputs.product_id "
                . "AND ciniki_foodmarket_product_outputs.retail_sdiscount_percent > 0 "
                . "AND ciniki_foodmarket_product_outputs.status = 40 "
                . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'num');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['num']['num_products']) && $rc['num']['num_products'] > 0 ) {
                $cat['visible'] = 'yes';
            }
        }

        //
        // Check for new products
        //
        if( $cat['ctype'] == 50 ) {
            $strsql = "SELECT COUNT(DISTINCT ciniki_foodmarket_products.id) AS num_products "
                . "FROM ciniki_foodmarket_products, ciniki_foodmarket_product_outputs "
                . "WHERE ciniki_foodmarket_products.status = 40 "
                . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND (ciniki_foodmarket_products.flags&0x01) = 0x01 "
                . "AND ciniki_foodmarket_products.id = ciniki_foodmarket_product_outputs.product_id "
                . "AND ciniki_foodmarket_product_outputs.status = 40 "
                . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'num');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['num']['num_products']) && $rc['num']['num_products'] > 0 ) {
                $cat['visible'] = 'yes';
            }
        }

        //
        // If category should be visible and isn't then set visible flag
        //
        $update_args = array();
        if( $cat['visible'] == 'yes' && ($cat['flags']&0x01) == 0 ) {
            $update_args['flags'] = $cat['flags'] | 0x01;
        } 
        //
        // If category is not visible and flags is set to visible, remove
        //
        elseif( $cat['visible'] == 'no' && ($cat['flags']&0x01) == 0x01 ) {
            $update_args['flags'] = $cat['flags'] | 0x01;
            $update_args['flags'] = ((int)$cat['flags']) &~ 0x01;
        }

        //
        // Update the category
        //
        if( count($update_args) > 0 ) {
            $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.foodmarket.category', $cat['id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>
