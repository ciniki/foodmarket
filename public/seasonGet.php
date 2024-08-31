<?php
//
// Description
// ===========
// This method will return all the information about an season.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the season is attached to.
// season_id:          The ID of the season to get the details for.
//
// Returns
// -------
//
function ciniki_foodmarket_seasonGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'season_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Season'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['tnid'], 'ciniki.foodmarket.seasonGet');
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
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Season
    //
    if( $args['season_id'] == 0 ) {
        $season = array('id'=>0,
            'name'=>'',
            'start_date'=>'',
            'end_date'=>'',
            'csa_start_date'=>'',
            'csa_end_date'=>'',
            'csa_days'=>'',
        );
    }

    //
    // Get the details for an existing Season
    //
    else {
        $strsql = "SELECT ciniki_foodmarket_seasons.id, "
            . "ciniki_foodmarket_seasons.name, "
            . "ciniki_foodmarket_seasons.start_date, "
            . "ciniki_foodmarket_seasons.end_date, "
            . "ciniki_foodmarket_seasons.csa_start_date, "
            . "ciniki_foodmarket_seasons.csa_end_date, "
            . "ciniki_foodmarket_seasons.csa_days "
            . "FROM ciniki_foodmarket_seasons "
            . "WHERE ciniki_foodmarket_seasons.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_foodmarket_seasons.id = '" . ciniki_core_dbQuote($ciniki, $args['season_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
            array('container'=>'seasons', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'start_date', 'end_date', 'csa_start_date', 'csa_end_date', 'csa_days'),
                'utctotz'=>array('start_date'=>array('timezone'=>'UTC', 'format'=>$date_format),
                    'end_date'=>array('timezone'=>'UTC', 'format'=>$date_format),
                    'csa_start_date'=>array('timezone'=>'UTC', 'format'=>$date_format),
                    'csa_end_date'=>array('timezone'=>'UTC', 'format'=>$date_format)),                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.102', 'msg'=>'Season not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['seasons'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.103', 'msg'=>'Unable to find Season'));
        }
        $season = $rc['seasons'][0];

        //
        // Get the list of products for the season
        //
        $strsql = "SELECT products.id, "
            . "products.season_id, "
            . "products.output_id, "
            . "outputs.pio_name, "
            . "products.repeat_days, "
            . "products.repeat_weeks, "
            . "products.price "
            . "FROM ciniki_foodmarket_season_products AS products "
            . "LEFT JOIN ciniki_foodmarket_product_outputs AS outputs ON ("
                . "products.output_id = outputs.id "
                . "AND outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND products.season_id = '" . ciniki_core_dbQuote($ciniki, $args['season_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
            array('container'=>'products', 'fname'=>'id', 
                'fields'=>array('id', 'season_id', 'output_id', 'pio_name', 'repeat_days', 'repeat_weeks', 'price'),
                'maps'=>array('repeat_days'=>$maps['seasonproduct']['repeat_days']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['products']) ) {
            $season['products'] = $rc['products'];
            foreach($season['products'] as $iid => $product) {
                $season['products'][$iid]['price_display'] = '$' . number_format($product['price'], 2);
            }
        } else {
            $season['products'] = array();
        }
    }

    return array('stat'=>'ok', 'season'=>$season);
}
?>
