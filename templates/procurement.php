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
function ciniki_foodmarket_templates_procurement(&$ciniki, $tnid, $args) {

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

    if( !isset($args['items']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.foodmarket.130', 'msg'=>'No items in procurement', 'err'=>$rc['err']));
    }

    //
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        //Page header
        public $size = 'fullpage';
        public $usable_width = 180;
        public $left_margin = 18;
        public $top_margin = 15;
        public $right_margin = 18;
        public $header_height = 0;
        public $name = '';
        public $date_text = '';
        public $modified = '';
        public $tenant_details = array();

        public function Header() {
            //
            // Check if there is an image to be output in the header.   The image
            // will be displayed in a narrow box if the contact information is to
            // be displayed as well.  Otherwise, image is scaled to be 100% page width
            // but only to a maximum height of the header_height (set far below).
            //
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            $this->SetFont('helvetica', '', 10);
            if( $this->size == 'halfpage' ) {
                $this->Cell(120, 12, $this->date_text, 0, false, 'C', 0, '', 0, false, 'M', 'M');
                $this->Cell(20, 12, '', 0, false, 'C', 0, '', 0, false, 'M', 'M');
                $this->Cell(120, 12, $this->date_text, 0, false, 'C', 0, '', 0, false, 'M', 'M');
            } else {
                $this->Cell(60, 12, $this->date_text, 0, false, 'L', 0, '', 0, false, 'M', 'M');
            }
        }
    }

    //
    // Start a new document
    //
    if( isset($args['size']) && $args['size'] == 'halfpage' ) {
        $pdf = new MYPDF('L', PDF_UNIT, 'LETTER', true, 'UTF-8', false);
        $w = array(60, 20, 39);
        $pdf->usable_width = 119;
        $pdf->header_height = 15;
        $pdf->left_margin = 10;
        $pdf->right_margin = 10;
        $pdf->setEqualColumns(2, 140);
        $pdf->SetMargins($pdf->left_margin, $pdf->header_height, $pdf->right_margin);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->size = $args['size'];
        $pdf->SetCellPadding(1.2);
    } else {
        $pdf = new MYPDF('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);
        $w = array(30, 90, 30, 30);
        // set margins
        $pdf->header_height = 15;
        $pdf->SetMargins($pdf->left_margin, $pdf->header_height, $pdf->right_margin);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetCellPaddings(2, 1.5, 2, 1.5);
    }

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->SetTitle('Procurement');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');


    // set font
    $pdf->SetFont('helvetica', '', 12);

    // add a page
    $pdf->SetFillColor(246);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(224);
    $pdf->SetLineWidth(0.1);

    //
    // Add the first page as a summary
    //
    $pdf->name = 'Procurement';
    $pdf->modified = '';
    $pdf->AddPage();
    $pdf->selectColumn(0);
    $pdf->SetFont('helvetica', 'B', 18);
    if( $pdf->size == 'halfpage' ) {
        $pdf->Cell($pdf->usable_width, 14, 'Procurement', 0, 1, 'L', 0, '', 0, false, 'M', 'T');
    } else {
        $pdf->Cell($pdf->usable_width, 14, 'Procurement', 0, 1, 'L', 0, '', 0, false, 'M', 'T');
    }
    $lh = 10;
    $border = 1;
    $pdf->SetFillColor(232);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell($w[0], $lh, 'Quantity', $border, 0, 'C', 1);
    $pdf->Cell($w[1], $lh, 'Name', $border, 0, 'L', 1);
    $pdf->Cell($w[2], $lh, 'SKU', $border, 0, 'L', 1);
    $pdf->Cell($w[3], $lh, 'Cost', $border, 0, 'C', 1);
    $pdf->Ln($lh);
    $pdf->SetFont('helvetica', '', 12);

    foreach($args['items'] as $item) {
        $pdf->Cell($w[0], $lh, $item['required_quantity_text'], $border, 0, 'C', 0);
        $pdf->Cell($w[1], $lh, $item['name'], $border, 0, 'L', 0);
        $pdf->Cell($w[2], $lh, $item['sku'], $border, 0, 'L', 0);
        $pdf->Cell($w[3], $lh, $item['cost_text'], $border, 0, 'R', 0);
        $pdf->Ln($lh);
    }

    return array('stat'=>'ok', 'pdf'=>$pdf);
}
?>
