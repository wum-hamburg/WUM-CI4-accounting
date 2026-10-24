<?php

namespace App\Services;

use App\Models\ArticleModel;

/**
 * Build PDF HTML for invoices and quotes / PDF-HTML aufbauen
 */
class DocumentPdfService
{
    /**
     * Build PDF line items from normalized positions.
     *
     * @return list<array{position_number: string, title: string, qty: int, unit: string, price_cents: int, total_cents: int}>
     */
    public function buildLineItems(array $lineItems): array
    {
        helper('wum');
        $sessionService = new DocumentSessionService();
        $positions      = $sessionService->normalizeLineItems($lineItems);
        $forQuote       = session()->get('document_type') === 'quote';
        $posten         = [];

        foreach ($positions as $item) {
            if ($item['type'] === 'custom') {
                $priceCents = (int) ($item['price_cents'] ?? 0);
                $title      = (string) $item['title'];
                $parsed     = wum_parse_optional_title($title);

                if ($parsed !== null && $forQuote) {
                    $qty  = (int) $item['qty'];
                    $unit = (string) ($item['unit'] ?? DocumentSessionService::DEFAULT_UNIT);
                    $posten[] = [
                        'is_optional'         => true,
                        'optional_prefix'     => $parsed['label'],
                        'optional_title'      => $parsed['detail'],
                        'position_number'     => (string) ($item['position_number'] ?? ''),
                        'title'               => $title,
                        'qty'                 => $qty,
                        'unit'                => $unit,
                        'price_cents'         => $priceCents,
                        'display_total_cents' => wum_optional_display_total_cents($qty, $unit, $priceCents),
                        'total_cents'         => 0,
                    ];
                    continue;
                }

                $posten[] = [
                    'position_number' => (string) ($item['position_number'] ?? ''),
                    'title'           => $title,
                    'qty'             => (int) $item['qty'],
                    'unit'            => (string) ($item['unit'] ?? DocumentSessionService::DEFAULT_UNIT),
                    'price_cents'     => $priceCents,
                    'total_cents'     => (int) $item['qty'] * $priceCents,
                ];
                continue;
            }
            $resolved = $this->resolveCatalogPosition($item);
            if ($resolved) {
                $title  = (string) $resolved['title'];
                $parsed = wum_parse_optional_title($title);

                if ($parsed !== null && $forQuote) {
                    $qty        = (int) $resolved['qty'];
                    $unit       = (string) ($resolved['unit'] ?? DocumentSessionService::DEFAULT_UNIT);
                    $priceCents = (int) $resolved['price_cents'];
                    $posten[]   = [
                        'is_optional'         => true,
                        'optional_prefix'     => $parsed['label'],
                        'optional_title'      => $parsed['detail'],
                        'position_number'     => (string) ($resolved['position_number'] ?? ''),
                        'title'               => $title,
                        'qty'                 => $qty,
                        'unit'                => $unit,
                        'price_cents'         => $priceCents,
                        'display_total_cents' => wum_optional_display_total_cents($qty, $unit, $priceCents),
                        'total_cents'         => 0,
                    ];
                    continue;
                }

                $posten[] = [
                    'position_number' => (string) ($resolved['position_number'] ?? ''),
                    'title'           => $title,
                    'qty'             => (int) $resolved['qty'],
                    'unit'            => (string) ($resolved['unit'] ?? DocumentSessionService::DEFAULT_UNIT),
                    'price_cents'     => (int) $resolved['price_cents'],
                    'total_cents'     => (int) $resolved['qty'] * (int) $resolved['price_cents'],
                ];
            }
        }

        return $posten;
    }

    /**
     * Resolve catalog position to storable/renderable data.
     */
    public function resolveCatalogPosition(array $item): ?array
    {
        if (($item['type'] ?? '') !== 'catalog' || empty($item['article_number'])) {
            return null;
        }
        $qty = (int) ($item['qty'] ?? 0);
        if ($qty <= 0) {
            return null;
        }

        $articleModel = new ArticleModel();
        $article      = $articleModel->findByArticleNumber($item['article_number']);

        if ($article) {
            return [
                'type'            => 'catalog',
                'article_number'  => $item['article_number'],
                'title'           => !empty($item['title']) ? (string) $item['title'] : $article['title'],
                'qty'             => $qty,
                'price_cents'     => isset($item['price_cents']) ? (int) $item['price_cents'] : (int) $article['price_cents'],
                'position_number' => $item['position_number'] ?? null,
                'unit'            => $item['unit'] ?? DocumentSessionService::DEFAULT_UNIT,
            ];
        }

        if (!empty($item['title']) && isset($item['price_cents'])) {
            return [
                'type'            => 'catalog',
                'article_number'  => $item['article_number'],
                'title'           => $item['title'],
                'qty'             => $qty,
                'price_cents'     => (int) $item['price_cents'],
                'position_number' => $item['position_number'] ?? null,
                'unit'            => $item['unit'] ?? DocumentSessionService::DEFAULT_UNIT,
            ];
        }

        return null;
    }

    public function buildRecipientHtml(array $customer): string
    {
        helper('text');
        $name = trim((string) ($customer['company'] ?? ''));
        if ($name === '') {
            $name = trim($customer['first_name'] . ' ' . $customer['last_name']);
        }
        return esc($name) . '<br>' . esc($customer['street']) . '<br><br>'
            . esc($customer['postal_code']) . ' ' . esc($customer['city']);
    }

    public function buildHtml(
        array $customer,
        array $lineItems,
        int $totalCents,
        string $documentLabel,
        ?string $number,
        string $date,
        string $headerHtml,
        string $introBeforeItems,
        string $footerText,
        ?int $discountCents = null,
        ?int $discountPercent = null,
        ?string $correctionOf = null
    ): string {
        helper(['wum', 'text']);

        return view('pdf/document', [
            'recipient'        => $this->buildRecipientHtml($customer),
            'customerNumber'   => $customer['customer_number'],
            'dateLabel'        => $documentLabel === 'Rechnung' ? 'Rechnungsdatum' : 'Angebotsdatum',
            'date'             => $date,
            'documentLabel'    => $documentLabel,
            'number'           => $number,
            'correctionOf'     => $correctionOf,
            'headerHtml'       => $headerHtml,
            'introBeforeItems' => $introBeforeItems,
            'lineItems'        => $lineItems,
            'discountCents'    => $discountCents,
            'discountPercent'  => $discountPercent,
            'totalCents'       => $totalCents,
            'footerText'       => $footerText,
        ]);
    }
}
