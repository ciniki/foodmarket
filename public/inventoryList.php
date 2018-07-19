<?php
//
// Description
// -----------
// This method will list the products with inventory tracked on them.
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
function ciniki_foodmarket_inventoryList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'category_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'),
        'input_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Input'),
        'addq'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Quantity to add'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['tnid'], 'ciniki.foodmarket.inventoryList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'core', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Check if adjustment to inventory
    //
    if( isset($args['input_id']) && $args['input_id'] > 0 && isset($args['addq']) && $args['addq'] != 0 ) {
        //
        // Get the current inventory
        //
        $strsql = "SELECT id, inventory "
            . "FROM ciniki_foodmarket_product_inputs "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['input_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.126', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
        }
        if( isset($rc['item']) ) {
            $item = $rc['item'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.foodmarket.input', $args['input_id'], 
                array('inventory'=>($item['inventory'] + $args['addq'])), 0x07);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.127', 'msg'=>'Unable to update inventory', 'err'=>$rc['err']));
            }
        }
    }

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
        $strsql = "SELECT products.id, "
            . "products.name, "
            . "products.permalink, "
            . "products.status, "
            . "products.flags, "
            . "products.supplier_id, "
            . "inputs.id AS input_id, "
            . "inputs.name AS input_name, "
            . "inputs.inventory, "
            . "outputs.retail_sdiscount_percent, "
            . "IFNULL(suppliers.code, '') AS supplier_code, "
            . "IFNULL(suppliers.name, '') AS supplier_name, "
            . "IFNULL(outputs.id, 0) AS output_ids "
            . "FROM ciniki_foodmarket_products AS products "
            . "INNER JOIN ciniki_foodmarket_product_inputs AS inputs ON ("
                . "products.id = inputs.product_id "
                . "AND (inputs.flags&0x02) = 0x02 "
                . "AND inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_outputs AS outputs ON ("
                . "products.id = outputs.product_id "
                . "AND outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_suppliers AS suppliers ON ("
                . "products.supplier_id = ciniki_foodmarket_suppliers.id "
                . "AND suppliers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND outputs.retail_sdiscount_percent > 0 "
            . "ORDER BY products.name, inputs.name "
            . "";

    } elseif( isset($args['category_id']) && $args['category_id'] != '' && $args['category_id'] > 0 && $ctype == 50 ) {
        $strsql = "SELECT products.id, "
            . "products.name, "
            . "products.permalink, "
            . "products.status, "
            . "products.flags, "
            . "products.supplier_id, "
            . "inputs.id AS input_id, "
            . "inputs.name AS input_name, "
            . "inputs.inventory, "
            . "outputs.retail_sdiscount_percent, "
            . "IFNULL(suppiers.code, '') AS supplier_code, "
            . "IFNULL(suppiers.name, '') AS supplier_name, "
            . "IFNULL(outputs.id, 0) AS output_ids "
            . "FROM ciniki_foodmarket_products AS products "
            . "INNER JOIN ciniki_foodmarket_product_inputs AS inputs ON ("
                . "products.id = inputs.product_id "
                . "AND (inputs.flags&0x02) = 0x02 "
                . "AND inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_outputs AS outputs ON ("
                . "products.id = outputs.product_id "
                . "AND outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_suppliers AS suppliers ON ("
                . "products.supplier_id = suppiers.id "
                . "AND suppiers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND (products.flags&0x01) = 0x01 "
            . "ORDER BY products.name, ciniki_foodmarket_product_inputs.name "
            . "";

    } elseif( isset($args['category_id']) && $args['category_id'] != '' && $args['category_id'] > 0 ) {
        $strsql = "SELECT products.id, "
            . "products.name, "
            . "products.permalink, "
            . "products.status, "
            . "products.flags, "
            . "products.supplier_id, "
            . "inputs.id AS input_id, "
            . "inputs.name AS input_name, "
            . "inputs.inventory, "
            . "IFNULL(suppliers.code, '') AS supplier_code, "
            . "IFNULL(suppliers.name, '') AS supplier_name, "
            . "IFNULL(outputs.id, 0) AS output_ids "
            . "FROM ciniki_foodmarket_category_items AS items "
            . "INNER JOIN ciniki_foodmarket_products AS products ON ("
                . "items.product_id = products.id "
                . "AND products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_foodmarket_product_inputs AS inputs ON ("
                . "products.id = inputs.product_id "
                . "AND (inputs.flags&0x02) = 0x02 "
                . "AND inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_outputs AS outputs ON ("
                . "products.id = outputs.product_id "
                . "AND outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_suppliers AS suppliers ON ("
                . "products.supplier_id = suppliers.id "
                . "AND suppliers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND items.category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
            . "ORDER BY products.name, inputs.name "
            . "";
    } elseif( isset($args['category_id']) && $args['category_id'] != '' && $args['category_id'] == 0 ) {
        $strsql = "SELECT products.id, "
            . "products.name, "
            . "products.permalink, "
            . "products.status, "
            . "products.flags, "
            . "products.supplier_id, "
            . "inputs.id AS input_id, "
            . "inputs.name AS input_name, "
            . "inputs.inventory, "
            . "IFNULL(suppliers.code, '') AS supplier_code, "
            . "IFNULL(suppliers.name, '') AS supplier_name, "
            . "IFNULL(outputs.id, 0) AS output_ids "
            . "FROM ciniki_foodmarket_products AS products "
            . "INNER JOIN ciniki_foodmarket_product_inputs AS inputs ON ("
                . "products.id = inputs.product_id "
                . "AND (inputs.flags&0x02) = 0x02 "
                . "AND inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_category_items AS items ON ("
                . "products.id = items.product_id "
                . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_outputs AS outputs ON ("
                . "products.id = outputs.product_id "
                . "AND outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_suppliers AS suppliers ON ("
                . "products.supplier_id = suppliers.id "
                . "AND suppliers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND items.id IS NULL "
            . "ORDER BY products.name, inputs.name "
            . "";
    } elseif( isset($args['category_id']) && $args['category_id'] == -1 ) {
        $strsql = "SELECT products.id, "
            . "products.name, "
            . "products.permalink, "
            . "products.status, "
            . "products.flags, "
            . "products.supplier_id, "
            . "inputs.id AS input_id, "
            . "inputs.name AS input_name, "
            . "inputs.inventory, "
            . "IFNULL(suppliers.code, '') AS supplier_code, "
            . "IFNULL(suppliers.name, '') AS supplier_name, "
            . "IFNULL(outputs.id, 0) AS output_ids "
            . "FROM ciniki_foodmarket_products AS products "
            . "INNER JOIN ciniki_foodmarket_product_inputs AS inputs ON ("
                . "products.id = inputs.product_id "
                . "AND (inputs.flags&0x02) = 0x02 "
                . "AND inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_outputs AS outputs ON ("
                . "products.id = outputs.product_id "
                . "AND outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_suppliers AS suppliers ON ("
                . "products.supplier_id = suppliers.id "
                . "AND suppliers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND products.status = 90 "
            . "ORDER BY products.name, inputs.name "
            . "";
    } else {
        $strsql = "SELECT products.id, "
            . "products.name, "
            . "products.permalink, "
            . "products.status, "
            . "products.flags, "
            . "products.supplier_id, "
            . "inputs.id AS input_id, "
            . "inputs.name AS input_name, "
            . "inputs.inventory, "
            . "IFNULL(suppliers.code, '') AS supplier_code, "
            . "IFNULL(suppliers.name, '') AS supplier_name, "
            . "IFNULL(outputs.id, 0) AS output_ids "
            . "FROM ciniki_foodmarket_products AS products "
            . "INNER JOIN ciniki_foodmarket_product_inputs AS inputs ON ("
                . "products.id = inputs.product_id "
                . "AND (inputs.flags&0x02) = 0x02 "
                . "AND inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_outputs AS outputs ON ("
                . "products.id = outputs.product_id "
                . "AND outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_suppliers AS suppliers ON ("
                . "products.supplier_id = suppliers.id "
                . "AND suppliers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY products.date_added DESC, inputs.name "
            . "LIMIT 25 "
            . "";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'products', 'fname'=>'input_id', 
            'fields'=>array('id', 'name', 'input_id', 'input_name', 'permalink', 'status', 'flags', 'inventory',
                'supplier_id', 'supplier_code', 'supplier_name', 'output_ids'),
            'lists'=>array('output_ids'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $products = isset($rc['products']) ? $products = $rc['products'] : array();

    $output_ids = array();
    foreach($products as $pid => $product) {
        $output_ids = array_merge($output_ids, explode(',', $product['output_ids']));
        $products[$pid]['inventory'] = (float)$products[$pid]['inventory'];
    }
    $output_ids = array_unique($output_ids);

    //
    // Get the order numbers for each product output and join them into the inputs
    //
    if( count($output_ids) > 0 ) {
        $dt = new DateTime('now', new DateTimezone($intl_timezone));
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
        $strsql = "SELECT items.object_id, SUM(items.unit_quantity) as num_ordered "
            . "FROM ciniki_poma_order_items AS items "
            . "INNER JOIN ciniki_poma_orders AS orders ON ("
                . "items.order_id = orders.id "
                . "AND orders.status < 50 "
                . "AND orders.order_date >= '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' "
                . "AND orders.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE items.object = 'ciniki.foodmarket.output' "
            . "AND items.object_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $output_ids) . ") "
            . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY items.object_id "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.foodmarket', array(
            array('container'=>'items', 'fname'=>'object_id', 'fields'=>array('num_ordered')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.93', 'msg'=>'Unable to load items', 'err'=>$rc['err']));
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
                $products[$pid]['num_available'] = $products[$pid]['inventory'] - $products[$pid]['num_ordered'];
            }
        }
    }

    $rsp = array('stat'=>'ok', 'inventory_products'=>$products);


    //
    // Get the list of categories
    //
    if( isset($args['categories']) && $args['categories'] == 'yes' ) {
        //
        // Get the number of products in each category
        //
        $strsql = "SELECT items.category_id, IFNULL(COUNT(inputs.product_id), 0) AS num "
            . "FROM ciniki_foodmarket_category_items AS items "
            . "LEFT JOIN ciniki_foodmarket_product_inputs AS inputs ON ("
                . "items.product_id = inputs.product_id "
                . "AND (inputs.flags&0x02) = 0x02 "     // Inventory Tracking enabled for product input
                . "AND inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY items.category_id "
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
        $strsql = "SELECT COUNT(DISTINCT products.id) "
            . "FROM ciniki_foodmarket_products AS products, ciniki_foodmarket_product_inputs, ciniki_foodmarket_product_outputs "
            . "WHERE products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND products.id = ciniki_foodmarket_product_inputs.product_id "
            . "AND (ciniki_foodmarket_product_inputs.flags&0x02) = 0x02 "
            . "AND products.id = ciniki_foodmarket_product_outputs.product_id "
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
        $strsql = "SELECT COUNT(DISTINCT products.id) "
            . "FROM ciniki_foodmarket_products AS products, ciniki_foodmarket_product_inputs, ciniki_foodmarket_product_outputs "
            . "WHERE products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND (products.flags&0x01) = 0x01 "
            . "AND products.id = ciniki_foodmarket_product_inputs.product_id "
            . "AND (ciniki_foodmarket_product_inputs.flags&0x02) = 0x02 "
            . "AND products.id = ciniki_foodmarket_product_outputs.product_id "
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

/*        //
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
        } */

        //
        // Check for any products that are currently not in a category
        //
        $strsql = "SELECT COUNT(products.id) "
            . "FROM ciniki_foodmarket_products AS products "
            . "INNER JOIN ciniki_foodmarket_product_inputs AS inputs ON ("
                . "products.id = inputs.product_id "
                . "AND (inputs.flags&0x02) = 0x02 "
                . "AND inputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_category_items ON ("
                . "products.id = ciniki_foodmarket_category_items.product_id "
                . "AND ciniki_foodmarket_category_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND products.status < 90 "
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
