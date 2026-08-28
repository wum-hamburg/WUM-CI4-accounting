<?php

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;
use Easybill\ZUGFeRD2\Model\Amount;
use Easybill\ZUGFeRD2\Model\CrossIndustryInvoice;
use Easybill\ZUGFeRD2\Reader;

/**
 * Parse uploaded invoice files for incoming invoice form prefill.
 */
class IncomingInvoiceImportService
{
    private const MAX_BYTES = 10_485_760;

    /**
     * @return array{
     *     ok: bool,
     *     invoice_number: ?string,
     *     invoice_date: ?string,
     *     amount_euro: ?string,
     *     warnings: list<string>,
     *     source: 'zugferd'|'pdf_text'|'none'
     * }
     */
    public function parse(UploadedFile $file): array
    {
        $empty = [
            'ok'             => false,
            'invoice_number' => null,
            'invoice_date'   => null,
            'amount_euro'    => null,
            'warnings'       => [],
            'source'         => 'none',
        ];

        if (! $file->isValid()) {
            $empty['warnings'][] = 'Upload fehlgeschlagen.';

            return $empty;
        }

        if ($file->getSize() > self::MAX_BYTES) {
            $empty['warnings'][] = 'Datei ist zu groß (max. 10 MB).';

            return $empty;
        }

        $ext = strtolower((string) $file->getExtension());
        if (! in_array($ext, ['pdf', 'xml'], true)) {
            $empty['warnings'][] = 'Nur PDF- oder XML-Dateien sind erlaubt.';

            return $empty;
        }

        $path = $file->getTempName();
        if ($path === '' || ! is_file($path)) {
            $empty['warnings'][] = 'Temporäre Datei nicht gefunden.';

            return $empty;
        }

        if ($ext === 'xml') {
            $xml = @file_get_contents($path);
            if (! is_string($xml) || trim($xml) === '') {
                $empty['warnings'][] = 'XML-Datei ist leer.';

                return $empty;
            }

            return $this->parseFromXml($xml);
        }

        $embedded = (new PdfEmbeddedXmlExtractor())->extract($path);
        if ($embedded !== null) {
            return $this->parseFromXml($embedded);
        }

        return $this->parseFromPdfText($path);
    }

    /**
     * @return array{
     *     ok: bool,
     *     invoice_number: ?string,
     *     invoice_date: ?string,
     *     amount_euro: ?string,
     *     warnings: list<string>,
     *     source: 'zugferd'|'pdf_text'|'none'
     * }
     */
    private function parseFromXml(string $xml): array
    {
        $result = [
            'ok'             => false,
            'invoice_number' => null,
            'invoice_date'   => null,
            'amount_euro'    => null,
            'warnings'       => [],
            'source'         => 'zugferd',
        ];

        try {
            $obj = Reader::create()->transform($xml);
        } catch (\Throwable $e) {
            $result['warnings'][] = 'XML konnte nicht gelesen werden.';

            return $result;
        }

        $mapped = $this->mapCrossIndustryInvoice($obj);
        if ($mapped['invoice_number'] === null && $mapped['invoice_date'] === null && $mapped['amount_euro'] === null) {
            $result['warnings'][] = 'Keine Rechnungsdaten in der XML-Datei gefunden.';

            return $result;
        }

        $result['ok']             = true;
        $result['invoice_number'] = $mapped['invoice_number'];
        $result['invoice_date']   = $mapped['invoice_date'];
        $result['amount_euro']    = $mapped['amount_euro'];

        return $result;
    }

    /**
     * @return array{invoice_number: ?string, invoice_date: ?string, amount_euro: ?string}
     */
    private function mapCrossIndustryInvoice(CrossIndustryInvoice $obj): array
    {
        $number = $obj->exchangedDocument->id ?? null;
        $date   = null;
        if (isset($obj->exchangedDocument->issueDateTime->dateTimeString->value)) {
            $raw = (string) $obj->exchangedDocument->issueDateTime->dateTimeString->value;
            if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $raw, $m)) {
                $date = sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
            }
        }

        $cents = $this->grossCentsFromInvoice($obj);

        return [
            'invoice_number' => $number !== null && $number !== '' ? (string) $number : null,
            'invoice_date'   => $date,
            'amount_euro'    => $cents !== null ? number_format($cents / 100, 2, ',', '') : null,
        ];
    }

    private function grossCentsFromInvoice(CrossIndustryInvoice $obj): ?int
    {
        $summation = $obj->supplyChainTradeTransaction
            ->applicableHeaderTradeSettlement
            ->specifiedTradeSettlementHeaderMonetarySummation ?? null;
        if ($summation === null) {
            return null;
        }

        $candidates = [];
        if ($summation->duePayableAmount instanceof Amount) {
            $candidates[] = $summation->duePayableAmount->value;
        }
        if ($summation->grandTotalAmount !== []) {
            $last = end($summation->grandTotalAmount);
            if ($last instanceof Amount) {
                $candidates[] = $last->value;
            }
        }
        if ($summation->taxBasisTotalAmount !== []) {
            $last = end($summation->taxBasisTotalAmount);
            if ($last instanceof Amount) {
                $candidates[] = $last->value;
            }
        }
        if ($summation->lineTotalAmount instanceof Amount) {
            $candidates[] = $summation->lineTotalAmount->value;
        }

        foreach ($candidates as $value) {
            $cents = $this->decimalToCents((string) $value);
            if ($cents !== null) {
                return $cents;
            }
        }

        return null;
    }

    /**
     * @return array{
     *     ok: bool,
     *     invoice_number: ?string,
     *     invoice_date: ?string,
     *     amount_euro: ?string,
     *     warnings: list<string>,
     *     source: 'zugferd'|'pdf_text'|'none'
     * }
     */
    private function parseFromPdfText(string $path): array
    {
        $extractor = new PdfTextExtractor();
        $text      = $extractor->extract($path);

        $result = [
            'ok'             => false,
            'invoice_number' => $extractor->parseInvoiceNumber($text),
            'invoice_date'   => $extractor->parseInvoiceDate($text),
            'amount_euro'    => null,
            'warnings'       => [],
            'source'         => 'pdf_text',
        ];

        $cents = $extractor->parseGrossAmount($text);
        if ($cents !== null) {
            $result['amount_euro'] = number_format($cents / 100, 2, ',', '');
        }

        if ($result['invoice_number'] === null && $result['invoice_date'] === null && $result['amount_euro'] === null) {
            $result['warnings'][] = 'In der PDF wurden keine Rechnungsdaten erkannt.';

            return $result;
        }

        $result['ok'] = true;
        if ($result['invoice_number'] === null) {
            $result['warnings'][] = 'Rechnungsnummer nicht erkannt.';
        }
        if ($result['invoice_date'] === null) {
            $result['warnings'][] = 'Rechnungsdatum nicht erkannt.';
        }
        if ($result['amount_euro'] === null) {
            $result['warnings'][] = 'Brutto-Betrag nicht erkannt.';
        }

        return $result;
    }

    private function decimalToCents(string $raw): ?int
    {
        $raw = str_replace([' ', "\xc2\xa0"], '', $raw);
        if ($raw === '' || ! is_numeric($raw)) {
            return null;
        }
        $cents = (int) round((float) $raw * 100);

        return $cents >= 0 ? $cents : null;
    }
}
