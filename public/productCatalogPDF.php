<?php
//
// Description
// -----------
// This method returns the PDF of product catalog.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Order Date Item for.
//
// Returns
// -------
//
function ciniki_foodmarket_productCatalogPDF(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'categories'=>array('required'=>'no', 'blank'=>'no', 'type'=>'idlist', 'name'=>'Categories'),
        'subscriptions'=>array('required'=>'no', 'blank'=>'no', 'type'=>'idlist', 'name'=>'Subscriptions'),
        'subject'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Subject'),
        'textmsg'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Message'),
        'output'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Output'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'private', 'checkAccess');
    $rc = ciniki_foodmarket_checkAccess($ciniki, $args['business_id'], 'ciniki.foodmarket.productCatalogPDF');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'foodmarket', 'templates', 'catalog');
    $rc = ciniki_foodmarket_templates_catalog($ciniki, $args['business_id'], $args);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['pdf']) ) {
        $pdf = $rc['pdf'];
        $filename = $rc['filename'];
        if( $args['output'] == 'download' ) {
            $pdf->Output($filename, 'D');
            return array('stat'=>'exit');
        } 
        elseif( $args['output'] == 'testemail' ) {
            if( isset($args['subject']) && $args['subject'] != '' && isset($args['textmsg']) && $args['textmsg'] != '' ) {
                //
                // Get the users email
                //
                $strsql = "SELECT id, CONCAT_WS(' ', firstname, lastname) AS name, email "
                    . "FROM ciniki_users "
                    . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
                    . "";
                $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
                if( $rc['stat'] != 'ok' || !isset($rc['user']) ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.62', 'msg'=>'Unable to find email information', 'err'=>$rc['err']));
                }
                $name = $rc['user']['name'];
                $email = $rc['user']['email'];
                ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
                $rc = ciniki_mail_hooks_addMessage($ciniki, $args['business_id'], array(
                    'customer_email'=>$email,
                    'customer_name'=>$name,
                    'subject'=>$args['subject'],
                    'html_content'=>$args['textmsg'],
                    'text_content'=>$args['textmsg'],
                    'attachments'=>array(array('content'=>$pdf->Output('catalog.pdf', 'S'), 'filename'=>$filename)),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $ciniki['emailqueue'][] = array('mail_id'=>$rc['id'], 'business_id'=>$args['business_id']);
                return array('stat'=>'ok');
            } else {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.60', 'msg'=>'Subject and message must be specified.'));
            }
        } 
        elseif( $args['output'] == 'mailinglists' ) {
            if( isset($args['subject']) && $args['subject'] != '' && isset($args['textmsg']) && $args['textmsg'] != '' ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'emailSubscriptionLists');
                $rc = ciniki_mail_hooks_emailSubscriptionLists($ciniki, $args['business_id'], array(
                    'subscriptions'=>$args['subscriptions'],
                    'subject'=>$args['subject'],
                    'html_content'=>$args['textmsg'],
                    'text_content'=>$args['textmsg'],
                    'attachments'=>array(array('content'=>$pdf->Output('catalog.pdf', 'S'), 'filename'=>$filename)),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                return array('stat'=>'ok');
            } else {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.61', 'msg'=>'Subject and message must be specified.'));
            }
        } 
        else {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.59', 'msg'=>'No output specified.'));
        }
    } 

    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.58', 'msg'=>'No pdf generated'));
}
?>
