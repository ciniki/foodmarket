<?php
//
// Description
// -----------
// This function will process a web request for the food market module.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get events for.
//
// args:            The possible arguments for products
//
//
// Returns
// -------
//
function ciniki_foodmarket_web_processRequestSlideshow(&$ciniki, $settings, $tnid, $args) {

    $page = array(
        'title'=>'Slideshow',
        'breadcrumbs'=>$args['breadcrumbs'],
        'blocks'=>array(),
        );

    $uri_split = $args['uri_split'];
    if( isset($uri_split[0]) && $uri_split[0] == 'slideshow' ) {
        array_shift($uri_split);
    }
    if( !isset($uri_split[0]) || $uri_split[0] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.145', 'msg'=>'No slideshow specified'));
    }
    $slideshow_permalink = $uri_split[0];

    //
    // Get the slideshow details
    //
    $strsql = "SELECT id, name, permalink, type, effect, speed, flags, slides "
        . "FROM ciniki_foodmarket_slideshows "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $slideshow_permalink) . "' "
        . "AND (flags&0x01) = 0x01 "    // Visible
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.foodmarket', 'slideshow');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.79', 'msg'=>'Unable to load slideshow', 'err'=>$rc['err']));
    }
    if( !isset($rc['slideshow']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.80', 'msg'=>'Slideshow does not exist'));
    }
    $slideshow = $rc['slideshow'];
    $slides = unserialize($slideshow['slides']);
    $slider_pause_time = ($slideshow['speed'] * 1000);

    //
    // Check if products should be loaded from categories
    //
    $products = array();
    if( isset($slides['categories']) && $slides['categories'] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
        //
        // Load the products
        //
        $strsql = "SELECT p.id, "
            . "p.uuid, "
            . "p.primary_image_id AS image_id, "
            . "p.name, "
            . "p.synopsis, "
            . "p.ingredients, "
            . "o.id AS oid, "
            . "o.io_name, "
            . "o.retail_price_text AS price_text "
            . "FROM ciniki_foodmarket_categories AS c "
            . "LEFT JOIN ciniki_foodmarket_category_items AS ci ON ("
                . "c.id = ci.category_id "
                . "AND ci.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_products AS p ON ("
                . "ci.product_id = p.id "
                . "AND p.status = 40 "
                . "AND p.primary_image_id > 0 "
                . "AND p.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_inputs AS i ON ("
                . "p.id = i.product_id "
                . "AND i.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_outputs AS o ON ("
                . "i.id = o.input_id "
                . "AND p.id = o.product_id "
                . "AND o.status = 40 "
                . "AND o.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE c.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND c.id IN (" . ciniki_core_dbQuoteIDs($ciniki, explode(',', $slides['categories'])) . ") "
            . "ORDER BY rand() "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
            array('container'=>'products', 'fname'=>'id', 'fields'=>array('id', 'uuid', 'image_id', 'name', 'synopsis', 'ingredients')),
            array('container'=>'outputs', 'fname'=>'oid', 'fields'=>array('id'=>'oid', 'uuid', 'io_name', 'price_text')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.146', 'msg'=>'', 'err'=>$rc['err']));
        }
        if( !isset($rc['products']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.144', 'msg'=>''));
        }
        $products = $rc['products'];
        
    }

    if( isset($slides['allproducts']) && $slides['allproducts'] == 'yes' ) {
        //
        // Load the products
        //
        $strsql = "SELECT p.id, "
            . "p.uuid, "
            . "p.primary_image_id AS image_id, "
            . "p.name, "
            . "p.synopsis, "
            . "p.ingredients, "
            . "o.id AS oid, "
            . "o.io_name, "
            . "o.retail_price_text AS price_text "
            . "FROM ciniki_foodmarket_products AS p "
            . "LEFT JOIN ciniki_foodmarket_product_inputs AS i ON ("
                . "p.id = i.product_id "
                . "AND i.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_foodmarket_product_outputs AS o ON ("
                . "i.id = o.input_id "
                . "AND p.id = o.product_id "
                . "AND o.status = 40 "
                . "AND o.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE p.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND p.status = 40 "
            . "AND p.primary_image_id > 0 "
            . "ORDER BY rand() "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
            array('container'=>'products', 'fname'=>'id', 'fields'=>array('id', 'uuid', 'image_id', 'name', 'synopsis', 'ingredients')),
            array('container'=>'outputs', 'fname'=>'oid', 'fields'=>array('id'=>'oid', 'uuid', 'io_name', 'price_text')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.81', 'msg'=>'', 'err'=>$rc['err']));
        }
        if( !isset($rc['products']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.82', 'msg'=>''));
        }
        $products = $rc['products'];
    }


    $content = "<div id='slideshow' class='slideshow'>";

    $c = 0;
    foreach($products as $product) {
        if( !isset($product['outputs']) ) {
            continue;
        }
        $content .= "<div id='slideshow-$c' class='slideshow-slide" . ($c == 0 ?' slideshow-slide-activ':'') . " slideshow-fullscreen'>";
        $content .= "<div class='slideshow-image'>";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
        $rc = ciniki_web_getScaledImageURL($ciniki, $product['image_id'], 'original', '1024', 0);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $ciniki['response']['head']['og']['image'] = $rc['domain_url'];
        $content .= "<img src='" . $rc['url'] . "'>";
        
        $content .= "</div>";
        $content .= "<div class='slideshow-content'>";
            $content .= "<div class='slideshow-content-title'>";
            $content .= $product['name'];
            $content .= "</div>";
            $content .= "<div class='slideshow-content-options'>";
            foreach($product['outputs'] as $output) {
                $content .= "<div class='slideshow-content-option'>";
                $content .= "<span class='slideshow-option-label'>" . $output['io_name'] . "</span>"; 
                $content .= "<span class='slideshow-option-price'>" . $output['price_text'] . "</span>"; 
                $content .= "</div>";
            }
        $content .= "</div>";
        $content .= "</div>";
        $content .= "</div>";

//        if( $c > 10 ) { break; }
        $c++;
    }

    $content .= "</div>";
    $content .= "<style>html {overflow:hidden;}</style>";

    //
    // Set the page to be fullscreen when run as webapp
    //
    $ciniki['response']['web-app'] = 'yes';

    $ciniki['request']['inline_javascript'] = "<script type='text/javascript'>"
        . "var cur_slide = 0;"
/*        . "function startSlideshow() {"
            . "var el = document.getElementById('slideshow'),"
            . "rfs = el.requestFullscreen"
                . "|| el.webkitRequestFullScreen"
                . "|| el.mozRequestFullScreen"
                . "|| el.msRequestFullscreen "
            . ";"
            . "el.classList.add('slideshow-fullscreen');"
            . "rfs.call(el);"
            . "slider_timer = setInterval(nextSlide, $slider_pause_time);"
        . "}" */
        . "function startSlideshow() {"
            . "var e=document.getElementById('content');"
            . "e.style.height = (window.innerHeight) + 'px';"
            . "var e=document.getElementById('slideshow-'+(cur_slide+1));"
            . "if(e!=null){e.style.height = (window.innerHeight) + 'px';};"
            . "if(e.children[0]!=null){e.children[0].style.height = (window.innerHeight-20) + 'px';};"
            . "nextSlide();"
            . "slider_timer = setInterval(nextSlide, $slider_pause_time);"
        . "}"
        . "function nextSlide() {"
            . "var e=document.getElementById('slideshow-'+(cur_slide+1));"
            . "if(e!=null){e.style.height = (window.innerHeight) + 'px';};"
            . "var e=document.getElementById('slideshow-'+cur_slide);"
            . "e.classList.remove('slideshow-slide-active');"
            . "cur_slide++;"
            . "if( cur_slide >= $c ) { cur_slide = 0; }"
            . "var e=document.getElementById('slideshow-'+cur_slide);"
            . "e.classList.add('slideshow-slide-active');"
            . "e.style.height = (window.innerHeight) + 'px';"
            . "if(e.children[0]!=null){e.children[0].style.height = (window.innerHeight-20) + 'px';};"
        . "}"
//        . "window.onload = function() {slider_timer = setInterval(nextSlide, $slider_pause_time);}"
        . "window.onload = function() {startSlideshow();}"
        . "</script>";

    $page['fullscreen-content'] = 'yes';
    $page['blocks'][] = array('type'=>'content', 'html'=>$content);

    return array('stat'=>'ok', 'page'=>$page);
}
?>
