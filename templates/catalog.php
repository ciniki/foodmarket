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
function ciniki_foodmarket_templates_catalog(&$ciniki, $tnid, $args) {

    //
    // Load the tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
    $rc = ciniki_tenants_tenantDetails($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {    
        $tenant_details = $rc['details'];
    } else {
        $tenant_details = array();
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Get the categories and their products that are available to the public
    //
    $strsql = "SELECT categories.id, "
        . "categories.name AS category_name, "
        . "IFNULL(subcategories.id, 0) AS sid, "
        . "IFNULL(subcategories.name, '') AS subcategory_name, "
        . "products.name AS product_name, "
        . "outputs.id AS oid, "
        . "outputs.pio_name, "
        . "outputs.otype, "
        . "outputs.units, "
        . "outputs.flags, "
        . "outputs.retail_price_text, "
        . "outputs.retail_sprice_text "
        . "FROM ciniki_foodmarket_categories AS categories "
        . "LEFT JOIN ciniki_foodmarket_categories AS subcategories ON ("
            . "categories.id = subcategories.parent_id "
            . "AND subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_foodmarket_category_items AS items ON ("
            . "(categories.id = items.category_id OR subcategories.id = items.category_id) "
            . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_foodmarket_products AS products ON ("
            . "items.product_id = products.id "
            . "AND products.status = 40 "
            . "AND products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_foodmarket_product_outputs AS outputs ON ("
            . "products.id = outputs.product_id "
            . "AND outputs.status = 40 "
            . "AND outputs.otype < 71 "
            . "AND outputs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND categories.parent_id = 0 "
        . "AND categories.ctype = 0 "
        . "";
    if( isset($args['categories']) && count($args['categories']) > 0 ) {
        $strsql .= "AND categories.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['categories']) . ") ";
    }
    $strsql .= "ORDER BY categories.name, subcategories.name, products.name, outputs.sequence, outputs.pio_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.foodmarket', array(
        array('container'=>'categories', 'fname'=>'id', 'fields'=>array('name'=>'category_name')),
        array('container'=>'subcategories', 'fname'=>'sid', 'fields'=>array('name'=>'subcategory_name')),
        array('container'=>'outputs', 'fname'=>'oid', 'fields'=>array('name'=>'pio_name', 'otype', 'flags', 'price'=>'retail_price_text', 'sale_price'=>'retail_sprice_text')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['categories']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.57', 'msg'=>'No products found'));
    }
    $categories = $rc['categories'];

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
        public $header_text = '';
        public $tenant_details = array();

        public function Header() {
            //
            // Check if there is an image to be output in the header.   The image
            // will be displayed in a narrow box if the contact information is to
            // be displayed as well.  Otherwise, image is scaled to be 100% page width
            // but only to a maximum height of the header_height (set far below).
            //
            $this->SetFont('helvetica', 'B', 18);
            $this->Ln(8);
            $this->Cell(180, 12, $this->header_text, 0, false, 'C', 0, '', 0, false, 'M', 'M');
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            $this->SetFont('helvetica', '', 10);
            $this->Cell(90, 10, $this->date_text, 0, false, 'L', 0, '', 0, false, 'T', 'M');
            $this->Cell(90, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
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
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->SetTitle($tenant_details['name'] . ' Catalog');
    $pdf->header_text = $tenant_details['name'] . ' Catalog';
    $filename = preg_replace("/[^A-Za-z0-9 -_]/", '', $tenant_details['name'] . ' Catalog.pdf');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    $dt = new DateTime('now', new DateTimezone($intl_timezone));
    $pdf->date_text = $dt->format('M j, Y');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+10, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set font
    $pdf->SetFont('helvetica', '', 12);

    // add a page
    $pdf->SetFillColor(246);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(232);
    $pdf->SetLineWidth(0.1);

    //
    // Add the first page as a summary
    //
    $pdf->AddPage();
    $w = array(123, 32, 25);
    $lh = 10;
    $fill = 1;
    $pdf->SetFillColor(232);
    foreach($categories as $category) {
        foreach($category['subcategories'] AS $subcategory) {
            if( isset($subcategory['outputs']) && count($subcategory['outputs']) > 0 ) {
                if( $pdf->getY() > ($pdf->getPageHeight() - 40) ) {
                    $pdf->AddPage();
                }
                $pdf->SetCellPadding(1.5);
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell($w[0], $lh, $category['name'] . ($subcategory['name'] != '' ? ' > ' . $subcategory['name'] : ''), 0, 0, 'L', 1);
                $pdf->SetFont('', '', 12);
                $pdf->Cell($w[1], $lh, 'Price', 0, 0, 'L', 1);
                $pdf->Cell($w[2], $lh, 'Availability', 0, 0, 'L', 1);
                $pdf->Ln($lh+1);
                $pdf->SetFont('', '', 10);
                $border = 'B';
                foreach($subcategory['outputs'] AS $output) {
                    $nlines = $pdf->getNumLines($output['name'], $w[0]);
                    $lh = 9;
                    if( $nlines == 2 ) {
                        $lh = 13;
                    }
                    if( $pdf->getY() > ($pdf->getPageHeight() - 30) ) {
                        $pdf->AddPage();
                        $pdf->SetCellPadding(1.5);
                        $pdf->SetFont('helvetica', 'B', 12);
                        $pdf->Cell($w[0], $lh, $category['name'] . ($subcategory['name'] != '' ? ' > ' . $subcategory['name'] : '') . ' (continued)', 0, 0, 'L', 1);
                        $pdf->SetFont('', '', 12);
                        $pdf->Cell($w[1], $lh, 'Price', 0, 0, 'L', 1);
                        $pdf->Cell($w[2], $lh, 'Availability', 0, 0, 'C', 1);
                        $pdf->Ln($lh+1);
                        $pdf->SetFont('', '', 10);
                        $border = 'B';
                    }

                    $pdf->SetCellPadding(2);
                    if( ($output['flags']&0x0100) == 0x0100 ) {
                        $available = 'Always';
                    } elseif( ($output['flags']&0x0200) == 0x0200 ) {
                        $available = 'Seasonal';
                    } elseif( ($output['flags']&0x0400) == 0x0400 ) {
                        $available = 'Queue';
                    } elseif( ($output['flags']&0x0800) == 0x0800 ) {
                        $available = 'Variable';
                    } else {
                        $available = '';
                    }
                    $nlines = $pdf->getNumLines($output['name'], $w[0]);
                    $lh = 9;
                    if( $nlines == 2 ) {
                        $lh = 13;
                    }
//                    if( isset($output['sale_price']) && $output['sale_price'] != '' && $nlines < 2 ) {
//                        $lh = 13;
//                    }
                    $pdf->writeHTMLCell($w[0], $lh, '', '', $output['name'], $border, 0, false, true, 'L', 1);
//                    $pdf->MultiCell($w[0], $lh, $output['name'], $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T');
                    if( isset($output['sale_price']) && $output['sale_price'] != '' ) {
//                        $pdf->MultiCell($w[1], $lh, '<s>' . $output['price'] . '</s> ' . $output['sale_price'], $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'B');
                        $pdf->writeHTMLCell($w[1], $lh, '', '', '<s>$' . $output['price'] . '</s> ' . $output['sale_price'], $border, 0, false, true, 'L', 1);
                    } else {
                        $pdf->MultiCell($w[1], $lh, $output['price'], $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'B');
                    }
                    $pdf->writeHTMLCell($w[2], $lh, '', '', $available, $border, 0, false, true, 'C', 1);
//                    $pdf->MultiCell($w[2], $lh, $available, $border, 'C', 0, 0, '', '', true, 0, false, true, 0, 'B');
                    //$pdf->Cell($w[0], $lh, $output['name'], $border, 0, 'L', 0);
                    //$pdf->Cell($w[1], $lh, $output['price'], $border, 0, 'L', 0);
                    //$pdf->Cell($w[2], $lh, $available, $border, 0, 'C', 0);
                    $pdf->Ln($lh);
//                    $border = 'T';
                }
            }
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename);
}
?>
