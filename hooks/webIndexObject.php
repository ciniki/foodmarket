<?php
//
// Description
// -----------
// This function returns the index details for an object
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business to get events for.
//
// Returns
// -------
//
function ciniki_foodmarket_hooks_webIndexObject($ciniki, $business_id, $args) {

    if( !isset($args['object']) || $args['object'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.65', 'msg'=>'No object specified'));
    }

    if( !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.66', 'msg'=>'No object ID specified'));
    }

    //
    // Setup the base_url for use in index
    //
    if( isset($args['base_url']) ) {
        $base_url = $args['base_url'];
    } else {
        $base_url = '/products';
    }

    if( $args['object'] == 'ciniki.foodmarket.product' ) {
        $strsql = "SELECT id, name, permalink, status, "
            . "primary_image_id, synopsis, description, ingredients "
            . "FROM ciniki_foodmarket_products "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND status = 40 "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.67', 'msg'=>'Object not found'));
        }
        if( !isset($rc['item']) ) {
            return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.foodmarket.68', 'msg'=>'Object not found'));
        }

        //
        // Check if item is visible on website
        //
        if( $rc['item']['status'] != '40' ) {
            return array('stat'=>'ok');
        }
        $object = array(
            'label'=>'Products',
            'title'=>$rc['item']['name'],
            'subtitle'=>'',
            'meta'=>'',
            'primary_image_id'=>$rc['item']['primary_image_id'],
            'synopsis'=>$rc['item']['synopsis'],
            'object'=>'ciniki.foodmarket.product',
            'object_id'=>$rc['item']['id'],
            'primary_words'=>$rc['item']['name'],
            'secondary_words'=>$rc['item']['synopsis'],
            'tertiary_words'=>$rc['item']['description'] . ' ' . $rc['item']['ingredients'],
            'weight'=>10000,
            'url'=>$base_url . '/' . $rc['item']['permalink']
            );
        
        //
        // Get the categories for the product
        //
        $strsql = "SELECT DISTINCT ciniki_foodmarket_categories.name "
            . "FROM ciniki_foodmarket_category_items, ciniki_foodmarket_categories "
            . "WHERE ciniki_foodmarket_category_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_foodmarket_category_items.product_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND ciniki_foodmarket_category_items.category_id = ciniki_foodmarket_categories.id "
            . "AND ciniki_foodmarket_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'category');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows']) ) {
            foreach($rc['rows'] as $row) {
                $object['primary_words'] .= ' ' . $row['name'];
            }
        }

        return array('stat'=>'ok', 'object'=>$object);
    }

    return array('stat'=>'ok');
}
?>
