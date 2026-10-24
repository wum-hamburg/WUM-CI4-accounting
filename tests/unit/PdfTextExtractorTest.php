<?php

use App\Services\PdfTextExtractor;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PdfTextExtractorTest extends CIUnitTestCase
{
    private PdfTextExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new PdfTextExtractor();
    }

    public function testBackupMonkeyStyleTotalAsHighestAmount(): void
    {
        $text = <<<'TXT'
Invoice Number: 2026-000646
Date: 01.08.2026
Subtotal
3.08
€
(Tax Rate: 19.0 %)
0.58
€
Total:
3.67
€
TXT;

        $this->assertSame(367, $this->extractor->parseGrossAmount($text));
        $this->assertSame('2026-000646', $this->extractor->parseInvoiceNumber($text));
        $this->assertSame('2026-08-01', $this->extractor->parseInvoiceDate($text));
    }

    public function testEuroPrefixEnglishDecimal(): void
    {
        $text = "Receipt\n€29.00\n€5.51\n€34.51\nThe €34.51 payment will appear";

        $this->assertSame(3451, $this->extractor->parseGrossAmount($text));
    }

    public function testGermanGesamtbetrag(): void
    {
        $text = "Rechnung Nr.: 080000740041\nRechnungsdatum: 19.02.2026\nGesamtbetrag 7,62 €";

        $this->assertSame(762, $this->extractor->parseGrossAmount($text));
        $this->assertSame('080000740041', $this->extractor->parseInvoiceNumber($text));
        $this->assertSame('2026-02-19', $this->extractor->parseInvoiceDate($text));
    }

    public function testEurPrefixGermanAndRechnungsnr(): void
    {
        $text = <<<'TXT'
Rechnung Rechnungsnr.: I-106489020-de 06.03.2026 Bestellnr.: BRZ25CDN
Nettopreis: EUR 125,21
MwSt (19%): EUR 23,79
EUR 149,00
TXT;

        $this->assertSame('I-106489020-de', $this->extractor->parseInvoiceNumber($text));
        $this->assertSame('2026-03-06', $this->extractor->parseInvoiceDate($text));
        $this->assertSame(14900, $this->extractor->parseGrossAmount($text));
    }

    public function testMaxAmountFallbackWhenNoLabel(): void
    {
        $text = "Pos 1 10,00 €\nPos 2 5,00 €\n42,50 €";

        $this->assertSame(4250, $this->extractor->parseGrossAmount($text));
    }
}
