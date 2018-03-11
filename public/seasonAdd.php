<?php
//
// Description
// -----------
// This method will add a new season for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Season to.
//
// Returns
// -------
//
function ciniki_foodmarket_seasonAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'),
        'start_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'Start Date'),
        'end_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'End Date'),
        'csa_start_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'CSA Start Date'),
        'csa_end_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'CSA End Date'),
        'csa_days'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'CSA Days'),
        'orders_start_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Orders Start Date'),
        'orders_end_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Orders End Date'),
        'orders_days'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Orders Days'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['tnid'], 'ciniki.foodmarket.seasonAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Get the settings for dates
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_poma_settings', 'tnid', $args['tnid'], 'ciniki.poma', 'settings', 'dates');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = $rc['settings'];

    
    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.foodmarket');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the season to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.foodmarket.season', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.foodmarket');
        return $rc;
    }
    $season_id = $rc['id'];

    //
    // Add the order dates for the season
    //
    if( isset($args['orders_start_date']) && isset($args['orders_end_date']) && isset($args['orders_days']) ) {
        $sdt = new DateTime($args['orders_start_date'], new DateTimezone($intl_timezone));
        $edt = new DateTime($args['orders_end_date'], new DateTimezone($intl_timezone));
        $oneday = new DateInterval('P1D');
        $days = array();
        if( ($args['orders_days']&0x02) == 0x02 ) { $days[] = 1; }      // Monday
        if( ($args['orders_days']&0x04) == 0x04 ) { $days[] = 2; }      // Tuesday
        if( ($args['orders_days']&0x08) == 0x08 ) { $days[] = 3; }      // Wednesday
        if( ($args['orders_days']&0x10) == 0x10 ) { $days[] = 4; }      // Thursday
        if( ($args['orders_days']&0x20) == 0x20 ) { $days[] = 5; }      // Friday
        if( ($args['orders_days']&0x40) == 0x40 ) { $days[] = 6; }      // Saturday
        if( ($args['orders_days']&0x01) == 0x01 || ($args['orders_days']&0x80) == 0x80 ) { $days[] = 7; }      // Sunday

        //
        // Prepare the args for adding order date
        //
        $order_date_args = array(
            'tnid' => $args['tnid'], 
            'order_date' => '',
            'status' => 5,
            'flags' => 0,
            'open_dt' => '',
            'repeats_dt' => '',
            'autolock_dt' => '',
            'lockreminder_dt' => '',
            'pickupreminder_dt' => '',
            );

        //
        // Prepare the pickup reminder date
        //
        $pickupreminder_dt = clone $sdt;
        $order_date_args['pickupreminder'] = 'no';
        if( isset($settings['dates-pickup-reminder']) && $settings['dates-pickup-reminder'] == 'yes' 
            && isset($settings['dates-pickup-reminder-time']) && $settings['dates-pickup-reminder-time'] != '' 
            ) {
            $ts = strtotime($args['orders_start_date'] . ' ' . $settings['dates-pickup-reminder-time']);
            if( $ts === FALSE || $ts < 1 ) {
                $args['pickupreminder_dt'] = '';
            } else {
                $pickupreminder_dt = new DateTime('@'.$ts, new DateTimeZone($intl_timezone));
                //
                // Check for the offset
                //
                if( isset($settings['dates-pickup-reminder-offset']) && $settings['dates-pickup-reminder-offset'] > 0 ) {
                    $pickupreminder_dt->sub(new DateInterval('P' . $settings['dates-pickup-reminder-offset'] . 'D'));
                }
                $order_date_args['pickupreminder'] = 'yes';
                $order_date_args['flags'] |= 0x40;
            }
        }
        
        //
        // Prepare the repeats date
        //
        $repeats_dt = clone $sdt;
        $order_date_args['repeats'] = 'no';
        if( isset($settings['dates-apply-repeats-offset']) && $settings['dates-apply-repeats-offset'] != '' 
            && isset($settings['dates-apply-repeats-time']) && $settings['dates-apply-repeats-time'] != '' 
            ) {
            $ts = strtotime($args['orders_start_date'] . ' ' . $settings['dates-apply-repeats-time']);
            if( $ts === FALSE || $ts < 1 ) {
                $args['repeats_dt'] = '';
            } else {
                $repeats_dt = new DateTime('@'.$ts, new DateTimeZone($intl_timezone));
                //
                // Check for the offset
                //
                if( isset($settings['dates-apply-repeats-offset']) && $settings['dates-apply-repeats-offset'] > 0 ) {
                    $repeats_dt->sub(new DateInterval('P' . $settings['dates-apply-repeats-offset'] . 'D'));
                }
                $order_date_args['repeats'] = 'yes';
            }
        }

        //
        // Add all the order dates for the season
        //
        while($sdt <= $edt) {
            //
            // Make sure it's one of the allowed days of the week
            //
            if( in_array($sdt->format('N'), $days) ) {
                $order_date_args['order_date'] = $sdt->format('Y-m-d');
                $order_date_args['display_name'] = $sdt->format('D M jS');

                //
                // Prepare the autoopen date
                //
                $open_dt = clone $sdt;
                $order_date_args['autoopen'] = 'no';
                if( isset($settings['dates-open-auto']) 
                    && ($settings['dates-open-auto'] == 'fixed' || $settings['dates-open-auto'] == 'variable')
                    && isset($settings['dates-open-time']) && $settings['dates-open-time'] != '' 
                    ) {
                    $ts = strtotime($sdt->format('Y-m-d') . ' ' . $settings['dates-open-time']);
                    if( $ts === FALSE || $ts < 1 ) {
                        $args['open_dt'] = '';
                    } else {
                        $open_dt = new DateTime('@'.$ts, new DateTimeZone($intl_timezone));
                        //
                        // Check for the offset
                        //
                        if( $settings['dates-open-auto'] == 'fixed' && isset($settings['dates-open-offset']) && $settings['dates-open-offset'] > 0 ) {
                            $open_dt->sub(new DateInterval('P' . $settings['dates-open-offset'] . 'D'));
                        } elseif( $settings['dates-open-auto'] == 'variable' ) {
                            $weekday = strtolower($open_dt->format('D'));
                            if( isset($settings['dates-open-offset-' . $weekday]) ) {
                                $open_dt->sub(new DateInterval('P' . $settings['dates-open-offset-' . $weekday] . 'D'));
                            }
                        }
                        $order_date_args['autoopen'] = 'yes';
                        $order_date_args['status'] = 5;
                        $order_date_args['flags'] |= 0x02;
                    }
                }

                //
                // Prepare the autolock date
                //
                $autolock_dt = clone $sdt;
                $order_date_args['autolock'] = 'no';
                if( isset($settings['dates-lock-auto']) 
                    && ($settings['dates-lock-auto'] == 'fixed' || $settings['dates-lock-auto'] == 'variable')
                    && isset($settings['dates-lock-time']) && $settings['dates-lock-time'] != '' 
                    ) {
                    $ts = strtotime($sdt->format('Y-m-d') . ' ' . $settings['dates-lock-time']);
                    if( $ts === FALSE || $ts < 1 ) {
                        $args['autolock_dt'] = '';
                    } else {
                        $autolock_dt = new DateTime('@'.$ts, new DateTimeZone($intl_timezone));
                        //
                        // Check for the offset
                        //
                        if( $settings['dates-lock-auto'] == 'fixed' && isset($settings['dates-lock-offset']) && $settings['dates-lock-offset'] > 0 ) {
                            $autolock_dt->sub(new DateInterval('P' . $settings['dates-lock-offset'] . 'D'));
                        } elseif( $settings['dates-lock-auto'] == 'variable' ) {
                            $weekday = strtolower($autolock_dt->format('D'));
                            if( isset($settings['dates-lock-offset-' . $weekday]) ) {
                                $autolock_dt->sub(new DateInterval('P' . $settings['dates-lock-offset-' . $weekday] . 'D'));
                            }
                        }
                        $order_date_args['autolock'] = 'yes';
                        $order_date_args['flags'] |= 0x01;
                    }
                }
        
                $order_date_args['open_dt'] = $order_date_args['autoopen'] == 'yes' ? $open_dt->format('Y-m-d H:i:s') : '';
                $order_date_args['repeats_dt'] = $order_date_args['repeats'] == 'yes' ? $repeats_dt->format('Y-m-d H:i:s') : '';
                $order_date_args['autolock_dt'] = $order_date_args['autolock'] == 'yes' ? $autolock_dt->format('Y-m-d H:i:s') : '';
                $order_date_args['pickupreminder_dt'] = $order_date_args['pickupreminder'] == 'yes' ? $pickupreminder_dt->format('Y-m-d H:i:s') : '';
                //
                // Add the date
                //
                $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.poma.orderdate', $order_date_args, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.poma');
                    return $rc;
                }
                $date_id = $rc['id'];
            }
            
            //
            // Increase all the dates
            //
            $sdt->add($oneday);
/*            $open_dt->add($oneday);
            $autolock_dt->add($oneday); */
            $pickupreminder_dt->add($oneday);
            $repeats_dt->add($oneday);
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.foodmarket');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'foodmarket');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.foodmarket.season', 'object_id'=>$season_id));

    return array('stat'=>'ok', 'id'=>$season_id);
}
?>
