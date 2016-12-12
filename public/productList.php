<?php
//
// Description
// -----------
// This method will return the list of Products for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Product for.
//
// Returns
// -------
//
function ciniki_foodmarket_productList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'category_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.productList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of products
    //
    if( isset($args['category_id']) && $args['category_id'] > 0 ) {
        $strsql = "SELECT ciniki_foodmarket_products.id, "
            . "ciniki_foodmarket_products.name, "
            . "ciniki_foodmarket_products.permalink, "
            . "ciniki_foodmarket_products.status, "
            . "ciniki_foodmarket_products.flags, "
            . "ciniki_foodmarket_products.supplier_id, "
            . "IFNULL(ciniki_foodmarket_suppliers.code, '') AS supplier_code, "
            . "IFNULL(ciniki_foodmarket_suppliers.name, '') AS supplier_name, "
            . "IFNULL(ciniki_foodmarket_product_inputs.name, '') AS input_names "
            . "FROM ciniki_foodmarket_category_items "
            . "LEFT JOIN ciniki_foodmarket_products ON ("
                . "ciniki_foodmarket_category_items.ref_object = 'ciniki.foodmarket.product' "
                . "AND ciniki_foodmarket_category_items.ref_id = ciniki_foodmarket_products.id "
                . "AND ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_inputs ON ("
                . "ciniki_foodmarket_products.id = ciniki_foodmarket_product_inputs.product_id "
                . "AND ciniki_foodmarket_product_inputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_suppliers ON ("
                . "ciniki_foodmarket_products.supplier_id = ciniki_foodmarket_suppliers.id "
                . "AND ciniki_foodmarket_suppliers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE ciniki_foodmarket_category_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_foodmarket_category_items.category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
            . "ORDER BY ciniki_foodmarket_products.name ";
    } elseif( isset($args['category_id']) && $args['category_id'] == 0 ) {
        $strsql = "SELECT ciniki_foodmarket_products.id, "
            . "ciniki_foodmarket_products.name, "
            . "ciniki_foodmarket_products.permalink, "
            . "ciniki_foodmarket_products.status, "
            . "ciniki_foodmarket_products.flags, "
            . "ciniki_foodmarket_products.supplier_id, "
            . "IFNULL(ciniki_foodmarket_suppliers.code, '') AS supplier_code, "
            . "IFNULL(ciniki_foodmarket_suppliers.name, '') AS supplier_name, "
            . "IFNULL(ciniki_foodmarket_product_inputs.name, '') AS input_names "
            . "FROM ciniki_foodmarket_products "
            . "LEFT JOIN ciniki_foodmarket_category_items ON ("
                . "ciniki_foodmarket_products.id = ciniki_foodmarket_category_items.ref_id "
                . "AND ciniki_foodmarket_category_items.ref_object = 'ciniki.foodmarket.product' "
                . "AND ciniki_foodmarket_category_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_inputs ON ("
                . "ciniki_foodmarket_products.id = ciniki_foodmarket_product_inputs.product_id "
                . "AND ciniki_foodmarket_product_inputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_suppliers ON ("
                . "ciniki_foodmarket_products.supplier_id = ciniki_foodmarket_suppliers.id "
                . "AND ciniki_foodmarket_suppliers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_foodmarket_category_items.id IS NULL "
            . "ORDER BY ciniki_foodmarket_products.name ";
    } else {
        $strsql = "SELECT ciniki_foodmarket_products.id, "
            . "ciniki_foodmarket_products.name, "
            . "ciniki_foodmarket_products.permalink, "
            . "ciniki_foodmarket_products.status, "
            . "ciniki_foodmarket_products.flags, "
            . "ciniki_foodmarket_products.supplier_id, "
            . "IFNULL(ciniki_foodmarket_suppliers.code, '') AS supplier_code, "
            . "IFNULL(ciniki_foodmarket_suppliers.name, '') AS supplier_name, "
            . "IFNULL(ciniki_foodmarket_product_inputs.name, '') AS input_names "
            . "FROM ciniki_foodmarket_products "
            . "LEFT JOIN ciniki_foodmarket_product_inputs ON ("
                . "ciniki_foodmarket_products.id = ciniki_foodmarket_product_inputs.product_id "
                . "AND ciniki_foodmarket_product_inputs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_suppliers ON ("
                . "ciniki_foodmarket_products.supplier_id = ciniki_foodmarket_suppliers.id "
                . "AND ciniki_foodmarket_suppliers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "ORDER BY ciniki_foodmarket_products.date_added DESC "
            . "LIMIT 25 "
            . "";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'products', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'status', 'flags', 'supplier_id', 'supplier_code', 'supplier_name', 'input_names'),
            'lists'=>array('input_names'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['products']) ) {
        $products = $rc['products'];
    } else {
        $products = array();
    }

    $rsp = array('stat'=>'ok', 'products'=>$products);

    //
    // Get the list of categories
    //
    if( isset($args['categories']) && $args['categories'] == 'yes' ) {
        //
        // Get the number of products in each category
        //
        $strsql = "SELECT category_id, COUNT(ref_id) AS num "
            . "FROM ciniki_foodmarket_category_items "
            . "WHERE ciniki_foodmarket_category_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_foodmarket_category_items.ref_object = 'ciniki.foodmarket.product' "
            . "GROUP BY category_id "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
        $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.foodmarket', 'numbers');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['numbers']) ) {
            $category_numbers = $rc['numbers'];
        } else {
            $category_numbers = array();
        }

        $strsql = "SELECT c1.id AS id, c1.name AS name, "
            . "c2.id AS sub_id, "
            . "c2.name AS sub_name "
            . "FROM ciniki_foodmarket_categories AS c1 "
            . "LEFT JOIN ciniki_foodmarket_categories AS c2 ON ("
                . "c1.id = c2.parent_id "
                . "AND c2.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE c1.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND c1.parent_id = 0 "
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
                    'fullname'=>$parent['name'],
                    'num_products'=>(isset($category_numbers[$parent['id']]) ? $category_numbers[$parent['id']] : '0'),
                    );
                if( isset($parent['children']) ) {
                    foreach($parent['children'] as $child) {
                        $rsp['categories'][] = array(
                            'id'=>$child['id'], 
                            'name'=>'&nbsp;&nbsp;-&nbsp;' . $child['name'],
                            'fullname'=>$parent['name'] . ' - ' . $child['name'],
                            'num_products'=>(isset($category_numbers[$child['id']]) ? $category_numbers[$child['id']] : '0'),
                            );
                    }
                }
            }
        } 

        //
        // Check for any products that are currently not in a category
        //
        $strsql = "SELECT COUNT(ciniki_foodmarket_products.id) "
            . "FROM ciniki_foodmarket_products "
            . "LEFT JOIN ciniki_foodmarket_category_items ON ("
                . "ciniki_foodmarket_products.id = ciniki_foodmarket_category_items.ref_id "
                . "AND ciniki_foodmarket_category_items.ref_object = 'ciniki.foodmarket.product' "
                . "AND ciniki_foodmarket_category_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE ciniki_foodmarket_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_foodmarket_category_items.id IS NULL "
            . "GROUP BY ciniki_foodmarket_category_items.id "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.foodmarket', 'number');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['number']) && $rc['number'] > 0 ) {
            $rsp['categories'][] = array(
                'id'=>0,
                'name'=>'Uncategorized',
                'num_products'=>$rc['number'],
                );
        }
    }

    return $rsp;
}
?>
