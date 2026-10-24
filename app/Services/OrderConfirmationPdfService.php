<?php

namespace App\Services;

use App\Libraries\TcpdfDocument;

/**
 * PDF generation for customer order confirmations (Auftragsbestätigung).
 */
class OrderConfirmationPdfService
{
    public function confirmationFilename(string $quoteNumber): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9\-_]/', '', $quoteNumber) ?? 'Angebot';

        return 'Auftragsbestaetigung' . $safe . '.pdf';
    }

    /**
     * @param array<string, mixed> $quote
     * @param array<string, mixed> $customer
     * @param array{notes: string, confirmation_date: string, signature_data: string, submitted_at: string} $formData
     */
    public function generate(array $quote, array $customer, array $formData, string $outputPath): bool
    {
        helper(['wum', 'text']);
        $pdfService = new DocumentPdfService();
        $signature  = $this->prepareSignatureImage((string) ($formData['signature_data'] ?? ''));

        $notes = trim((string) ($formData['notes'] ?? ''));
        $html  = view('pdf/order_confirmation', [
            'recipientHtml'      => $pdfService->buildRecipientHtml($customer),
            'customerNumber'     => $customer['customer_number'] ?? '',
            'quoteNumber'        => $quote['quote_number'] ?? '',
            'quoteDateDe'        => format_german_date((string) ($quote['quote_date'] ?? date('Y-m-d'))),
            'confirmationDateDe' => format_german_date((string) $formData['confirmation_date']),
            'notesHtml'          => $notes !== '' ? nl2br(esc($notes)) : '<em>— keine —</em>',
            'signatureImgTag'    => $signature['html'],
            'privacyConsented'   => ! empty($formData['privacy_consented']),
            'privacyPolicyUrl'   => config('Wum')->privacyPolicyUrl,
            'submittedAtDe'      => format_german_date(substr((string) $formData['submitted_at'], 0, 10))
                . ' ' . date('H:i', strtotime((string) $formData['submitted_at'])),
        ]);

        $ok = (new TcpdfDocument())->generate(
            $html,
            'Auftragsbestätigung ' . ($quote['quote_number'] ?? ''),
            $outputPath
        );

        return $ok;
    }

    /**
     * TCPDF writeHTML expects inline images as src="@{base64}" (not file paths).
     *
     * @return array{html: string}
     */
    private function prepareSignatureImage(string $signatureData): array
    {
        $binary = $this->decodeSignatureBinary($signatureData);
        if ($binary === null) {
            return ['html' => ''];
        }

        return [
            'html' => '<img src="@' . base64_encode($binary) . '" style="height:60px;" alt="Unterschrift">',
        ];
    }

    private function decodeSignatureBinary(string $signatureData): ?string
    {
        $signatureData = trim($signatureData);
        if ($signatureData === '') {
            return null;
        }

        if (str_starts_with($signatureData, 'data:image/')) {
            if (! preg_match('#^data:image/(?:png|jpeg|jpg);base64,(.+)$#is', $signatureData, $m)) {
                return null;
            }
            $binary = base64_decode(preg_replace('/\s+/', '', $m[1]), true);

            return $binary !== false ? $binary : null;
        }

        return null;
    }
}
