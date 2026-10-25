<?php

namespace App\Services;

use App\Models\ArticleModel;

/**
 * Document wizard session handling / Beleg-Wizard Session
 */
class DocumentSessionService
{
    public const DEFAULT_UNIT = 'Stck.';

    public const UNIT_OPTIONS = ['', 'psch.', 'Std.', 'AW', 'Stck.'];

    private const KEYS = [
        'document_type', 'document_step', 'customer_number',
        'line_items', 'text_keys', 'total_cents', 'discount_cents', 'discount_percent',
        'correction_of', 'source_quote_id', 'convert_post_data',
        'quote_preview_ready', 'invoice_preview_ready',
    ];

    public function reset(): void
    {
        foreach (self::KEYS as $key) {
            session()->remove($key);
        }
    }

    public function getStep(): int
    {
        return (int) (session()->get('document_step') ?? 2);
    }

    public function setStep(int $step): void
    {
        session()->set('document_step', $step);
    }

    public function isStepAllowed(int $step): bool
    {
        return match ($step) {
            2       => session()->get('document_type') !== null,
            3       => session()->get('document_type') && session()->get('customer_number'),
            4       => session()->get('document_type') && session()->get('customer_number')
                && ($this->hasLineItems() || session()->get('correction_of'))
                && session()->get('text_keys') !== null,
            default => false,
        };
    }

    public function hasLineItems(): bool
    {
        $items = $this->normalizeLineItems(session()->get('line_items') ?? []);
        return $items !== [];
    }

    public function stepTitle(int $step): string
    {
        $type = session()->get('document_type');
        return match ($step) {
            2       => 'Kunde',
            3       => $type === 'quote' ? 'Positionen & Texte' : 'Artikel & Texte',
            4       => 'Anpassung',
            default => '',
        };
    }

    public function typeLabel(?string $type): string
    {
        return match ($type) {
            'quote'   => 'Angebot',
            'invoice' => 'Rechnung',
            default   => '',
        };
    }

    public function normalizeUnit(mixed $unit): string
    {
        $unit = trim((string) $unit);
        if ($unit === '') {
            return '';
        }
        if (in_array($unit, self::UNIT_OPTIONS, true)) {
            return $unit;
        }
        return self::DEFAULT_UNIT;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function normalizeLineItems(mixed $lineItems): array
    {
        if (!is_array($lineItems) || $lineItems === []) {
            return [];
        }

        if (!isset($lineItems[0]) || !is_array($lineItems[0])) {
            return [];
        }

        $result = [];
        foreach ($lineItems as $item) {
            $normalized = $this->normalizePositionItem($item);
            if ($normalized !== null) {
                $result[] = $normalized;
            }
        }

        return $result;
    }

    public function hasBillablePositions(array $positions): bool
    {
        helper('wum');
        $forQuote   = session()->get('document_type') === 'quote';
        $normalized = $this->normalizeLineItems($positions);

        foreach ($normalized as $item) {
            $qty = (int) ($item['qty'] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            if ($forQuote && wum_is_optional_position($item)) {
                continue;
            }
            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>|null
     */
    private function normalizePositionItem(array $item): ?array
    {
        if (empty($item['type'])) {
            return null;
        }

        helper('wum');

        $qty   = (int) ($item['qty'] ?? 0);
        $title = $this->positionTitleFromItem($item);
        $isOptional = $title !== '' && wum_parse_optional_title($title) !== null;

        if (!$isOptional && $qty <= 0) {
            return null;
        }

        if ($item['type'] === 'custom' && $title === '') {
            return null;
        }

        $base = [
            'qty'             => $qty,
            'unit'            => $this->normalizeUnit($item['unit'] ?? self::DEFAULT_UNIT),
            'position_number' => $this->normalizePositionNumber($item['position_number'] ?? null),
            'position_source' => (string) ($item['position_source'] ?? 'quote'),
        ];

        if ($isOptional) {
            $base['is_optional'] = true;
        }

        if ($item['type'] === 'catalog' && !empty($item['article_number'])) {
            return array_merge($base, [
                'type'           => 'catalog',
                'article_number' => (string) $item['article_number'],
                'title'          => isset($item['title']) ? (string) $item['title'] : null,
                'price_cents'    => isset($item['price_cents']) ? (int) $item['price_cents'] : null,
            ]);
        }

        if ($item['type'] === 'custom' && $title !== '') {
            return array_merge($base, [
                'type'        => 'custom',
                'title'       => $title,
                'price_cents' => (int) ($item['price_cents'] ?? 0),
            ]);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function positionTitleFromItem(array $item): string
    {
        if (($item['type'] ?? '') === 'custom') {
            return trim((string) ($item['title'] ?? ''));
        }

        return trim((string) ($item['title'] ?? ''));
    }

    public function setLineItems(array $positions, bool $preserveNumbers = false): void
    {
        $normalized = $this->normalizeLineItems($positions);
        session()->set(
            'line_items',
            $preserveNumbers ? $this->fillMissingPositionNumbers($normalized) : $this->assignPositionNumbers($normalized)
        );
    }

    public function recalculateTotal(): int
    {
        helper('wum');
        $positions    = $this->normalizeLineItems(session()->get('line_items') ?? []);
        $articleModel = new ArticleModel();
        $forQuote     = session()->get('document_type') === 'quote';
        $total        = 0;

        foreach ($positions as $item) {
            if ($forQuote && wum_is_optional_position($item)) {
                continue;
            }
            if ($item['type'] === 'custom') {
                $total += $item['qty'] * ($item['price_cents'] ?? 0);
                continue;
            }
            if (isset($item['price_cents'])) {
                $total += $item['qty'] * (int) $item['price_cents'];
                continue;
            }
            $article = $articleModel->findByArticleNumber($item['article_number']);
            if ($article) {
                $total += $item['qty'] * (int) $article['price_cents'];
            }
        }

        session()->set('total_cents', $total);
        return $total;
    }

    public function syncQuantitiesFromPost(array $quantities): void
    {
        $positions = $this->normalizeLineItems(session()->get('line_items') ?? []);
        $custom    = array_values(array_filter($positions, static fn ($p) => $p['type'] === 'custom'));

        $catalog = [];
        foreach ($quantities as $nr => $qty) {
            $qty = (int) $qty;
            if ($qty > 0) {
                $catalog[] = [
                    'type'            => 'catalog',
                    'article_number'  => (string) $nr,
                    'qty'             => $qty,
                    'unit'            => self::DEFAULT_UNIT,
                    'position_source' => 'quote',
                ];
            }
        }

        if ($catalog !== [] || $custom !== []) {
            session()->set('line_items', $this->assignPositionNumbers(array_merge($custom, $catalog)));
        }
        session()->remove(['discount_cents', 'discount_percent']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function parseLineItemsFromPost(array $post): array
    {
        if (isset($post['line_position_number']) && is_array($post['line_position_number'])) {
            return $this->parseUnifiedLinesFromPost($post);
        }

        return $this->parsePositionsFromPost($post);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseUnifiedLinesFromPost(array $post): array
    {
        helper('wum');
        $positions = [];
        $numbers   = $post['line_position_number'] ?? [];
        $types     = $post['line_type'] ?? [];
        $titles    = $post['line_title'] ?? [];
        $qtys      = $post['line_qty'] ?? [];
        $units     = $post['line_unit'] ?? [];
        $prices    = $post['line_price'] ?? [];
        $articles  = $post['line_article_number'] ?? [];
        $sources   = $post['line_position_source'] ?? [];

        foreach ($numbers as $i => $positionNumber) {
            $qty   = (int) ($qtys[$i] ?? 0);
            $type  = (string) ($types[$i] ?? 'custom');
            $title = trim((string) ($titles[$i] ?? ''));
            $isOptional = $title !== '' && wum_parse_optional_title($title) !== null;

            if (!$isOptional && $qty <= 0) {
                continue;
            }
            $row = [
                'type'            => $type,
                'qty'             => $qty,
                'unit'            => $this->normalizeUnit($units[$i] ?? self::DEFAULT_UNIT),
                'position_number' => $this->normalizePositionNumber($positionNumber),
                'position_source' => (string) ($sources[$i] ?? 'quote'),
            ];
            if ($type === 'catalog') {
                $row['article_number'] = (string) ($articles[$i] ?? '');
                $row['title']          = $title;
                $row['price_cents']    = $this->parseEuroToCents($prices[$i] ?? '0');
            } else {
                if ($title === '') {
                    continue;
                }
                $row['title']       = $title;
                $row['price_cents'] = $this->parseEuroToCents($prices[$i] ?? '0');
            }
            $normalized = $this->normalizePositionItem($row);
            if ($normalized !== null) {
                $positions[] = $normalized;
            }
        }

        return $this->fillMissingPositionNumbers($positions);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function parsePositionsFromPost(array $post): array
    {
        helper('wum');
        $positions    = [];
        $customTitles = $post['custom_title'] ?? [];
        $customQtys   = $post['custom_qty'] ?? [];
        $customPrices = $post['custom_price'] ?? [];
        $customUnits  = $post['custom_unit'] ?? [];

        if (is_array($customTitles)) {
            foreach ($customTitles as $i => $title) {
                $title = trim((string) $title);
                $qty   = (int) ($customQtys[$i] ?? 0);
                $price = $this->parseEuroToCents($customPrices[$i] ?? '0');
                $isOptional = $title !== '' && wum_parse_optional_title($title) !== null;
                if ($title !== '' && ($isOptional || $qty > 0)) {
                    $positions[] = [
                        'type'            => 'custom',
                        'title'           => $title,
                        'qty'             => $qty,
                        'price_cents'     => $price,
                        'unit'            => $this->normalizeUnit($customUnits[$i] ?? self::DEFAULT_UNIT),
                        'position_source' => 'quote',
                    ];
                }
            }
        }

        $quantities = $post['quantity'] ?? [];
        if (is_array($quantities)) {
            foreach ($quantities as $nr => $qty) {
                $qty = (int) $qty;
                if ($qty > 0) {
                    $positions[] = [
                        'type'            => 'catalog',
                        'article_number'  => (string) $nr,
                        'qty'             => $qty,
                        'unit'            => self::DEFAULT_UNIT,
                        'position_source' => 'quote',
                    ];
                }
            }
        }

        return $this->assignPositionNumbers($this->normalizeLineItems($positions));
    }

    /**
     * @param list<array<string, mixed>> $quotePositions
     * @return list<array<string, mixed>>
     */
    public function parseConvertPost(array $post, array $quotePositions): array
    {
        $billQtys = $post['bill_qty'] ?? [];
        $chosen   = [];

        foreach ($quotePositions as $i => $pos) {
            $qty = (int) ($billQtys[(string) $i] ?? $billQtys[$pos['position_number'] ?? ''] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $row = $pos;
            $row['qty']             = $qty;
            $row['position_source'] = 'quote';
            $normalized             = $this->normalizePositionItem($row);
            if ($normalized !== null) {
                $chosen[] = $normalized;
            }
        }

        $extras = $this->parseExtraPositionsFromPost($post, $quotePositions);

        return $this->fillMissingPositionNumbers(array_merge($chosen, $extras));
    }

    /**
     * @param list<array<string, mixed>> $quotePositions
     * @return list<array<string, mixed>>
     */
    public function parseExtraPositionsFromPost(array $post, array $quotePositions): array
    {
        $titles = $post['extra_title'] ?? [];
        $qtys   = $post['extra_qty'] ?? [];
        $prices = $post['extra_price'] ?? [];
        $units  = $post['extra_unit'] ?? [];
        $extras = [];

        if (!is_array($titles)) {
            return [];
        }

        $nextNumber = $this->nextPositionNumberValue($quotePositions);

        foreach ($titles as $i => $title) {
            $title = trim((string) $title);
            $qty   = (int) ($qtys[$i] ?? 0);
            if ($title === '' || $qty <= 0) {
                continue;
            }
            $normalized = $this->normalizePositionItem([
                'type'            => 'custom',
                'title'           => $title,
                'qty'             => $qty,
                'price_cents'     => $this->parseEuroToCents($prices[$i] ?? '0'),
                'unit'            => $this->normalizeUnit($units[$i] ?? self::DEFAULT_UNIT),
                'position_number' => $this->formatPositionNumber($nextNumber++),
                'position_source' => 'invoice_extra',
            ]);
            if ($normalized !== null) {
                $extras[] = $normalized;
            }
        }

        return $extras;
    }

    /**
     * @param list<array<string, mixed>> $quotePositions
     * @return list<array<string, mixed>>
     */
    public function applyInvoicedQuantities(array $quotePositions, array $invoiced): array
    {
        $result = [];
        foreach ($quotePositions as $pos) {
            $number  = (string) ($pos['position_number'] ?? '');
            $offered = (int) ($pos['qty'] ?? 0);
            $already = (int) ($invoiced[$number] ?? 0);
            $row     = $pos;
            $row['offered_qty']   = $offered;
            $row['invoiced_qty']  = $already;
            $row['remaining_qty'] = max(0, $offered - $already);
            $result[]             = $row;
        }
        return $result;
    }

    public function getInvoicedQuantitiesFromMeta(?string $meta): array
    {
        $data     = $this->decodeMeta($meta);
        $invoiced = $data['invoiced'] ?? [];
        return is_array($invoiced) ? $invoiced : [];
    }

    /**
     * @param list<array<string, mixed>> $billedPositions
     */
    public function recordInvoicedQuantities(string $quoteMeta, array $billedPositions): string
    {
        $data     = $this->decodeMeta($quoteMeta);
        $invoiced = is_array($data['invoiced'] ?? null) ? $data['invoiced'] : [];

        foreach ($billedPositions as $pos) {
            if (($pos['position_source'] ?? 'quote') === 'invoice_extra') {
                continue;
            }
            $number = (string) ($pos['position_number'] ?? '');
            if ($number === '') {
                continue;
            }
            $invoiced[$number] = (int) ($invoiced[$number] ?? 0) + (int) ($pos['qty'] ?? 0);
        }

        $data['invoiced'] = $invoiced;
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function decodeMeta(?string $meta): array
    {
        if ($meta === null || $meta === '') {
            return [];
        }
        $data = json_decode($meta, true);
        return is_array($data) ? $data : [];
    }

    public function quoteEmailPending(?string $meta): bool
    {
        $data = $this->decodeMeta($meta);

        return array_key_exists('email_sent', $data) && $data['email_sent'] === false;
    }

    public function invoiceEmailRetryNeeded(?string $meta): bool
    {
        $data = $this->decodeMeta($meta);

        return array_key_exists('email_sent', $data) && $data['email_sent'] === false;
    }

    public function markEmailSent(?string $meta): string
    {
        $data               = $this->decodeMeta($meta);
        $data['email_sent'] = true;

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function parseEuroToCents(mixed $value): int
    {
        $value = str_replace(',', '.', trim((string) $value));
        if ($value === '' || !is_numeric($value)) {
            return 0;
        }
        return (int) round((float) $value * 100);
    }

    public function positionsToMeta(array $positions, array $texts = [], array $extra = []): string
    {
        $pdfService = new DocumentPdfService();
        $normalized = $this->normalizeLineItems($positions);
        $stored     = [];

        foreach ($normalized as $item) {
            if ($item['type'] === 'custom') {
                $stored[] = [
                    'type'            => 'custom',
                    'title'           => $item['title'],
                    'qty'             => $item['qty'],
                    'price_cents'     => $item['price_cents'] ?? 0,
                    'unit'            => $item['unit'] ?? self::DEFAULT_UNIT,
                    'position_number' => $item['position_number'] ?? null,
                    'position_source' => $item['position_source'] ?? 'quote',
                ];
                continue;
            }
            $resolved = $pdfService->resolveCatalogPosition($item);
            if ($resolved) {
                $stored[] = array_merge($resolved, [
                    'unit'            => $item['unit'] ?? self::DEFAULT_UNIT,
                    'position_number' => $item['position_number'] ?? null,
                    'position_source' => $item['position_source'] ?? 'quote',
                ]);
            }
        }

        $preserveMeta = null;
        if (isset($extra['preserve_quote_meta'])) {
            $preserveMeta = $extra['preserve_quote_meta'];
            unset($extra['preserve_quote_meta']);
        }

        $payload = array_merge(['positions' => $stored, 'texts' => $texts], $extra);
        if ($preserveMeta !== null) {
            $existing = $this->decodeMeta($preserveMeta);
            if (isset($existing['invoiced'])) {
                $payload['invoiced'] = $existing['invoiced'];
            }
        }

        return json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    public function positionsFromMeta(?string $meta): array
    {
        $data = $this->decodeMeta($meta);
        if (!isset($data['positions']) || !is_array($data['positions'])) {
            return [];
        }
        return $this->fillMissingPositionNumbers($this->normalizeLineItems($data['positions']));
    }

    /**
     * @param list<array<string, mixed>> $positions
     * @return list<array<string, mixed>>
     */
    public function assignPositionNumbers(array $positions): array
    {
        $normalized = $this->normalizeLineItems($positions);
        $result     = [];
        foreach ($normalized as $index => $item) {
            $item['position_number'] = $this->normalizePositionNumber(
                $item['position_number'] ?? $this->formatPositionNumber($index + 1)
            );
            $result[] = $item;
        }
        return $result;
    }

    /**
     * @param list<array<string, mixed>> $positions
     * @return list<array<string, mixed>>
     */
    public function fillMissingPositionNumbers(array $positions): array
    {
        $normalized = $this->normalizeLineItems($positions);
        $nextNumber = $this->nextPositionNumberValue($normalized);
        $result     = [];

        foreach ($normalized as $item) {
            if (empty($item['position_number'])) {
                $item['position_number'] = $this->formatPositionNumber($nextNumber++);
            }
            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param list<array<string, mixed>> $positions
     */
    public function nextPositionNumberValue(array $positions): int
    {
        $max = 0;
        foreach ($positions as $item) {
            $number = (string) ($item['position_number'] ?? '');
            if ($number !== '' && ctype_digit($number)) {
                $max = max($max, (int) $number);
            }
        }
        return $max + 1;
    }

    public function formatPositionNumber(int $number): string
    {
        return str_pad((string) max(1, $number), 3, '0', STR_PAD_LEFT);
    }

    private function normalizePositionNumber(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (ctype_digit($value)) {
            return $this->formatPositionNumber((int) $value);
        }
        return $value;
    }
}
