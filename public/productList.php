<?php
//
// Description
// -----------
// This method will return the list of Products for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Product for.
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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'category_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'),
        'sales'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sales'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['tnid'], 'ciniki.foodmarket.productList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'maps');
    $rc = ciniki_foodmarket_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Get the category type
    //
    $strsql = "SELECT ctype, name "
        . "FROM ciniki_foodmarket_categories "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'category');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['category']) ) {
        $ctype = $rc['category']['ctype'];
    } else {
        $ctype = 0;
    }

    //
    // Get the list of products
    //
    if( isset($args['category_id']) && $args['category_id'] != '' && $args['category_id'] > 0 && $ctype == 30 ) {
        $strsql = "SELECT ciniki_foodmarket_products.id, "
            . "ciniki_foodmarket_products.name, "
            . "ciniki_foodmarket_products.permalink, "
            . "ciniki_foodmarket_products.status, "
            . "ciniki_foodmarket_products.flags, "
            . "ciniki_foodmarket_products.supplier_id, "
            . "ciniki_foodmarket_product_outputs.retail_sdiscount_percent, "
            . "IFNULL(ciniki_foodmarket_suppliers.code, '') AS supplier_code, "
            . "IFNULL(ciniki_foodmarket_suppliers.name, '') AS supplier_name, "
            . "IFNULL(ciniki_foodmarket_product_inputs.id, 0) AS input_id, "
            . "IFNULL(ciniki_foodmarket_product_inputs.itype, 0) AS itype, "
            . "IFNULL(ciniki_foodmarket_product_inputs.name, '') AS input_name, "
            . "IFNULL(ciniki_foodmarket_product_outputs.id, 0) AS output_id, "
            . "IFNULL(ciniki_foodmarket_product_outputs.status, 0) AS output_status, "
            . "IFNULL(ciniki_foodmarket_product_outputs.otype, 0) AS otype, "
            . "IFNULL(ciniki_foodmarket_product_outputs.flags, 0) AS oflags "
            . "FROM ciniki_foodmarket_products "
            . "LEFT JOIN ciniki_foodmarket_product_inputs ON ("
                . "ciniki_foodmarket_products.id = ciniki_foodmarket_product_inputs.product_id "
                . "AND ciniki_foodmarket_product_inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_outputs ON ("
                . "ciniki_foodmarket_products.id = ciniki_foodmarket_product_outputs.product_id "
                . "AND ciniki_foodmarket_product_outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_suppliers ON ("
                . "ciniki_foodmarket_products.supplier_id = ciniki_foodmarket_suppliers.id "
                . "AND ciniki_foodmarket_suppliers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_foodmarket_products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_foodmarket_product_outputs.retail_sdiscount_percent > 0 "
            . "ORDER BY ciniki_foodmarket_products.name, ciniki_foodmarket_product_inputs.name "
            . "";

    } elseif( isset($args['category_id']) && $args['category_id'] != '' && $args['category_id'] > 0 && $ctype == 50 ) {
        $strsql = "SELECT ciniki_foodmarket_products.id, "
            . "ciniki_foodmarket_products.name, "
            . "ciniki_foodmarket_products.permalink, "
            . "ciniki_foodmarket_products.status, "
            . "ciniki_foodmarket_products.flags, "
            . "ciniki_foodmarket_products.supplier_id, "
            . "ciniki_foodmarket_product_outputs.retail_sdiscount_percent, "
            . "IFNULL(ciniki_foodmarket_suppliers.code, '') AS supplier_code, "
            . "IFNULL(ciniki_foodmarket_suppliers.name, '') AS supplier_name, "
            . "IFNULL(ciniki_foodmarket_product_inputs.id, 0) AS input_id, "
            . "IFNULL(ciniki_foodmarket_product_inputs.itype, 0) AS itype, "
            . "IFNULL(ciniki_foodmarket_product_inputs.name, '') AS input_name, "
            . "IFNULL(ciniki_foodmarket_product_outputs.id, 0) AS output_id, "
            . "IFNULL(ciniki_foodmarket_product_outputs.status, 0) AS output_status, "
            . "IFNULL(ciniki_foodmarket_product_outputs.otype, 0) AS otype, "
            . "IFNULL(ciniki_foodmarket_product_outputs.flags, 0) AS oflags "
            . "FROM ciniki_foodmarket_products "
            . "LEFT JOIN ciniki_foodmarket_product_inputs ON ("
                . "ciniki_foodmarket_products.id = ciniki_foodmarket_product_inputs.product_id "
                . "AND ciniki_foodmarket_product_inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_outputs ON ("
                . "ciniki_foodmarket_products.id = ciniki_foodmarket_product_outputs.product_id "
                . "AND ciniki_foodmarket_product_outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_suppliers ON ("
                . "ciniki_foodmarket_products.supplier_id = ciniki_foodmarket_suppliers.id "
                . "AND ciniki_foodmarket_suppliers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_foodmarket_products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND (ciniki_foodmarket_products.flags&0x01) = 0x01 "
            . "ORDER BY ciniki_foodmarket_products.name, ciniki_foodmarket_product_inputs.name "
            . "";

    } elseif( isset($args['category_id']) && $args['category_id'] != '' && $args['category_id'] > 0 ) {
        $strsql = "SELECT ciniki_foodmarket_products.id, "
            . "ciniki_foodmarket_products.name, "
            . "ciniki_foodmarket_products.permalink, "
            . "ciniki_foodmarket_products.status, "
            . "ciniki_foodmarket_products.flags, "
            . "ciniki_foodmarket_products.supplier_id, "
            . "IFNULL(ciniki_foodmarket_suppliers.code, '') AS supplier_code, "
            . "IFNULL(ciniki_foodmarket_suppliers.name, '') AS supplier_name, "
            . "IFNULL(ciniki_foodmarket_product_inputs.id, 0) AS input_id, "
            . "IFNULL(ciniki_foodmarket_product_inputs.itype, 0) AS itype, "
            . "IFNULL(ciniki_foodmarket_product_inputs.name, '') AS input_name, "
            . "IFNULL(ciniki_foodmarket_product_outputs.id, 0) AS output_id, "
            . "IFNULL(ciniki_foodmarket_product_outputs.status, 0) AS output_status, "
            . "IFNULL(ciniki_foodmarket_product_outputs.otype, 0) AS otype, "
            . "IFNULL(ciniki_foodmarket_product_outputs.flags, 0) AS oflags "
            . "FROM ciniki_foodmarket_category_items "
            . "LEFT JOIN ciniki_foodmarket_products ON ("
                . "ciniki_foodmarket_category_items.product_id = ciniki_foodmarket_products.id "
                . "AND ciniki_foodmarket_products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_inputs ON ("
                . "ciniki_foodmarket_products.id = ciniki_foodmarket_product_inputs.product_id "
                . "AND ciniki_foodmarket_product_inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_outputs ON ("
                . "ciniki_foodmarket_products.id = ciniki_foodmarket_product_outputs.product_id "
                . "AND ciniki_foodmarket_product_outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_suppliers ON ("
                . "ciniki_foodmarket_products.supplier_id = ciniki_foodmarket_suppliers.id "
                . "AND ciniki_foodmarket_suppliers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_foodmarket_category_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_foodmarket_category_items.category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
            . "ORDER BY ciniki_foodmarket_products.date_added DESC, ciniki_foodmarket_product_inputs.name "
            . "";
    } elseif( isset($args['category_id']) && $args['category_id'] != '' && $args['category_id'] == 0 ) {
        $strsql = "SELECT ciniki_foodmarket_products.id, "
            . "ciniki_foodmarket_products.name, "
            . "ciniki_foodmarket_products.permalink, "
            . "ciniki_foodmarket_products.status, "
            . "ciniki_foodmarket_products.flags, "
            . "ciniki_foodmarket_products.supplier_id, "
            . "IFNULL(ciniki_foodmarket_suppliers.code, '') AS supplier_code, "
            . "IFNULL(ciniki_foodmarket_suppliers.name, '') AS supplier_name, "
            . "IFNULL(ciniki_foodmarket_product_inputs.id, 0) AS input_id, "
            . "IFNULL(ciniki_foodmarket_product_inputs.itype, 0) AS itype, "
            . "IFNULL(ciniki_foodmarket_product_inputs.name, '') AS input_name, "
            . "IFNULL(ciniki_foodmarket_product_outputs.id, 0) AS output_id, "
            . "IFNULL(ciniki_foodmarket_product_outputs.status, 0) AS output_status, "
            . "IFNULL(ciniki_foodmarket_product_outputs.otype, 0) AS otype, "
            . "IFNULL(ciniki_foodmarket_product_outputs.flags, 0) AS oflags "
            . "FROM ciniki_foodmarket_products "
            . "LEFT JOIN ciniki_foodmarket_category_items ON ("
                . "ciniki_foodmarket_products.id = ciniki_foodmarket_category_items.product_id "
                . "AND ciniki_foodmarket_category_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_inputs ON ("
                . "ciniki_foodmarket_products.id = ciniki_foodmarket_product_inputs.product_id "
                . "AND ciniki_foodmarket_product_inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_outputs ON ("
                . "ciniki_foodmarket_products.id = ciniki_foodmarket_product_outputs.product_id "
                . "AND ciniki_foodmarket_product_outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_suppliers ON ("
                . "ciniki_foodmarket_products.supplier_id = ciniki_foodmarket_suppliers.id "
                . "AND ciniki_foodmarket_suppliers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_foodmarket_products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_foodmarket_category_items.id IS NULL "
            . "ORDER BY ciniki_foodmarket_products.name, ciniki_foodmarket_product_inputs.name "
            . "";
    } elseif( isset($args['category_id']) && $args['category_id'] == -1 ) {
        $strsql = "SELECT ciniki_foodmarket_products.id, "
            . "ciniki_foodmarket_products.name, "
            . "ciniki_foodmarket_products.permalink, "
            . "ciniki_foodmarket_products.status, "
            . "ciniki_foodmarket_products.flags, "
            . "ciniki_foodmarket_products.supplier_id, "
            . "IFNULL(ciniki_foodmarket_suppliers.code, '') AS supplier_code, "
            . "IFNULL(ciniki_foodmarket_suppliers.name, '') AS supplier_name, "
            . "IFNULL(ciniki_foodmarket_product_inputs.id, 0) AS input_id, "
            . "IFNULL(ciniki_foodmarket_product_inputs.itype, 0) AS itype, "
            . "IFNULL(ciniki_foodmarket_product_inputs.name, '') AS input_name, "
            . "IFNULL(ciniki_foodmarket_product_outputs.id, 0) AS output_id, "
            . "IFNULL(ciniki_foodmarket_product_outputs.status, 0) AS output_status, "
            . "IFNULL(ciniki_foodmarket_product_outputs.otype, 0) AS otype, "
            . "IFNULL(ciniki_foodmarket_product_outputs.flags, 0) AS oflags "
            . "FROM ciniki_foodmarket_products "
            . "LEFT JOIN ciniki_foodmarket_product_inputs ON ("
                . "ciniki_foodmarket_products.id = ciniki_foodmarket_product_inputs.product_id "
                . "AND ciniki_foodmarket_product_inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_outputs ON ("
                . "ciniki_foodmarket_products.id = ciniki_foodmarket_product_outputs.product_id "
                . "AND ciniki_foodmarket_product_outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_suppliers ON ("
                . "ciniki_foodmarket_products.supplier_id = ciniki_foodmarket_suppliers.id "
                . "AND ciniki_foodmarket_suppliers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_foodmarket_products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_foodmarket_products.status = 90 "
            . "ORDER BY ciniki_foodmarket_products.name, ciniki_foodmarket_product_inputs.name "
            . "";
    } else {
        $strsql = "SELECT ciniki_foodmarket_products.id, "
            . "ciniki_foodmarket_products.name, "
            . "ciniki_foodmarket_products.permalink, "
            . "ciniki_foodmarket_products.status, "
            . "ciniki_foodmarket_products.flags, "
            . "ciniki_foodmarket_products.supplier_id, "
            . "IFNULL(ciniki_foodmarket_suppliers.code, '') AS supplier_code, "
            . "IFNULL(ciniki_foodmarket_suppliers.name, '') AS supplier_name, "
            . "IFNULL(ciniki_foodmarket_product_inputs.id, 0) AS input_id, "
            . "IFNULL(ciniki_foodmarket_product_inputs.itype, 0) AS itype, "
            . "IFNULL(ciniki_foodmarket_product_inputs.name, '') AS input_name, "
            . "IFNULL(ciniki_foodmarket_product_outputs.id, 0) AS output_id, "
            . "IFNULL(ciniki_foodmarket_product_outputs.status, 0) AS output_status, "
            . "IFNULL(ciniki_foodmarket_product_outputs.otype, 0) AS otype, "
            . "IFNULL(ciniki_foodmarket_product_outputs.flags, 0) AS oflags "
            . "FROM ciniki_foodmarket_products "
            . "LEFT JOIN ciniki_foodmarket_product_inputs ON ("
                . "ciniki_foodmarket_products.id = ciniki_foodmarket_product_inputs.product_id "
                . "AND ciniki_foodmarket_product_inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_outputs ON ("
                . "ciniki_foodmarket_products.id = ciniki_foodmarket_product_outputs.product_id "
                . "AND ciniki_foodmarket_product_outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_suppliers ON ("
                . "ciniki_foodmarket_products.supplier_id = ciniki_foodmarket_suppliers.id "
                . "AND ciniki_foodmarket_suppliers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_foodmarket_products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY ciniki_foodmarket_products.date_added DESC, ciniki_foodmarket_product_inputs.name "
            . "LIMIT 25 "
            . "";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'products', 'fname'=>'input_id', 
            'fields'=>array('id', 'name', 'permalink', 'status', 'flags', 'supplier_id', 'supplier_code', 'supplier_name', 'itype', 'input_name'),
//            'lists'=>array('output_ids'),
            ),
        array('container'=>'outputs', 'fname'=>'output_id',
            'fields'=>array('id'=>'output_id', 'status'=>'output_status', 'status_text'=>'output_status', 'otype', 'flags'=>'oflags'),
            'maps'=>array('status_text'=>$maps['output']['status']),
            ),
//        array('container'=>'inputs', 'fname'=>'option_id', 'fields'=>array('id'=>'input_id', 'input_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['products']) ) {
        $products = $rc['products'];
    } else {
        $products = array();
    }

    $output_ids = array();
    foreach($products as $pid => $product) {
        $products[$pid]['output_ids'] = '';
//        $products[$pid]['input_names'] = str_replace(',', ', ', $product['input_name']);
        $products[$pid]['status_text'] = '';
        $products[$pid]['availability'] = '';
        if( isset($product['outputs']) ) {
            foreach($product['outputs'] as $output) {
                $output_ids[] = $output['id'];
                if( ($product['itype'] == $output['otype'] && $output['otype'] < 50) || ($product['itype'] == 50 && $output['otype'] == 30) ) {
                    $products[$pid]['status_text'] = $output['status_text'];
                }
                if( ($output['flags']&0x0100) == 0x0100 ) {
                    $products[$pid]['availability'] = 'Always';
                } elseif( ($output['flags']&0x0200) == 0x0200 ) {
                    $products[$pid]['availability'] = 'Dates';
                } elseif( ($output['flags']&0x0400) == 0x0400 ) {
                    $products[$pid]['availability'] = 'Queue';
                } elseif( ($output['flags']&0x0800) == 0x0800 ) {
                    $products[$pid]['availability'] = 'Limited';
                }
                $output_ids[] = $output['id'];
                $products[$pid]['output_ids'] .= ($products[$pid]['output_ids'] != '' ? ',' . $output['id'] : '');
            }
//            $products[$pid]['output_ids'] = $output_ids;
        }
//        $output_ids = array_merge($output_ids, explode(',', $product['output_ids']));
    }
    $output_ids = array_unique($output_ids);
    //
    // Get the sales for each product
    //
    if( isset($args['sales']) && $args['sales'] == 'yes' ) {
        
        //
        // Get the sales numbers
        //
        if( count($output_ids) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
            $strsql = "SELECT object_id, COUNT(*) as num_ordered "
                . "FROM ciniki_poma_order_items "
                . "WHERE object = 'ciniki.foodmarket.output' "
                . "AND object_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $output_ids) . ") "
                . "AND DATEDIFF(UTC_TIMESTAMP(), date_added) < 366 "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY object_id "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
            $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
                array('container'=>'items', 'fname'=>'object_id', 'fields'=>array('num_ordered')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.92', 'msg'=>'Unable to load items', 'err'=>$rc['err']));
            }
            if( isset($rc['items']) ) {
                foreach($products as $pid => $product) {
                    $products[$pid]['num_ordered'] = 0;
                    $oids = explode(',', $product['output_ids']);
                        
                    foreach($oids as $output_id) {
                        if( isset($rc['items'][$output_id]['num_ordered']) ) {
                            $products[$pid]['num_ordered'] += $rc['items'][$output_id]['num_ordered'];
                        }
                    }
                }
            }
        }
    }

    $rsp = array('stat'=>'ok', 'products'=>$products);


    //
    // Get the list of categories
    //
    if( isset($args['categories']) && $args['categories'] == 'yes' ) {
        //
        // Get the number of products in each category
        //
        $strsql = "SELECT category_id, COUNT(product_id) AS num "
            . "FROM ciniki_foodmarket_category_items "
            . "WHERE ciniki_foodmarket_category_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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

        //
        // Get the number of products on Special
        //
        $strsql = "SELECT COUNT(DISTINCT ciniki_foodmarket_products.id) "
            . "FROM ciniki_foodmarket_products, ciniki_foodmarket_product_outputs "
            . "WHERE ciniki_foodmarket_products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_foodmarket_products.id = ciniki_foodmarket_product_outputs.product_id "
            . "AND ciniki_foodmarket_product_outputs.retail_sdiscount_percent > 0 "
            . "AND ciniki_foodmarket_product_outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.foodmarket', 'number');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $specials = 0;
        if( isset($rc['number']) && $rc['number'] > 0 ) {
            $specials = $rc['number'];
        }

        //
        // Get the number of products in new
        //
        $strsql = "SELECT COUNT(DISTINCT ciniki_foodmarket_products.id) "
            . "FROM ciniki_foodmarket_products, ciniki_foodmarket_product_outputs "
            . "WHERE ciniki_foodmarket_products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND (ciniki_foodmarket_products.flags&0x01) = 0x01 "
            . "AND ciniki_foodmarket_products.id = ciniki_foodmarket_product_outputs.product_id "
            . "AND ciniki_foodmarket_product_outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.foodmarket', 'number');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $new = 0;
        if( isset($rc['number']) && $rc['number'] > 0 ) {
            $new = $rc['number'];
        }

        $strsql = "SELECT c1.id AS id, c1.ctype, c1.name AS name, "
            . "c2.id AS sub_id, "
            . "c2.name AS sub_name "
            . "FROM ciniki_foodmarket_categories AS c1 "
            . "LEFT JOIN ciniki_foodmarket_categories AS c2 ON ("
                . "c1.id = c2.parent_id "
                . "AND c2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE c1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND c1.parent_id = 0 "
            . "ORDER BY c1.sequence, c1.name, c2.name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
            array('container'=>'parents', 'fname'=>'id', 'fields'=>array('id', 'name', 'ctype')),
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
                if( $parent['ctype'] == 30 ) {
                    $rsp['categories'][] = array(
                        'id'=>$parent['id'], 
                        'name'=>$parent['name'],
                        'fullname'=>$parent['name'],
                        'num_products'=>(isset($specials) ? $specials : '0'),
                        );
                } elseif( $parent['ctype'] == 50 ) {
                    $rsp['categories'][] = array(
                        'id'=>$parent['id'], 
                        'name'=>$parent['name'],
                        'fullname'=>$parent['name'],
                        'num_products'=>(isset($new) ? $new : '0'),
                        );
                } else {
                    $rsp['categories'][] = array(
                        'id'=>$parent['id'], 
                        'name'=>$parent['name'],
                        'fullname'=>$parent['name'],
                        'num_products'=>(isset($category_numbers[$parent['id']]) ? $category_numbers[$parent['id']] : '0'),
                        );
                }
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
        // Check for any products that are currently archived
        //
        $strsql = "SELECT COUNT(ciniki_foodmarket_products.id) "
            . "FROM ciniki_foodmarket_products "
            . "WHERE ciniki_foodmarket_products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_foodmarket_products.status = 90 "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.foodmarket', 'number');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['number']) && $rc['number'] > 0 ) {
            $rsp['categories'][] = array(
                'id'=>-1,
                'name'=>'Archived',
                'num_products'=>$rc['number'],
                );
        }

        //
        // Check for any products that are currently not in a category
        //
        $strsql = "SELECT COUNT(ciniki_foodmarket_products.id) "
            . "FROM ciniki_foodmarket_products "
            . "LEFT JOIN ciniki_foodmarket_category_items ON ("
                . "ciniki_foodmarket_products.id = ciniki_foodmarket_category_items.product_id "
                . "AND ciniki_foodmarket_category_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_foodmarket_products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_foodmarket_products.status < 90 "
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
