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
            'ptype'=>array('name'=>'Type'),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'legend_codes'=>array('name'=>'Legend Codes', 'default'=>''),
            'legend_names'=>array('name'=>'Legend Names', 'default'=>''),
            'category'=>array('name'=>'Category', 'default'=>''),
            'packing_order'=>array('name'=>'Packing Order', 'default'=>'10'),
            'primary_image_id'=>array('name'=>'Primary Image', 'default'=>'0'),
            'synopsis'=>array('name'=>'Synopsis', 'default'=>''),
            'description'=>array('name'=>'Description', 'default'=>''),
            'ingredients'=>array('name'=>'Ingredients', 'default'=>''),
            'supplier_id'=>array('name'=>'Supplier', 'ref'=>'ciniki.foodmarket.supplier', 'default'=>'0'),
            ),
        'history_table'=>'ciniki_foodmarket_history',
        );
    $objects['input'] = array(
        'name'=>'Product Input',
        'sync'=>'yes',
        'o_name'=>'input',
        'o_container'=>'inputs',
        'table'=>'ciniki_foodmarket_product_inputs',
        'fields'=>array(
            'product_id'=>array('name'=>'Product', 'ref'=>'ciniki.foodmarket.product'),
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink'),
            'status'=>array('name'=>'Status', 'default'=>'10'),
            'itype'=>array('name'=>'Type'),
            'units'=>array('name'=>'Units'),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'sequence'=>array('name'=>'Order', 'default'=>'1'),
            'case_cost'=>array('name'=>'Case Cost', 'type'=>'currency', 'default'=>'0'),
            'half_cost'=>array('name'=>'Half Cost', 'type'=>'currency', 'default'=>'0'),
            'unit_cost'=>array('name'=>'Unit Cost', 'type'=>'currency', 'default'=>'0'),
            'case_units'=>array('name'=>'Units/Case', 'default'=>'1'),
            'min_quantity'=>array('name'=>'Minimum Order Quantity', 'default'=>'1'),
            'inc_quantity'=>array('name'=>'Increment Order Quantity', 'default'=>'1'),
            'cdeposit_name'=>array('name'=>'Container Deposit Text', 'default'=>''),
            'cdeposit_amount'=>array('name'=>'Container Deposit', 'default'=>'0'),
            'sku'=>array('name'=>'Sku/Code', 'default'=>''),
            'inventory'=>array('name'=>'Inventory', 'default'=>'0'),
            'recipe_id'=>array('name'=>'Recipe', 'ref'=>'ciniki.foodmarket.recipe', 'default'=>'0'),
            'recipe_quantity'=>array('name'=>'Recipe Quantity', 'default'=>'1'),
            'container_id'=>array('name'=>'Container', 'ref'=>'ciniki.foodmarket.container', 'default'=>'0'),
            'materials_cost_per_container'=>array('name'=>'Materials Cost', 'default'=>'0'),
            'time_cost_per_container'=>array('name'=>'Time Cost', 'default'=>'0'),
            'total_cost_per_container'=>array('name'=>'Total Cost', 'default'=>'0'),
            'total_time_per_container'=>array('name'=>'Total Time', 'default'=>'0'),
            ),
        'history_table'=>'ciniki_foodmarket_history',
        );
    $objects['output'] = array(
        'name'=>'Product Output',
        'sync'=>'yes',
        'o_name'=>'output',
        'o_container'=>'outputs',
        'table'=>'ciniki_foodmarket_product_outputs',
        'fields'=>array(
            'product_id'=>array('name'=>'Product', 'ref'=>'ciniki.foodmarket.product'),
            'input_id'=>array('name'=>'Input', 'ref'=>'ciniki.foodmarket.input', 'default'=>'0'),
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink'),
            'pio_name'=>array('name'=>'Full Name', 'default'=>''),
            'io_name'=>array('name'=>'Input/Output Name', 'default'=>''),
            'keywords'=>array('name'=>'Keywords', 'default'=>''),
            'status'=>array('name'=>'Status', 'default'=>'10'),
            'otype'=>array('name'=>'Type'),
            'units'=>array('name'=>'Units'),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'sequence'=>array('name'=>'Order', 'default'=>'1'),
            'io_sequence'=>array('name'=>'Input/Output Order', 'default'=>1),
            'start_date'=>array('name'=>'Start Date', 'default'=>''),
            'end_date'=>array('name'=>'End Date', 'default'=>''),
            'wholesale_percent'=>array('name'=>'Wholesale Percent', 'default'=>'0'),
            'wholesale_price'=>array('name'=>'Wholesale Price', 'default'=>'0'),
            'wholesale_taxtype_id'=>array('name'=>'Wholesale Taxtype', 'default'=>'0'),
            'retail_percent'=>array('name'=>'Retail Percent', 'default'=>'0'),
            'retail_price'=>array('name'=>'Retail Price', 'default'=>'0'),
            'retail_price_text'=>array('name'=>'Retail Price Text', 'default'=>''),
            'retail_sdiscount_percent'=>array('name'=>'Retail Special Discount', 'default'=>'0'),
            'retail_sprice'=>array('name'=>'Retail Price', 'default'=>'0'),
            'retail_sprice_text'=>array('name'=>'Retail Price Text', 'default'=>''),
            'retail_deposit'=>array('name'=>'Retail Deposit', 'default'=>'0'),
            'retail_taxtype_id'=>array('name'=>'Retail Taxtype', 'default'=>'0'),
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
            'ctype'=>array('name'=>'Type', 'default'=>'0'),
            'sequence'=>array('name'=>'Order', 'default'=>'1'),
            'flags'=>array('name'=>'Options', 'default'=>''),
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
            'product_id'=>array('name'=>'Product'),
            ),
        'history_table'=>'ciniki_foodmarket_history',
        );
    $objects['legend'] = array(
        'name'=>'Legend',
        'sync'=>'yes',
        'o_name'=>'legend',
        'o_container'=>'legends',
        'table'=>'ciniki_foodmarket_legends',
        'fields'=>array(
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink'),
            'code'=>array('name'=>'Code'),
            'image_id'=>array('name'=>'Image', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'synopsis'=>array('name'=>'Synopsis', 'default'=>''),
            'description'=>array('name'=>'Description', 'default'=>''),
            ),
        'history_table'=>'ciniki_foodmarket_history',
        );
    $objects['legenditem'] = array(
        'name'=>'Legend Item',
        'sync'=>'yes',
        'o_name'=>'legenditem',
        'o_container'=>'legenditems',
        'table'=>'ciniki_foodmarket_legend_items',
        'fields'=>array(
            'legend_id'=>array('name'=>'Legend'),
            'product_id'=>array('name'=>'Product'),
            ),
        'history_table'=>'ciniki_foodmarket_history',
        );
    $objects['dateitem'] = array(
        'name'=>'Order Date Item',
        'sync'=>'yes',
        'o_name'=>'dateitem',
        'o_container'=>'dateitems',
        'table'=>'ciniki_foodmarket_date_items',
        'fields'=>array(
            'date_id'=>array('name'=>'Category', 'ref'=>'ciniki.poma.orderdate'),
            'output_id'=>array('name'=>'Product', 'ref'=>'ciniki.foodmarket.output'),
            'quantity'=>array('name'=>'Quantity', 'default'=>'0'),
            ),
        'history_table'=>'ciniki_foodmarket_history',
        );
    $objects['basketitem'] = array(
        'name'=>'Order Date Basket Item',
        'sync'=>'yes',
        'o_name'=>'basketitem',
        'o_container'=>'basketitems',
        'table'=>'ciniki_foodmarket_basket_items',
        'fields'=>array(
            'basket_output_id'=>array('name'=>'Basket', 'ref'=>'ciniki.foodmarket.output'),
            'date_id'=>array('name'=>'Category', 'ref'=>'ciniki.poma.orderdate'),
            'item_output_id'=>array('name'=>'Product', 'ref'=>'ciniki.foodmarket.output'),
            'quantity'=>array('name'=>'Quantity', 'default'=>'0'),
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
            'code'=>array('name'=>'Code', 'default'=>''),
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
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
