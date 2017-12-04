<?php
//
// Description
// -----------
// This function will return the list of options for the module that can be set for the website.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get foodmarket web options for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_foodmarket_hooks_webOptions(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.foodmarket']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.32', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Get the settings from the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_settings', 'tnid', $tnid, 'ciniki.web', 'settings', 'page-foodmarket');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']) ) {
        $settings = array();
    } else {
        $settings = $rc['settings'];
    }


    $poptions = array();
/*    $poptions[] = array(
        'label'=>'Layout',
        'setting'=>'page-foodmarket-products-display-sections', 
        'type'=>'select',
        'value'=>(isset($settings['page-foodmarket-products-display-sections']) ? $settings['page-foodmarket-display-sections'] : 'categories-specials-products'),
        'toggles'=>array(
            array('value'=>'categories-specials-products', 'label'=>'Categories, Specials, Products'),
            array('value'=>'categories-specials', 'label'=>'Categories, Specials'),
            array('value'=>'categories-products', 'label'=>'Categories, Products'),
            array('value'=>'categories', 'label'=>'Categories'),
            ),
        ); 
    $poptions[] = array(
        'label'=>'Category Format',
        'setting'=>'page-foodmarket-products-category-format', 
        'type'=>'toggle',
        'value'=>(isset($settings['page-foodmarket-products-category-format'])?$settings['page-foodmarket-products-category-format']:'thumbnails'),
        'toggles'=>array(
            array('value'=>'thumbnails', 'label'=>'Thumbnails'),
            array('value'=>'list', 'label'=>'List'),
            ),
        ); */
    $poptions[] = array(
        'label'=>'Layout',
        'setting'=>'page-foodmarket-categories-layout', 
        'type'=>'toggle',
        'value'=>(isset($settings['page-foodmarket-categories-layout']) ? $settings['page-foodmarket-categories-layout'] : 'categories'),
        'toggles'=>array(
            array('value'=>'categories', 'label'=>'Category List'),
            array('value'=>'expandsubcategories', 'label'=>'Expand Subcategories'),
            ),
        ); 
    $poptions[] = array(
        'label'=>'Public Prices',
        'setting'=>'page-foodmarket-public-prices', 
        'type'=>'toggle',
        'value'=>(isset($settings['page-foodmarket-public-prices']) ? $settings['page-foodmarket-public-prices'] : 'yes'),
        'toggles'=>array(
            array('value'=>'no', 'label'=>'No'),
            array('value'=>'yes', 'label'=>'Yes'),
            ),
        ); 
    $poptions[] = array(
        'label'=>'Category Format',
        'setting'=>'page-foodmarket-category-thumbnail-format', 
        'type'=>'toggle',
        'value'=>(isset($settings['page-foodmarket-category-thumbnail-format']) ? $settings['page-foodmarket-category-thumbnail-format'] : 'square-cropped'),
        'toggles'=>array(
            array('value'=>'square-cropped', 'label'=>'Cropped'),
            array('value'=>'square-padded', 'label'=>'Padded'),
            ),
        ); 
    $poptions[] = array(
        'label'=>'Thumbnail Padding Color',
        'setting'=>'page-foodmarket-category-thumbnail-padding-color',
        'type'=>'colour',
        'value'=>(isset($settings['page-foodmarket-category-thumbnail-padding-color'])?$settings['page-foodmarket-category-thumbnail-padding-color']:'#ffffff'),
        );

    $poptions[] = array(
        'label'=>'Product Format',
        'setting'=>'page-foodmarket-products-thumbnail-format', 
        'type'=>'toggle',
        'value'=>(isset($settings['page-foodmarket-products-thumbnail-format']) ? $settings['page-foodmarket-products-thumbnail-format'] : 'square-cropped'),
        'toggles'=>array(
            array('value'=>'square-cropped', 'label'=>'Cropped'),
            array('value'=>'square-padded', 'label'=>'Padded'),
            ),
        ); 
    $poptions[] = array(
        'label'=>'Thumbnail Padding Color',
        'setting'=>'page-foodmarket-products-thumbnail-padding-color',
        'type'=>'colour',
        'value'=>(isset($settings['page-foodmarket-products-thumbnail-padding-color'])?$settings['page-foodmarket-products-thumbnail-padding-color']:'#ffffff'),
        );

    $pages['ciniki.foodmarket.products'] = array('name'=>'Food Market Products', 'options'=>$poptions);

    return array('stat'=>'ok', 'pages'=>$pages);
}
?>
