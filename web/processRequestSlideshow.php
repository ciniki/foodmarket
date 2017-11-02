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
// business_id:     The ID of the business to get events for.
//
// args:            The possible arguments for products
//
//
// Returns
// -------
//
function ciniki_foodmarket_web_processRequestSlideshow(&$ciniki, $settings, $business_id, $args) {

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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.84', 'msg'=>'No slideshow specified'));
    }
    $slideshow_permalink = $uri_split[0];

    //
    // Get the slideshow details
    //
    $strsql = "SELECT id, name, permalink, type, effect, speed, flags "
        . "FROM ciniki_foodmarket_slideshows "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
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
            . "AND i.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "LEFT JOIN ciniki_foodmarket_product_outputs AS o ON ("
            . "i.id = o.input_id "
            . "AND p.id = o.product_id "
            . "AND o.status = 40 "
            . "AND o.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "WHERE p.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
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


    $content = "<div id='slideshow' class='slideshow'>";

    $c = 0;
    foreach($products as $product) {
        if( !isset($product['outputs']) ) {
            continue;
        }
        $content .= "<div id='slideshow-$c' class='slideshow-slide" . ($c == 0 ?' slideshow-slide-active':'') . "'>";
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
    $content .= "<button onclick='startSlideshow();'>Start Slideshow</button>";

    $slider_pause_time = 4500;
    $content .= "<script type='text/javascript'>"
        . "var cur_slide = 0;"
        . "function startSlideshow() {"
            . "var el = document.getElementById('slideshow'),"
            . "rfs = el.requestFullscreen"
                . "|| el.webkitRequestFullScreen"
                . "|| el.mozRequestFullScreen"
                . "|| el.msRequestFullscreen "
            . ";"
            . "el.classList.add('slideshow-fullscreen');"
            . "rfs.call(el);"
            . "slider_timer = setInterval(nextSlide, $slider_pause_time);"
        . "}"
        . "function nextSlide() {"
            . "var e=document.getElementById('slideshow-'+cur_slide);"
            . "e.classList.remove('slideshow-slide-active');"
            . "cur_slide++;"
            . "if( cur_slide > $c ) { cur_slide = 0; }"
            . "var e=document.getElementById('slideshow-'+cur_slide);"
            . "e.classList.add('slideshow-slide-active');"
        . "}"
        . "</script>";

    $page['blocks'][] = array('type'=>'content', 'html'=>$content);

    return array('stat'=>'ok', 'page'=>$page);
}
?>
