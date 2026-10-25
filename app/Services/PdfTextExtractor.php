<?php

namespace App\Services;

/**
 * Lightweight PDF text extraction for invoice metadata.
 */
class PdfTextExtractor
{
    public function extract(string $path): string
    {
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return '';
        }

        $parts = [];
        if (preg_match_all('/stream\r?\n(.*)\r?\nendstream/sU', $raw, $matches)) {
            foreach ($matches[1] as $stream) {
                $decoded = $this->inflate(ltrim($stream, "\r\n"));
                if ($decoded === null || $decoded === '') {
                    continue;
                }
                $parts = array_merge($parts, $this->extractLiterals($decoded));
            }
        }

        if ($parts === []) {
            $parts = $this->extractLiterals($raw);
        }

        return implode("\n", $parts);
    }

    public function parseNetAmount(string $text): ?int
    {
        if (preg_match('/(?:Gesamtbetrag|Summe|Netto:?)\s*([\d.]+,\d{2})/ui', $text, $m)) {
            return $this->germanPriceToCents($m[1]);
        }

        if (preg_match_all('/([\d.]+,\d{2})\s*(?:€|EUR)/u', $text, $m)) {
            $last = end($m[1]);
            if (is_string($last)) {
                return $this->germanPriceToCents($last);
            }
        }

        return null;
    }

    public function parseGrossAmount(string $text): ?int
    {
        if (preg_match('/(?:Brutto|Gesamtbetrag|Summe|Rechnungsbetrag|Zahlbetrag)\s*:?\s*([\d.]+,\d{2})/ui', $text, $m)) {
            return $this->germanPriceToCents($m[1]);
        }

        if (preg_match('/(?:inkl\.?\s*MwSt|inkl\.?\s*USt|Gesamtsumme)\s*:?\s*([\d.]+,\d{2})/ui', $text, $m)) {
            return $this->germanPriceToCents($m[1]);
        }

        if (preg_match_all('/([\d.]+,\d{2})\s*(?:€|EUR)/u', $text, $m)) {
            $last = end($m[1]);
            if (is_string($last)) {
                return $this->germanPriceToCents($last);
            }
        }

        return $this->parseNetAmount($text);
    }

    public function parseInvoiceDate(string $text, ?string $yearHint = null): ?string
    {
        $dates = [];

        if (preg_match_all('/(?:Rechnungsdatum|Datum|Invoice date)\s*:?\s*(\d{1,2}\.\d{1,2}\.\d{4})/ui', $text, $m)) {
            foreach ($m[1] as $candidate) {
                $iso = $this->germanDateToIso($candidate);
                if ($iso !== null) {
                    $dates[] = $iso;
                }
            }
        }

        if ($dates === [] && preg_match_all('/\b(\d{1,2}\.\d{1,2}\.\d{4})\b/', $text, $m)) {
            foreach ($m[1] as $candidate) {
                $iso = $this->germanDateToIso($candidate);
                if ($iso !== null) {
                    $dates[] = $iso;
                }
            }
        }

        if ($dates !== []) {
            if ($yearHint !== null && $yearHint !== '') {
                foreach ($dates as $iso) {
                    if (str_starts_with($iso, $yearHint . '-')) {
                        return $iso;
                    }
                }
            }

            return $dates[0];
        }

        return null;
    }

    public function parseInvoiceNumber(string $text): ?string
    {
        if (preg_match('/(?:Rechnungsnummer|Rechnung\s*Nr\.?|Invoice\s*(?:No\.?|Number))\s*:?\s*([A-Za-z0-9][A-Za-z0-9\-\/_.]{0,98})/ui', $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function extractLiterals(string $content): array
    {
        $parts = [];
        if (preg_match_all('/\(((?:\\\\.|[^\\\\)])*)\)/', $content, $m)) {
            foreach ($m[1] as $lit) {
                $parts[] = $this->normalizeLiteral($this->unescapePdfString($lit));
            }
        }

        return $parts;
    }

    private function unescapePdfString(string $value): string
    {
        return stripcslashes(str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $value));
    }

    private function normalizeLiteral(string $value): string
    {
        $value = str_replace(["\x80", "\xA4"], '€', $value);
        $value = str_replace("\x00", '', $value);
        if ($value !== '' && ! mb_check_encoding($value, 'UTF-8')) {
            $converted = @mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
            if (is_string($converted) && $converted !== '') {
                $value = $converted;
            }
        }

        return $value;
    }

    private function inflate(string $data): ?string
    {
        $out = @gzuncompress($data);
        if ($out !== false) {
            return $out;
        }
        $out = @gzinflate($data);
        if ($out !== false) {
            return $out;
        }
        $out = @gzinflate(substr($data, 2));

        return $out !== false ? $out : null;
    }

    private function germanDateToIso(string $dmy): ?string
    {
        if (! preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $dmy, $m)) {
            return null;
        }
        $day   = (int) $m[1];
        $month = (int) $m[2];
        $year  = (int) $m[3];
        if ($year < 2000 || $year > 2100 || ! checkdate($month, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    private function germanPriceToCents(string $raw): ?int
    {
        $raw = str_replace(["\xc2\xa0", ' '], '', $raw);
        $raw = str_replace('.', '', $raw);
        $raw = str_replace(',', '.', $raw);
        if (! is_numeric($raw)) {
            return null;
        }
        $cents = (int) round((float) $raw * 100);

        return $cents >= 0 ? $cents : null;
    }
}
