<?php

namespace App\Services;

/**
 * Extract embedded ZUGFeRD / Factur-X XML from PDF files.
 */
class PdfEmbeddedXmlExtractor
{
    private const XML_NAMES = [
        'factur-x.xml',
        'zugferd-invoice.xml',
        'ZUGFeRD-invoice.xml',
        'xrechnung.xml',
    ];

    public function extract(string $path): ?string
    {
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }

        foreach (self::XML_NAMES as $name) {
            $xml = $this->extractNamedAttachment($raw, $name);
            if ($xml !== null) {
                return $xml;
            }
        }

        return $this->extractCrossIndustryInvoice($raw);
    }

    private function extractNamedAttachment(string $raw, string $filename): ?string
    {
        $pattern = '/' . preg_quote($filename, '/') . '/i';
        if (! preg_match($pattern, $raw)) {
            return null;
        }

        if (preg_match_all('/stream\r?\n(.*)\r?\nendstream/sU', $raw, $matches)) {
            foreach ($matches[1] as $stream) {
                $decoded = $this->inflate(ltrim($stream, "\r\n"));
                if ($decoded === null || $decoded === '') {
                    continue;
                }
                if (str_contains($decoded, 'CrossIndustryInvoice') || str_starts_with(ltrim($decoded), '<?xml')) {
                    return $this->normalizeXml($decoded);
                }
            }
        }

        return null;
    }

    private function extractCrossIndustryInvoice(string $raw): ?string
    {
        if (preg_match_all('/stream\r?\n(.*)\r?\nendstream/sU', $raw, $matches)) {
            foreach ($matches[1] as $stream) {
                $decoded = $this->inflate(ltrim($stream, "\r\n"));
                if ($decoded === null || $decoded === '') {
                    continue;
                }
                if (str_contains($decoded, 'CrossIndustryInvoice')) {
                    return $this->normalizeXml($decoded);
                }
            }
        }

        if (preg_match('/(<\?xml[^>]*>.*CrossIndustryInvoice.*)/s', $raw, $m)) {
            return $this->normalizeXml($m[1]);
        }

        return null;
    }

    private function normalizeXml(string $xml): string
    {
        $xml = trim($xml);
        if (! str_starts_with($xml, '<?xml')) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $xml;
        }

        return $xml;
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
}
