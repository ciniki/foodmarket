<?php
//
// Description
// -----------
// This function returns the index details for an object
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
//
function ciniki_foodmarket_hooks_webIndexObject($ciniki, $tnid, $args) {

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
        $strsql = "SELECT id, name, permalink, status, legend_codes, "
            . "primary_image_id, synopsis, description, ingredients "
            . "FROM ciniki_foodmarket_products "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
        $item = $rc['item'];

        //
        // Get the list of inputs to add to the search words
        //
        $strsql = "SELECT i.name "
            . "FROM ciniki_foodmarket_product_inputs AS i, ciniki_foodmarket_product_outputs AS o "
            . "WHERE i.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND i.product_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND i.id = o.input_id "
            . "AND o.status = 40 "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.foodmarket', 'inputs', 'name');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $inputs = '';
        if( isset($rc['inputs']) ) {
            $inputs = ' ' . implode(' ', $rc['inputs']);
        }

        //
        // Check if item is visible on website
        //
        if( $item['status'] != '40' ) {
            return array('stat'=>'ok');
        }
        $object = array(
            'label'=>'Products',
            'title'=>$item['name'] . (isset($item['legend_codes']) && $item['legend_codes'] != '' ? ' ' . $item['legend_codes'] : ''),
            'subtitle'=>'',
            'meta'=>'',
            'primary_image_id'=>$item['primary_image_id'],
            'synopsis'=>$item['synopsis'],
            'object'=>'ciniki.foodmarket.product',
            'object_id'=>$item['id'],
            'primary_words'=>$item['name'] . $inputs,
            'secondary_words'=>$item['synopsis'],
            'tertiary_words'=>$item['description'] . ' ' . $item['ingredients'],
            'weight'=>10000,
            'url'=>$base_url . '/' . $item['permalink']
            );
        
        //
        // Get the categories for the product
        //
        $strsql = "SELECT DISTINCT ciniki_foodmarket_categories.name "
            . "FROM ciniki_foodmarket_category_items, ciniki_foodmarket_categories "
            . "WHERE ciniki_foodmarket_category_items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_foodmarket_category_items.product_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND ciniki_foodmarket_category_items.category_id = ciniki_foodmarket_categories.id "
            . "AND ciniki_foodmarket_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'category');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows']) ) {
            foreach($rc['rows'] as $row) {
                $object['secondary_words'] .= ' ' . $row['name'];
            }
        }

        return array('stat'=>'ok', 'object'=>$object);
    }

    return array('stat'=>'ok');
}
?>
