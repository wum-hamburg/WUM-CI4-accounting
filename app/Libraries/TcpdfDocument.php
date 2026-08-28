<?php

namespace App\Libraries;

/**
 * TCPDF wrapper for WUM invoices/quotes / PDF-Erzeugung
 */
class TcpdfDocument
{
    private bool $loaded = false;

    /** Content Y below Kontakt block when Firmenbogen is on every page */
    private const LETTERHEAD_CONTENT_TOP = 88.0;

    /** Footer band (bank line / page footer) */
    private const FOOTER_Y = 265.0;

    private function loadTcpdf(): void
    {
        if ($this->loaded) {
            return;
        }
        $base = APPPATH . 'ThirdParty' . DIRECTORY_SEPARATOR . 'TCPDF' . DIRECTORY_SEPARATOR;
        require_once $base . 'config' . DIRECTORY_SEPARATOR . 'tcpdf_config.php';
        require_once $base . 'tcpdf.php';
        $this->loaded = true;
    }

    /**
     * @param float $htmlStartY Content start Y (mm) on page 1
     * @param bool  $letterheadEveryPage Draw Firmenbogen on every page (e.g. GuV)
     */
    public function generate(
        string $html,
        string $title,
        string $outputPath,
        bool $isDraft = false,
        float $htmlStartY = 40.0,
        bool $letterheadEveryPage = false,
        ?string $facturXml = null
    ): bool {
        $this->loadTcpdf();
        helper('wum');
        $wum    = config('Wum');
        $isDemo = wum_is_demo();
        $pdfaMode = ($facturXml !== null && $facturXml !== '') ? 3 : false;

        $logoPath = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $wum->logoPath);

        $pdf = new class(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false, $pdfaMode) extends \TCPDF {
            public bool $wumDemoWatermark = false;
            public bool $wumLetterheadEveryPage = false;
            /** GuV: page numbers + author instead of bank line */
            public bool $wumPageFooter = false;
            /** @var object|null */
            public $wumConfig = null;
            public string $wumLogoPath = '';
            public float $wumFooterY = 265.0;

            public function Header(): void
            {
                $bMargin         = $this->getBreakMargin();
                $auto_page_break = $this->AutoPageBreak;
                $this->SetAutoPageBreak(false, 0);

                if ($this->wumDemoWatermark) {
                    $this->SetFont('helvetica', 'B', 54);
                    $this->SetTextColor(200, 200, 200);
                    $this->setAlpha(0.28);
                    $cx = $this->getPageWidth() / 2;
                    $cy = $this->getPageHeight() / 2;
                    $this->StartTransform();
                    $this->Rotate(45, $cx, $cy);
                    $this->Text($cx - 55, $cy - 20, 'MUSTER');
                    $this->SetFont('helvetica', 'B', 36);
                    $this->Text($cx - 30, $cy + 10, 'DEMO');
                    $this->StopTransform();
                    $this->setAlpha(1);
                    $this->SetTextColor(0, 0, 0);
                }

                if ($this->wumLetterheadEveryPage && $this->wumConfig !== null) {
                    $this->drawWumLetterhead(false);
                }

                $this->SetAutoPageBreak($auto_page_break, $bMargin);
                $this->setPageMark();
            }

            public function Footer(): void
            {
                if (! $this->wumPageFooter) {
                    return;
                }

                $this->SetY($this->wumFooterY);
                $this->SetFont('helvetica', '', 10);
                $half = ($this->getPageWidth() - $this->lMargin - $this->rMargin) / 2;
                $this->Cell(
                    $half,
                    10,
                    'Seite ' . $this->getAliasNumPage() . ' von ' . $this->getAliasNbPages() . ' Seiten',
                    0,
                    0,
                    'L'
                );
                $this->Cell($half, 10, 'Verfasser: Marko Jorissen', 0, 0, 'R');
            }

            public function drawWumLetterhead(bool $withBankLine = true): void
            {
                $wum = $this->wumConfig;

                $this->SetFont('times', 'B', 21);
                $this->SetY(10);
                $this->Cell(0, 10, $wum->companyName, 0, 1, 'L');
                $this->SetFont('helvetica', '', 12);
                $this->Cell(0, 10, $wum->companyOwner, 0, 1, 'L');

                if ($this->wumLogoPath !== '' && is_file($this->wumLogoPath)) {
                    $this->Image($this->wumLogoPath, 160, 10, 40, 45, '', '', '', false, 300);
                }

                $this->SetXY(159, 60);
                $this->SetFont('helvetica', 'B', 8);
                $this->MultiCell(80, 10, 'Kontakt:', 0, 'L', false, 1, '', '', true);
                $this->SetXY(159, 65);
                $this->SetFont('helvetica', '', 8);
                $this->MultiCell(
                    80,
                    10,
                    $wum->companyStreet . "\n" . $wum->companyCity . "\n" . $wum->companyPhone . "\n" . $wum->companyFax . "\n" . $wum->companyEmail,
                    0,
                    'L',
                    false,
                    1,
                    '',
                    '',
                    true
                );

                if ($withBankLine) {
                    $this->SetY($this->wumFooterY);
                    $this->SetFont('helvetica', '', 10);
                    $this->Cell(0, 10, $wum->companyBankLine, 1, 0, 'L');
                }
            }
        };

        $pdf->wumDemoWatermark       = $isDemo;
        $pdf->wumLetterheadEveryPage = $letterheadEveryPage;
        $pdf->wumPageFooter          = $letterheadEveryPage;
        $pdf->wumConfig              = $wum;
        $pdf->wumLogoPath            = is_file($logoPath) ? $logoPath : '';
        $pdf->wumFooterY             = self::FOOTER_Y;

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Marko Jorissen');
        $pdf->SetTitle($title);
        $pdf->SetSubject($title);
        $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
        $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        if ($letterheadEveryPage) {
            // Page 2+ must clear Kontakt; page 1 still uses $htmlStartY
            $pdf->SetMargins(PDF_MARGIN_LEFT, self::LETTERHEAD_CONTENT_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetAutoPageBreak(true, 297.0 - self::FOOTER_Y);
            $pdf->SetFooterMargin(297.0 - self::FOOTER_Y);
            $pdf->setPrintFooter(true);
        } else {
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
            $pdf->setPrintFooter(false);
        }

        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->AddPage();

        if (! $letterheadEveryPage) {
            $pdf->drawWumLetterhead(true);
        }

        $pdf->SetXY(20, $htmlStartY);
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->writeHTML($html, true, false, true, false, '');

        $facturTempDir = null;
        if ($facturXml !== null && $facturXml !== '') {
            $facturTempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wum-fx-' . uniqid('', true);
            if (@mkdir($facturTempDir) || is_dir($facturTempDir)) {
                $facturPath = $facturTempDir . DIRECTORY_SEPARATOR . 'factur-x.xml';
                file_put_contents($facturPath, $facturXml);
                $pdf->Annotation(-100, -100, 0, 0, '', [
                    'Subtype'        => 'FileAttachment',
                    'Name'           => 'Paperclip',
                    'FS'             => $facturPath,
                    'Desc'           => 'Factur-X/ZUGFeRD electronic invoice',
                    'AFRelationship' => 'Alternative',
                ]);
                $pdf->setExtraXMPRDF(
                    '<fx:DocumentType>INVOICE</fx:DocumentType>' . "\n"
                    . '<fx:DocumentFileName>factur-x.xml</fx:DocumentFileName>' . "\n"
                    . '<fx:Version>1.0</fx:Version>' . "\n"
                    . '<fx:ConformanceLevel>EN 16931</fx:ConformanceLevel>'
                );
            }
        }

        if ($isDemo && $facturXml === null) {
            $pdf->setProtection(
                ['modify', 'assemble', 'annot-forms', 'fill-forms', 'copy', 'extract'],
                '',
                null,
                3
            );
        }

        $dir = dirname($outputPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $pdf->Output($outputPath, 'F');

        if ($facturTempDir !== null && is_dir($facturTempDir)) {
            @unlink($facturTempDir . DIRECTORY_SEPARATOR . 'factur-x.xml');
            @rmdir($facturTempDir);
        }

        return is_file($outputPath);
    }
}
