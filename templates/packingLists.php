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
        . "ciniki_poma_order_items.unit_suffix, "
        . "IFNULL(ciniki_foodmarket_product_outputs.sequence, 1) AS sequence "
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
            . ") "
        . "LEFT JOIN ciniki_foodmarket_product_outputs ON ("
            . "ciniki_poma_order_items.object = 'ciniki.foodmarket.output' "
            . "AND ciniki_poma_order_items.object_id = ciniki_foodmarket_product_outputs.id "
            . "AND ciniki_foodmarket_product_outputs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") ";
    if( isset($args['date_id']) && $args['date_id'] > 0 ) {
        $strsql .= "WHERE ciniki_poma_orders.date_id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' ";
    } elseif( isset($args['order_id']) && $args['order_id'] > 0 ) {
        $strsql .= "WHERE ciniki_poma_orders.id = '" . ciniki_core_dbQuote($ciniki, $args['order_id']) . "' ";
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.53', 'msg'=>'No orders specified'));
    }
    $strsql .= "AND ciniki_poma_orders.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "ORDER BY ciniki_customers.sort_name, ciniki_poma_orders.id, packing_order DESC, description "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.poma', array(
        array('container'=>'orders', 'fname'=>'id', 'fields'=>array('id', 'billing_name', 'sort_name', 'first', 'last', 'order_date_text')),
        array('container'=>'items', 'fname'=>'item_id', 
            'fields'=>array('id'=>'item_id', 'parent_id', 'line_number', 'code', 'description', 'object', 'object_id', 
                'flags', 'itype', 'weight_units', 'weight_quantity', 'unit_quantity', 'unit_suffix', 'sequence')), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['orders']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.54', 'msg'=>'No orders found'));
    }
    $orders = $rc['orders'];

    $weighted_items = array();
    //
    // Add parent name to subitems
    //
    foreach($orders as $oid => $order) {
        $orders[$oid]['sortnumber'] = 1;
        $orders[$oid]['modified'] = '';
        if( isset($order['items']) ) {
            foreach($order['items'] as $iid => $item) {
                if( $item['weight_quantity'] == 0 && $item['unit_quantity'] == 0 ) {
                    unset($orders[$oid]['items'][$iid]);
                    continue;
                }
                if( ($item['flags']&0x14) > 0 ) {
                    $orders[$oid]['items'][$iid]['modified'] = 'yes';
                    $orders[$oid]['modified'] = 'M';
                }
                if( $item['itype'] == 10 ) {
                    if( $item['weight_units'] == 20 ) {
                        $suffix = 'lb' . ($item['weight_quantity'] > 1 ? 's':'');
                    } elseif( $item['weight_units'] == 25 ) {
                        $suffix = 'oz' . ($item['weight_quantity'] > 1 ? 's':'');
                    } elseif( $item['weight_units'] == 60 ) {
                        $suffix = 'kg' . ($item['weight_quantity'] > 1 ? 's':'');
                    } elseif( $item['weight_units'] == 65 ) {
                        $suffix = 'g' . ($item['weight_quantity'] > 1 ? 's':'');
                    }
                    $orders[$oid]['items'][$iid]['quantity'] = (float)$item['weight_quantity'];
                    $orders[$oid]['items'][$iid]['suffix'] = $suffix;
                    if( !isset($weighted_items[$item['description']]) ) {
                        $weighted_items[$item['description']] = array(
                            'description'=>$item['description'],
                            'quantities'=>array(),
                        );
                    }
                    if( !isset($weighted_items[$item['description']]['quantities'][$item['weight_quantity']]['count']) ) {
                        $weighted_items[$item['description']]['quantities'][$item['weight_quantity']] = array(
                            'count'=>0, 
                            'size'=>$item['weight_quantity'],
                            'suffix'=>$suffix,
                        );
                    }
                    $weighted_items[$item['description']]['quantities'][$item['weight_quantity']]['count'] += 1;
                } else {
                    $orders[$oid]['items'][$iid]['quantity'] = (float)$item['unit_quantity'];
                    $orders[$oid]['items'][$iid]['suffix'] = $item['unit_suffix'];
                }
                if( $item['parent_id'] > 0 && isset($order['items'][$item['parent_id']]) ) {
                    $parent_id = $item['parent_id'];
                    if( !isset($orders[$oid]['items'][$parent_id]['subitems']) ) {
                        $orders[$oid]['items'][$parent_id]['subitems'] = array();
                        $orders[$oid]['items'][$parent_id]['basket'] = 'yes';
                        $orders[$oid]['sortnumber'] = (1000 * $orders[$oid]['items'][$parent_id]['sequence']);
                    }
                    if( ($item['flags']&0x14) > 0 ) {
                        $orders[$oid]['items'][$parent_id]['modified'] = 'yes';
                    }
                    $orders[$oid]['items'][$parent_id]['subitems'][] = $orders[$oid]['items'][$iid];
                    unset($orders[$oid]['items'][$iid]);
                }
            }
        }
//        if( $orders[$oid]['basket'] == 'yes' ) {
//            $orders[$oid]['sortnumber'] *= 100;
//        }
        if( $orders[$oid]['modified'] == 'M' ) {
            $orders[$oid]['sortnumber'] *= 100;
        }
    }
    uasort($orders, function($a, $b) {
        if( $a['sortnumber'] == $b['sortnumber'] ) {
            return strcasecmp($a['sort_name'], $b['sort_name']);
        }
        return $a['sortnumber'] > $b['sortnumber'] ? -1 : 1;
    });

    //
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        //Page header
        public $left_margin = 18;
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
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetCellPadding(1.5);

    // add a page
    $pdf->SetFillColor(246);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(232);
    $pdf->SetLineWidth(0.1);

    //
    // Add the first page as a summary
    //
    $pdf->name = 'Summary';
    $pdf->modified = '';
    $pdf->AddPage();
    $w = array(80, 40, 60);
    $lh = 10;
    $pdf->SetFillColor(232);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(180, 10, 'Baskets', 0, 1, 'L', 1);
    $border = 'TB';
    $pdf->SetFont('helvetica', '', 12);
    foreach($orders as $order) {
        $pdf->date_text = $order['order_date_text'];
        if( isset($order['items']) ) {
            foreach($order['items'] as $item) {
                if( isset($item['basket']) && $item['basket'] == 'yes' ) {
                    $pdf->Cell($w[0], $lh, $order['sort_name'], $border, 0, 'L', 0);
                    $pdf->Cell($w[1], $lh, (isset($item['modified']) && $item['modified'] == 'yes' ? 'Modified' : ''), $border, 0, 'R', 0);
                    $pdf->Cell($w[2], $lh, $item['description'], $border, 0, 'R', 0);
                    $pdf->Ln($lh);
                }
            }
        }
    }

    //
    // Check if there are weighted items and add a page for them
    //
    $w = array(140, 15, 10, 15);
    if( isset($weighted_items) && count($weighted_items) > 0 ) {
        if( $pdf->GetY() > 160 ) {
            $pdf->AddPage();
        } else {
            $pdf->Ln();
        }
        $pdf->SetFillColor(232);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(180, 10, 'Weighted Items', 0, 1, 'L', 1);
        $pdf->SetFont('helvetica', '', 12);
        foreach($weighted_items as $item) {
            foreach($item['quantities'] as $quantity) {
                $pdf->Cell($w[0], $lh, $item['description'], $border, 0, 'L', 0);
                $pdf->Cell($w[1], $lh, (float)$quantity['count'], $border, 0, 'L', 0);
                $pdf->Cell($w[2], $lh, (float)$quantity['size'], $border, 0, 'R', 0);
                $pdf->Cell($w[3], $lh, $quantity['suffix'], $border, 0, 'L', 0);
                $pdf->Ln($lh);
            }
        }
    }

    //
    // Go through the sections, categories and classes
    //
//    $w = array(8, 142, 5, 10, 15);
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

        $w = array(5, 8, 10, 15, 142);
        $num_baskets = 0;
        foreach($order['items'] as $item) {
            if( !isset($item['basket']) || $item['basket'] != 'yes' ) {
                continue;
            }
            $pdf->SetFillColor(232);
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(180, 10, $item['description'] . (isset($item['modified'])&&$item['modified'] == 'yes' ? ' - Modified':''), 0, 0, 'L', 1);
            $pdf->Ln(10);
            $pdf->SetFillColor(246);
            $subfill = 0;
            $border = 'B';
            foreach($item['subitems'] as $subitem) {
                $lh = 10;
                $pdf->SetFont('helvetica', '', 12);
                $pdf->Cell($w[0], $lh, (isset($subitem['modified'])&&$subitem['modified']=='yes'?'M':''), $border, 0, 'L', $subfill);
                $pdf->SetFont('zapfdingbats', '', 14);
                $pdf->Cell($w[1], $lh, 'o', $border, 0, 'C', $subfill);
                $pdf->SetFont('helvetica', '', 12);
                $pdf->Cell($w[2], $lh, $subitem['quantity'], $border, 0, 'R', $subfill);
                $pdf->Cell($w[3], $lh, $subitem['suffix'], $border, 0, 'L', $subfill);
                $pdf->Cell($w[4], $lh, $subitem['description'], $border, 0, 'L', $subfill);
//                $pdf->SetFont('zapfdingbats', '', 14);
//                $pdf->Cell($w[0], $lh, 'o', $border, 0, 'C', $subfill);
//                $pdf->SetFont('helvetica', '', 12);
//                $pdf->Cell($w[1], $lh, $subitem['description'], $border, 0, 'L', $subfill);
//                $pdf->Cell($w[2], $lh, (isset($subitem['modified'])&&$subitem['modified']=='yes'?'M':''), $border, 0, 'L', $subfill);
//                $pdf->Cell($w[3], $lh, $subitem['quantity'], $border, 0, 'R', $subfill);
//                $pdf->Cell($w[4], $lh, $subitem['suffix'], $border, 0, 'L', $subfill);
                $pdf->Ln();
                $border = 'B';
            }
            $num_baskets++;
        }

        if( $num_baskets > 0 && $num_baskets < count($order['items']) ) {
            $pdf->SetFillColor(232);
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(180, 10, 'Additional Items', 0, 0, 'L', 1);
            $pdf->Ln(10);
            $pdf->SetFillColor(246);
        }


        //
        // Output the regular items
        //
        $fill = 0;
        $border = 'TB';
        $w = array(8, 10, 15, 147);
        foreach($order['items'] as $item) {
            if( isset($item['basket']) && $item['basket'] == 'yes' ) {  
                continue;
            }
            if( $pdf->getY() > ($pdf->getPageHeight() - 30) ) {
                $pdf->AddPage();
                $pdf->SetFillColor(232);
                $pdf->SetFont('helvetica', 'B', 14);
                $pdf->Cell(180, 10, 'Additional Items (continued)', 0, 0, 'L', 1);
                $pdf->Ln(10);
                $pdf->SetFillColor(246);
            }
            $lh = 10;
            $pdf->SetFont('zapfdingbats', '', 14);
            $pdf->Cell($w[0], $lh, 'o', $border, 0, 'C', $fill);
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell($w[1], $lh, $item['quantity'], $border, 0, 'R', $fill);
            $pdf->Cell($w[2], $lh, $item['suffix'], $border, 0, 'L', $fill);
            $pdf->Cell($w[3], $lh, $item['description'], $border, 0, 'L', $fill);
//            $pdf->SetFont('zapfdingbats', '', 14);
//            $pdf->Cell($w[0], $lh, 'o', $border, 0, 'C', $fill);
//            $pdf->SetFont('helvetica', '', 12);
//            $pdf->Cell($w[1], $lh, $item['description'], $border, 0, 'L', $fill);
//            $pdf->Cell($w[2], $lh, '', $border, 0, 'L', $fill);
//            $pdf->Cell($w[3], $lh, $item['quantity'], $border, 0, 'R', $fill);
//            $pdf->Cell($w[4], $lh, $item['suffix'], $border, 0, 'L', $fill);
            $pdf->Ln();
            $border = 'B';
            //$fill=!$fill;
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf);
}
?>
