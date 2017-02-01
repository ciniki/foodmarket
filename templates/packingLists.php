<?php
//
// Description
// ===========
// This function will return a PDF with the packing lists.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_foodmarket_templates_packingLists(&$ciniki, $business_id, $args) {

    //
    // Load the business details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'businessDetails');
    $rc = ciniki_businesses_businessDetails($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {    
        $business_details = $rc['details'];
    } else {
        $business_details = array();
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
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Load the orders and their items/subitems
    //
    $strsql = "SELECT ciniki_poma_orders.id, "
        . "ciniki_poma_orders.billing_name, "
        . "ciniki_customers.first, "
        . "ciniki_customers.last, "
        . "ciniki_customers.sort_name, "
        . "ciniki_poma_order_dates.display_name AS order_date_text, "
        . "ciniki_poma_order_items.id AS item_id, "
        . "ciniki_poma_order_items.parent_id, "
        . "ciniki_poma_order_items.line_number, "
        . "ciniki_poma_order_items.code, "
        . "ciniki_poma_order_items.description, "
        . "ciniki_poma_order_items.object, "
        . "ciniki_poma_order_items.object_id, "
        . "ciniki_poma_order_items.flags, "
        . "ciniki_poma_order_items.itype, "
        . "ciniki_poma_order_items.weight_units, "
        . "ciniki_poma_order_items.weight_quantity, "
        . "ciniki_poma_order_items.unit_quantity, "
        . "ciniki_poma_order_items.unit_suffix "
        . "FROM ciniki_poma_orders "
        . "LEFT JOIN ciniki_customers ON ("
            . "ciniki_poma_orders.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "LEFT JOIN ciniki_poma_order_dates ON ("
            . "ciniki_poma_orders.date_id = ciniki_poma_order_dates.id "
            . "AND ciniki_poma_order_dates.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "LEFT JOIN ciniki_poma_order_items ON ("
            . "ciniki_poma_orders.id = ciniki_poma_order_items.order_id "
            . "AND (ciniki_poma_order_items.flags&0x08) = 0 "
            . "AND ciniki_poma_order_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") ";
    if( isset($args['date_id']) && $args['date_id'] > 0 ) {
        $strsql .= "WHERE ciniki_poma_orders.date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' ";
    } elseif( isset($args['order_id']) && $args['order_id'] > 0 ) {
        $strsql .= "WHERE ciniki_poma_orders.id = '" . ciniki_core_dbQuote($ciniki, $args['order_id']) . "' ";
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.poma.78', 'msg'=>'No orders specified'));
    }
    $strsql .= "AND ciniki_poma_orders.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "ORDER BY ciniki_customers.sort_name, ciniki_poma_orders.id, packing_order DESC, description "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.poma', array(
        array('container'=>'orders', 'fname'=>'id', 'fields'=>array('id', 'billing_name', 'sort_name', 'first', 'last', 'order_date_text')),
        array('container'=>'items', 'fname'=>'item_id', 
            'fields'=>array('id'=>'item_id', 'parent_id', 'line_number', 'code', 'description', 'object', 'object_id', 
                'flags', 'itype', 'weight_units', 'weight_quantity', 'unit_quantity', 'unit_suffix')), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['orders']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.poma.91', 'msg'=>'No orders found'));
    }
    $orders = $rc['orders'];

    //
    // Add parent name to subitems
    //
    foreach($orders as $oid => $order) {
        $orders[$oid]['modified'] = '';
        if( isset($order['items']) ) {
            foreach($order['items'] as $iid => $item) {
                if( $item['weight_quantity'] == 0 && $item['unit_quantity'] == 0 ) {
                    unset($orders[$oid]['items'][$iid]);
                    continue;
                }
                if( ($item['flags']&0x14) > 0 ) {
                    $orders[$oid]['modified'] = 'M';
                }
                if( $item['itype'] == 10 ) {
                    if( $item['weight_units'] == 20 ) {
                        $suffix = ' lb' . ($item['weight_quantity'] > 1 ? 's':'');
                    } elseif( $item['weight_units'] == 25 ) {
                        $suffix = ' oz' . ($item['weight_quantity'] > 1 ? 's':'');
                    } elseif( $item['weight_units'] == 60 ) {
                        $suffix = ' kg' . ($item['weight_quantity'] > 1 ? 's':'');
                    } elseif( $item['weight_units'] == 65 ) {
                        $suffix = ' g' . ($item['weight_quantity'] > 1 ? 's':'');
                    }
                    $orders[$oid]['items'][$iid]['quantity'] = (float)$item['weight_quantity'];
                    $orders[$oid]['items'][$iid]['suffix'] = $suffix;
                } else {
                    $orders[$oid]['items'][$iid]['quantity'] = (float)$item['unit_quantity'];
                    $orders[$oid]['items'][$iid]['suffix'] = $item['unit_suffix'];
                }
                if( $item['parent_id'] > 0 && isset($order['items'][$item['parent_id']]) ) {
                    $parent_id = $item['parent_id'];
                    if( !isset($orders[$oid]['items'][$parent_id]['subitems']) ) {
                        $orders[$oid]['items'][$parent_id]['subitems'] = array();
                        $orders[$oid]['items'][$parent_id]['basket'] = 'yes';
                    }
                    if( ($item['flags']&0x14) > 0 ) {
                        $orders[$oid]['items'][$parent_id]['modified'] = 'yes';
                    }
                    $orders[$oid]['items'][$parent_id]['subitems'][] = $orders[$oid]['items'][$iid];
                    unset($orders[$oid]['items'][$iid]);
                }
            }
        }
    }

    //
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        //Page header
        public $left_margin = 18;
        public $right_margin = 18;
        public $top_margin = 15;
        public $header_height = 15;
        public $name = '';
        public $date_text = '';
        public $modified = '';
        public $business_details = array();

        public function Header() {
            //
            // Check if there is an image to be output in the header.   The image
            // will be displayed in a narrow box if the contact information is to
            // be displayed as well.  Otherwise, image is scaled to be 100% page width
            // but only to a maximum height of the header_height (set far below).
            //
            $this->SetFont('helvetica', 'B', 18);
            $this->Ln(8);
            $this->Cell(120, 12, $this->name, 0, false, 'L', 0, '', 0, false, 'M', 'M');
            $this->Cell(60, 12, $this->modified, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            $this->SetFont('helvetica', '', 10);
            $this->Cell(60, 12, $this->date_text, 0, false, 'L', 0, '', 0, false, 'M', 'M');
//            $this->Cell(90, 10, $this->footer_msg, 0, false, 'L', 0, '', 0, false, 'T', 'M');
//            $this->SetFont('helvetica', '', 10);
//            $this->Cell(90, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($business_details['name']);
    $pdf->SetTitle('Packing list');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+10, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set font
    $pdf->SetFont('times', '', 12);
    $pdf->SetCellPadding(1.5);

    // add a page
    $pdf->SetFillColor(240);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(51);
    $pdf->SetLineWidth(0.15);

    //
    // Go through the sections, categories and classes
    //
    $w = array(8, 142, 10, 20);
    foreach($orders as $order) {
        if( !isset($order['items']) || count($order['items']) == 0 ) {
            continue;
        }

        //
        // Start a new section
        //
        $pdf->name = $order['sort_name'];
        $pdf->date_text = $order['order_date_text'];
        $pdf->modified = $order['modified'];

        $pdf->AddPage();

        $num_baskets = 0;
        foreach($order['items'] as $item) {
            if( !isset($item['basket']) || $item['basket'] != 'yes' ) {
                continue;
            }
            $pdf->SetFont('times', 'B', 14);
            $pdf->Cell(180, 12, $item['description'] . (isset($item['modified'])&&$item['modified'] == 'yes' ? ' - Modified':''), 0, 0, 'L', false);
            $pdf->Ln(12);
            $subfill = 1;
            foreach($item['subitems'] as $subitem) {
                $lh = 10;
                $pdf->SetFont('zapfdingbats', '', 14);
                $pdf->Cell($w[0], $lh, 'o', 0, 0, 'C', $subfill);
                $pdf->SetFont('times', '', 12);
                $pdf->Cell($w[1], $lh, $subitem['description'], 0, 0, 'L', $subfill);
                $pdf->Cell($w[2], $lh, $subitem['quantity'], 0, 0, 'R', $subfill);
                $pdf->Cell($w[3], $lh, $subitem['suffix'], 0, 0, 'L', $subfill);
                $pdf->Ln();
                $subfill=!$subfill;
            }
            $num_baskets++;
        }

        if( $num_baskets > 0 && $num_baskets < count($order['items']) ) {
            $pdf->SetFont('times', 'B', 14);
            $pdf->Cell(180, 12, 'Additional Items', 0, 0, 'L', false);
            $pdf->Ln(12);
        }


        //
        // Output the categories
        //
        $fill = 1;
        foreach($order['items'] as $item) {
            if( isset($item['basket']) && $item['basket'] == 'yes' ) {  
                continue;
            }
            $lh = 10;
            $pdf->SetFont('zapfdingbats', '', 14);
            $pdf->Cell($w[0], $lh, 'o', 0, 0, 'C', $fill);
            $pdf->SetFont('times', '', 12);
            $pdf->Cell($w[1], $lh, $item['description'], 0, 0, 'L', $fill);
            $pdf->Cell($w[2], $lh, $item['quantity'], 0, 0, 'R', $fill);
            $pdf->Cell($w[3], $lh, $item['suffix'], 0, 0, 'L', $fill);
            $pdf->Ln();
            $fill=!$fill;
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf);
}
?>
