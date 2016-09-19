<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_foodmarket_objects($ciniki) {
    
    $objects = array();
    $objects['product'] = array(
        'name'=>'Product',
        'sync'=>'yes',
        'o_name'=>'product',
        'o_container'=>'products',
        'table'=>'ciniki_foodmarket_products',
        'fields'=>array(
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink'),
            'status'=>array('name'=>'Status', 'default'=>'10'),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'category'=>array('name'=>'Category', 'default'=>''),
            'primary_image_id'=>array('name'=>'Primary Image', 'default'=>'0'),
            'synopsis'=>array('name'=>'Synopsis', 'default'=>''),
            'description'=>array('name'=>'Description', 'default'=>''),
            'ingredients'=>array('name'=>'Ingredients', 'default'=>''),
            'supplier_id'=>array('name'=>'Supplier', 'ref'=>'ciniki.foodmarket.supplier', 'default'=>'0'),
            ),
        'history_table'=>'ciniki_foodmarket_history',
        );
    $objects['productversion'] = array(
        'name'=>'Product Version',
        'sync'=>'yes',
        'o_name'=>'version',
        'o_container'=>'versions',
        'table'=>'ciniki_foodmarket_product_versions',
        'fields'=>array(
            'product_id'=>array('name'=>'Product', 'ref'=>'ciniki.foodmarket.product'),
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink'),
            'status'=>array('name'=>'Status', 'default'=>'10'),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'sequence'=>array('name'=>'Order', 'default'=>'1'),
            'recipe_id'=>array('name'=>'Recipe', 'ref'=>'ciniki.foodmarket.recipe', 'default'=>'0'),
            'recipe_quantity'=>array('name'=>'Recipe Quantity', 'default'=>'1'),
            'container_id'=>array('name'=>'Container', 'ref'=>'ciniki.foodmarket.container', 'default'=>'0'),
            'materials_cost_per_container'=>array('name'=>'Materials Cost', 'default'=>'0'),
            'time_cost_per_container'=>array('name'=>'Time Cost', 'default'=>'0'),
            'total_cost_per_container'=>array('name'=>'Total Cost', 'default'=>'0'),
            'total_time_per_container'=>array('name'=>'Total Time', 'default'=>'0'),
            'inventory'=>array('name'=>'Inventory', 'default'=>'0'),
            'supplier_price'=>array('name'=>'Supplier Price', 'default'=>'0'),
            'wholesale_price'=>array('name'=>'Wholesale Price', 'default'=>'0'),
            'basket_price'=>array('name'=>'Basket Price', 'default'=>'0'),
            'retail_price'=>array('name'=>'Retail Price', 'default'=>'0'),
            ),
        'history_table'=>'ciniki_foodmarket_history',
        );
    $objects['category'] = array(
        'name'=>'Category',
        'sync'=>'yes',
        'o_name'=>'category',
        'o_container'=>'categories',
        'table'=>'ciniki_foodmarket_categories',
        'fields'=>array(
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink'),
            'parent_id'=>array('name'=>'Parent Category', 'default'=>'0'),
            'sequence'=>array('name'=>'Order', 'default'=>'1'),
            'image_id'=>array('name'=>'Image', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'synopsis'=>array('name'=>'Synopsis', 'default'=>''),
            'description'=>array('name'=>'Description', 'default'=>''),
            ),
        'history_table'=>'ciniki_foodmarket_history',
        );
    $objects['categoryitem'] = array(
        'name'=>'Category Item',
        'sync'=>'yes',
        'o_name'=>'categoryitem',
        'o_container'=>'categoryitems',
        'table'=>'ciniki_foodmarket_category_items',
        'fields'=>array(
            'category_id'=>array('name'=>'Category'),
            'ref_object'=>array('name'=>'Object'),
            'ref_id'=>array('name'=>'ID'),
            ),
        'history_table'=>'ciniki_foodmarket_history',
        );
    $objects['orderdate'] = array(
        'name'=>'Order Date',
        'sync'=>'yes',
        'o_name'=>'orderdate',
        'o_container'=>'orderdates',
        'table'=>'ciniki_foodmarket_order_dates',
        'fields'=>array(
            'order_date'=>array('name'=>'Order Date'),
            'status'=>array('name'=>'Status', 'default'=>'10'),
            'flags'=>array('flags'=>'Options', 'default'=>'0'),
            'change_deadline'=>array('name'=>'Change Deadline', 'default'=>''),
            'notices'=>array('name'=>'Notices', 'default'=>''),
            ),
        'history_table'=>'ciniki_foodmarket_history',
        );
    $objects['supplier'] = array(
        'name'=>'Supplier',
        'sync'=>'yes',
        'o_name'=>'supplier',
        'o_container'=>'suppliers',
        'table'=>'ciniki_foodmarket_suppliers',
        'fields'=>array(
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink'),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'category'=>array('name'=>'Category', 'default'=>''),
            'primary_image_id'=>array('name'=>'Image', 'default'=>'0'),
            'synopsis'=>array('name'=>'Synopsis', 'default'=>''),
            'description'=>array('name'=>'Description', 'default'=>''),
            'contact_name'=>array('name'=>'Contact Name', 'default'=>''),
            'contact_email'=>array('name'=>'Contact Email', 'default'=>''),
            'contact_phone'=>array('name'=>'Contact Phone', 'default'=>''),
            'contact_cell'=>array('name'=>'Contact Cell', 'default'=>''),
            ),
        'history_table'=>'ciniki_foodmarket_history',
        );
    $objects['tag'] = array(
        'name'=>'Tag',
        'sync'=>'yes',
        'o_name'=>'tag',
        'o_container'=>'tags',
        'table'=>'ciniki_foodmarket_tags',
        'fields'=>array(
            'ref_object'=>array('name'=>'Object'),
            'ref_id'=>array('name'=>'ID'),
            'tag_type'=>array('name'=>'Type'),
            'tag_name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink'),
            ),
        'history_table'=>'ciniki_foodmarket_history',
        );
    $objects['setting'] = array(
        'type'=>'settings',
        'name'=>'Food Market Settings',
        'table'=>'ciniki_foodmarket_settings',
        'history_table'=>'ciniki_foodmarket_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
