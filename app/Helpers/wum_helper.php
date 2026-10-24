<?php

/**
 * WUM helper functions / Hilfsfunktionen
 */

if (!function_exists('format_price')) {
    /**
     * Format cents as German price string.
     */
    function format_price(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.') . ' €';
    }
}

if (!function_exists('format_german_date')) {
    function format_german_date(string $date): string
    {
        $ts = strtotime($date);
        return $ts ? date('d.m.Y', $ts) : $date;
    }
}

if (!function_exists('wum_unit_options')) {
    /**
     * @return list<string>
     */
    function wum_unit_options(): array
    {
        return \App\Services\DocumentSessionService::UNIT_OPTIONS;
    }
}

if (!function_exists('wum_unit_select')) {
    function wum_unit_select(string $name, string $selected = 'Stck.', string $class = 'form-select form-select-sm'): string
    {
        $html = '<select name="' . esc($name) . '" class="' . esc($class) . '">';
        foreach (wum_unit_options() as $unit) {
            $label = $unit === '' ? '—' : $unit;
            $html .= '<option value="' . esc($unit) . '"' . ($unit === $selected ? ' selected' : '') . '>' . esc($label) . '</option>';
        }
        return $html . '</select>';
    }
}

if (!function_exists('wum_parse_optional_title')) {
    /**
     * Parse Eventual:/Alternativ: prefix from a position title.
     *
     * @return array{label: string, detail: string}|null
     */
    function wum_parse_optional_title(string $title): ?array
    {
        if (preg_match('/^\s*(Eventual|Alternativ)\s*:\s*(.*)$/iu', $title, $matches) !== 1) {
            return null;
        }

        $kind  = mb_strtolower($matches[1]);
        $label = $kind === 'alternativ' ? 'Alternativ:' : 'Eventual:';

        return [
            'label'  => $label,
            'detail' => trim($matches[2]),
        ];
    }
}

if (!function_exists('wum_position_title')) {
    function wum_position_title(array $item): string
    {
        return trim((string) ($item['title'] ?? ''));
    }
}

if (!function_exists('wum_is_optional_position')) {
    function wum_is_optional_position(array $item): bool
    {
        return wum_parse_optional_title(wum_position_title($item)) !== null;
    }
}

if (!function_exists('wum_optional_display_total_cents')) {
    function wum_optional_display_total_cents(int $qty, string $unit, int $priceCents): int
    {
        $unit = trim($unit);
        if ($qty <= 0 && $unit === 'psch.') {
            return $priceCents;
        }

        return $qty * $priceCents;
    }
}

if (!function_exists('wum_format_optional_detail_line')) {
    function wum_format_optional_detail_line(int $qty, string $unit, string $detail, int $priceCents): string
    {
        $unit = trim($unit);
        if ($unit === '') {
            $unit = \App\Services\DocumentSessionService::DEFAULT_UNIT;
        }

        if ($qty <= 0 && $unit === 'psch.') {
            return 'psch. ' . $detail . ' ' . format_price($priceCents) . ' (' . format_price($priceCents) . ')';
        }

        $lineTotal = $qty * $priceCents;

        return trim((string) $qty) . ' ' . $unit . ' ' . $detail . ' ' . format_price($priceCents) . ' (' . format_price($lineTotal) . ')';
    }
}

if (!function_exists('wum_is_demo')) {
    function wum_is_demo(): bool
    {
        return (bool) (session()->get('is_demo') ?? false)
            || session()->get('rights') === 'demo';
    }
}

if (!function_exists('wum_is_offline')) {
    /**
     * Local/test host (Windows drive or localhost) vs. live server.
     */
    function wum_is_offline(): bool
    {
        if (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') {
            return true;
        }

        $root = (string) ($_SERVER['DOCUMENT_ROOT'] ?? '');
        if ($root !== '' && isset($root[1]) && $root[1] === ':') {
            return true;
        }

        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $host = preg_replace('/:\d+$/', '', $host) ?? $host;

        return in_array($host, ['localhost', '127.0.0.1', 'wumpi', '::1'], true)
            || str_ends_with($host, '.local');
    }
}

if (! function_exists('wum_menu_is_active')) {
    /**
     * Whether the current request matches a menu route (for active styling).
     */
    function wum_menu_is_active(string $route): bool
    {
        $uri   = trim((string) uri_string(), '/');
        $route = trim($route, '/');

        if ($route === '' || $route === 'dashboard') {
            return $uri === '' || $uri === 'dashboard';
        }

        if ($uri === $route || str_starts_with($uri . '/', $route . '/')) {
            return true;
        }

        // Document wizard continues under /documents after type selection
        if ($uri === 'documents' || str_starts_with($uri, 'quotes/') || str_starts_with($uri, 'invoices/')) {
            $type = session()->get('document_type');
            if ($route === 'documents/quote' && $type === 'quote') {
                return true;
            }
            if ($route === 'documents/invoice' && $type === 'invoice') {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('wum_archives_web_prefix')) {
    /**
     * URL path segment under files/view|archive for PDF storage (quotes / demo).
     */
    function wum_archives_web_prefix(): string
    {
        return wum_is_demo() ? 'demo/archives' : 'archives';
    }
}

if (!function_exists('wum_archives_dir')) {
    /**
     * Absolute directory for quote PDF archives and demo invoice archives (trailing separator).
     */
    function wum_archives_dir(string $type): string
    {
        $type = basename($type);
        $base = wum_is_demo()
            ? WRITEPATH . 'demo' . DIRECTORY_SEPARATOR . 'archives'
            : WRITEPATH . 'archives';

        return $base . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('wum_shared_invoices_root')) {
    /**
     * Shared webroot invoices/ directory (www/invoices or html/invoices).
     */
    function wum_shared_invoices_root(): string
    {
        return rtrim(dirname(ROOTPATH), '/\\') . DIRECTORY_SEPARATOR . 'invoices' . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('wum_invoice_dir')) {
    /**
     * Absolute directory for invoice PDFs (trailing separator).
     * Demo: writable/demo/archives/invoices/; prod: shared invoices/{folder}/.
     */
    function wum_invoice_dir(?string $folder = null): string
    {
        if (wum_is_demo()) {
            return wum_archives_dir('invoices');
        }

        $folder = basename((string) $folder);
        if ($folder === '' || $folder === '.' || $folder === '..') {
            return wum_shared_invoices_root();
        }

        return wum_shared_invoices_root() . $folder . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('wum_ensure_invoice_folder')) {
    /**
     * Create shared invoices/{folder}/ if missing (non-demo).
     */
    function wum_ensure_invoice_folder(string $folder): bool
    {
        if (wum_is_demo()) {
            return true;
        }

        $folder = basename($folder);
        if ($folder === '' || $folder === '.' || $folder === '..') {
            return false;
        }

        $dir = wum_shared_invoices_root() . $folder;
        if (is_dir($dir)) {
            return true;
        }

        return mkdir($dir, 0755, true);
    }
}

if (!function_exists('wum_invoice_web_path')) {
    /**
     * Relative storage key for an invoice PDF (resolved by wum_pdf_url).
     * Demo: demo/archives/invoices/{file}
     * Prod: shared/invoices/{folder}/{file}
     */
    function wum_invoice_web_path(?string $folder, string $filename): string
    {
        $filename = basename($filename);
        if (wum_is_demo()) {
            return 'demo/archives/invoices/' . $filename;
        }

        $folder = basename((string) $folder);

        return 'shared/invoices/' . $folder . '/' . $filename;
    }
}

if (!function_exists('wum_pdf_url')) {
    /**
     * Browser URL for a PDF path from wum_invoice_web_path / archives.
     * Always authenticated files/view (staff UI); never a public /invoices/ link.
     */
    function wum_pdf_url(string $webPath): string
    {
        $webPath = str_replace(['..', '\\'], '', trim($webPath));
        $webPath = trim($webPath, '/');
        if ($webPath === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $webPath) === 1) {
            return $webPath;
        }

        $segments = array_map('rawurlencode', explode('/', $webPath));

        return site_url('files/view/' . implode('/', $segments));
    }
}

if (!function_exists('wum_use_public_invoice_urls')) {
    /**
     * Staff PDFs always use files/view; public invoice URLs stay disabled.
     */
    function wum_use_public_invoice_urls(): bool
    {
        return false;
    }
}
