<?php

namespace App\Services;

use App\Models\CustomerModel;
use App\Models\InvoiceModel;
use App\Models\QuoteConfirmationModel;
use App\Models\QuoteModel;
use Config\Wum;

/**
 * Merged document list for dashboard / Dokumentenübersicht
 */
class DocumentOverviewService
{
    private DocumentSessionService $sessionService;

    public function __construct()
    {
        $this->sessionService = new DocumentSessionService();
    }

    public function getRecentDocuments(int $limit = 5): array
    {
        helper('wum');
        $isDemo  = function_exists('wum_is_demo') && wum_is_demo();
        $perType = $limit;

        $quotes   = (new QuoteModel())->getRecent($perType);
        $invoices = (new InvoiceModel())->getRecent($perType);

        $docs = [];
        foreach ($quotes as $q) {
            $docs[] = $this->mapQuote($q);
        }
        foreach ($invoices as $i) {
            $docs[] = $this->mapInvoice($i);
        }

        usort($docs, static function ($a, $b) {
            return strcmp($b['sort_date'], $a['sort_date']);
        });

        if ($isDemo) {
            return $docs;
        }

        return array_slice($docs, 0, $limit);
    }

    public function searchByNumber(string $query): array
    {
        helper('wum');
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $docs = [];
        $quote = (new QuoteModel())->findByNumber($query);
        if ($quote) {
            $docs[] = $this->mapQuote($quote);
        }
        $invoice = (new InvoiceModel())->findByNumber($query);
        if ($invoice) {
            $docs[] = $this->mapInvoice($invoice);
        }

        return $docs;
    }

    /**
     * Filesystem listing of current-year invoice PDFs from shared invoices/{folder}/.
     *
     * @return list<array{folder: string, filename: string, pdf_path: string}>
     */
    public function getCurrentYearInvoiceFiles(?string $year = null): array
    {
        helper('wum');
        if (wum_is_demo()) {
            return [];
        }

        $year = $year ?? date('Y');
        if (! preg_match('/^\d{4}$/', $year)) {
            return [];
        }

        $root = wum_shared_invoices_root();
        if (! is_dir($root)) {
            return [];
        }

        $out = [];
        foreach (glob($root . '*', GLOB_ONLYDIR) ?: [] as $folderPath) {
            $folder = basename($folderPath);
            if ($folder === '' || $folder === '.' || $folder === '..') {
                continue;
            }

            foreach (glob($folderPath . DIRECTORY_SEPARATOR . $year . '*.pdf') ?: [] as $file) {
                if (! is_file($file)) {
                    continue;
                }
                $filename = basename($file);
                $out[]    = [
                    'folder'   => $folder,
                    'filename' => $filename,
                    'pdf_path' => wum_invoice_web_path($folder, $filename),
                ];
            }
        }

        usort($out, static function ($a, $b) {
            return strcmp($b['filename'], $a['filename']);
        });

        return $out;
    }

    private function mapQuote(array $row): array
    {
        helper('wum');
        $wum          = config('Wum');
        $filename     = $row['filename'] ?? null;
        $pdfPath      = $filename
            ? wum_archives_web_prefix() . '/quotes/' . $filename
            : null;
        $confirmation = null;
        $tableReady   = true;
        try {
            $confirmation = (new QuoteConfirmationModel())->findByQuoteId((int) $row['id']);
        } catch (\Throwable) {
            $tableReady   = false;
            $confirmation = null;
        }

        $confirmationPdfPath = null;
        $confirmationUrl     = null;
        $hasConfirmation     = false;

        if ($confirmation !== null) {
            if (! empty($confirmation['submitted_at']) && ! empty($confirmation['pdf_filename'])) {
                $hasConfirmation     = true;
                $confirmationPdfPath = wum_archives_web_prefix() . '/quote-confirmations/' . $confirmation['pdf_filename'];
            } elseif (empty($confirmation['submitted_at']) && ! empty($confirmation['token'])) {
                $confirmationUrl = (new OrderConfirmationService())->getPublicUrl((string) $confirmation['token']);
            }
        } elseif ($tableReady && (int) $row['status'] === Wum::QUOTE_OPEN) {
            $token           = (new OrderConfirmationService())->ensureTokenForQuote((int) $row['id']);
            $confirmationUrl = (new OrderConfirmationService())->getPublicUrl($token);
        }

        return [
            'id'          => (int) $row['id'],
            'type'        => 'quote',
            'type_label'  => 'Angebot',
            'number'      => $row['quote_number'],
            'date'        => $row['quote_date'],
            'sort_date'   => $row['quote_date'] . ' ' . ($row['created_at'] ?? ''),
            'customer'    => $row['company_name'],
            'amount_cents'=> (int) $row['amount_cents'],
            'status'      => (int) $row['status'],
            'status_label'=> $wum->quoteStatusLabels[(int) $row['status']] ?? 'Unbekannt',
            'filename'    => $filename,
            'pdf_path'    => $pdfPath,
            'invoice_id'  => $row['invoice_id'] ?? null,
            'confirmation_pdf_path'      => $confirmationPdfPath,
            'confirmation_url'           => $confirmationUrl,
            'has_confirmation'           => $hasConfirmation,
            'show_decline_confirmation'  => (int) $row['status'] === Wum::QUOTE_ORDERED && $hasConfirmation,
            'show_email_button' => ! (session()->get('is_demo') || session()->get('rights') === 'demo')
                && $this->sessionService->quoteEmailPending($row['meta'] ?? null),
        ];
    }

    private function mapInvoice(array $row): array
    {
        helper('wum');
        $wum      = config('Wum');
        $filename = $row['filename'] ?? null;
        $folder   = null;

        if ($filename && ! wum_is_demo()) {
            $customer = (new CustomerModel())->findByCustomerNumber($row['customer_number'] ?? '');
            $folder   = trim((string) ($customer['invoice_folder'] ?? ''));
            if ($folder === '') {
                $numbers = new DocumentNumberService();
                $folder  = $numbers->findFolderForInvoiceFilename((string) $filename) ?? '';
            }
            if ($folder === '') {
                $folder = null;
            }
        }

        $pdfPath = null;
        if ($filename) {
            $pdfPath = wum_is_demo()
                ? wum_invoice_web_path(null, $filename)
                : ($folder !== null ? wum_invoice_web_path($folder, $filename) : null);
        }

        return [
            'id'          => (int) $row['id'],
            'type'        => 'invoice',
            'type_label'  => 'Rechnung',
            'number'      => $row['invoice_number'],
            'date'        => $row['invoice_date'],
            'sort_date'   => $row['invoice_date'] . ' ' . ($row['created_at'] ?? ''),
            'customer'    => $row['customer_last_name'],
            'amount_cents'=> (int) $row['amount_cents'],
            'status'      => (int) $row['status'],
            'status_label'=> $wum->invoiceStatusLabels[(int) $row['status']] ?? 'Unbekannt',
            'filename'    => $filename,
            'pdf_path'    => $pdfPath,
            'invoice_folder' => $folder,
            'source_quote_id' => $row['source_quote_id'] ?? null,
            'show_email_button' => ! (session()->get('is_demo') || session()->get('rights') === 'demo')
                && $this->sessionService->invoiceEmailRetryNeeded($row['meta'] ?? null),
        ];
    }
}
