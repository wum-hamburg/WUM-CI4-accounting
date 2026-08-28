<?php

namespace App\Services;

use App\Models\CustomerModel;
use App\Models\InvoiceModel;
use Config\Wum;

/**
 * Fast import/repair of outgoing invoices from shared invoice PDFs.
 */
class InvoicePdfSyncService
{
    /**
     * @return array{
     *     imported: int,
     *     updated: int,
     *     skipped: int,
     *     marked_paid: int,
     *     errors: list<string>,
     *     summary: string
     * }
     */
    public function sync(): array
    {
        helper('wum');
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);

        $imported = 0;
        $updated  = 0;
        $skipped  = 0;
        $errors   = [];

        $invoiceModel = new InvoiceModel();
        $customers    = (new CustomerModel())->findAll();

        foreach ($this->listInvoicePdfs() as $file) {
            if ($this->isIgnoredInvoiceFolder($file['folder'])) {
                $skipped++;
                continue;
            }

            $parsed = $this->parsePdfFile($file['path'], $file['folder'], $file['filename']);
            if ($parsed === null) {
                $skipped++;
                $errors[] = $file['filename'] . ': Rechnungsnummer im Dateinamen nicht erkannt.';
                continue;
            }

            $customer = $this->matchCustomer($customers, $file['folder'], $parsed['suffix']);
            $existing = $invoiceModel->findByNumber($parsed['invoice_number']);

            if ($existing) {
                $data = ['filename' => $parsed['filename']];
                if ($parsed['invoice_date'] !== null) {
                    $data['invoice_date'] = $parsed['invoice_date'];
                }
                if ($parsed['amount_cents'] !== null) {
                    $data['amount_cents'] = $parsed['amount_cents'];
                }
                if ($customer !== null) {
                    $data['customer_number']    = (string) $customer['customer_number'];
                    $data['customer_last_name'] = (string) $customer['last_name'];
                }
                if ((int) $existing['status'] !== Wum::INVOICE_CORRECTION) {
                    $data['status'] = Wum::INVOICE_PAID;
                }
                $invoiceModel->update((int) $existing['id'], $data);
                $updated++;
                continue;
            }

            if ($customer === null) {
                $customer = [
                    'customer_number' => '0',
                    'last_name'       => $file['folder'] !== '' ? $file['folder'] : $parsed['suffix'],
                ];
            }

            $invoiceModel->insert([
                'invoice_number'     => $parsed['invoice_number'],
                'amount_cents'       => $parsed['amount_cents'] ?? 0,
                'invoice_date'       => $parsed['invoice_date'] ?? $this->dateFromInvoiceNumber($parsed['invoice_number']),
                'customer_number'    => (string) $customer['customer_number'],
                'customer_last_name' => (string) $customer['last_name'],
                'status'             => Wum::INVOICE_PAID,
                'filename'           => $parsed['filename'],
                'meta'               => null,
                'source_quote_id'    => null,
            ]);
            $imported++;
        }

        $markedPaid = $this->markRemainingOpenPaid($invoiceModel);

        $summary = sprintf(
            '%d importiert, %d aktualisiert, %d übersprungen, %d weitere auf bezahlt gesetzt.',
            $imported,
            $updated,
            $skipped,
            $markedPaid
        );

        return [
            'imported'    => $imported,
            'updated'     => $updated,
            'skipped'     => $skipped,
            'marked_paid' => $markedPaid,
            'errors'      => $errors,
            'summary'     => $summary,
        ];
    }

    /**
     * @return list<array{path: string, folder: string, filename: string}>
     */
    private function listInvoicePdfs(): array
    {
        $root = wum_shared_invoices_root();
        if (! is_dir($root)) {
            return [];
        }

        $out = [];
        foreach (glob($root . '*', GLOB_ONLYDIR) ?: [] as $dir) {
            $folder = basename($dir);
            if ($folder === '.' || $folder === '..' || $this->isIgnoredInvoiceFolder($folder)) {
                continue;
            }
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*.pdf') ?: [] as $path) {
                if (! is_file($path)) {
                    continue;
                }
                $filename = basename($path);
                if (strncasecmp($filename, 'Angebot', 7) === 0) {
                    continue;
                }
                $out[] = [
                    'path'     => $path,
                    'folder'   => $folder,
                    'filename' => $filename,
                ];
            }
        }

        return $out;
    }

    /**
     * @param list<array<string, mixed>> $customers
     * @return array<string, mixed>|null
     */
    private function matchCustomer(array $customers, string $folder, string $suffix): ?array
    {
        $folder  = trim($folder);
        $suffix  = trim($suffix);
        $numbers = new DocumentNumberService();

        if ($folder !== '') {
            foreach ($customers as $customer) {
                if (strcasecmp(trim((string) ($customer['invoice_folder'] ?? '')), $folder) === 0) {
                    return $customer;
                }
            }
        }

        $needles = array_values(array_filter([$suffix, $folder], static fn ($v) => $v !== ''));
        foreach ($needles as $needle) {
            $needleLower = mb_strtolower($needle);
            foreach ($customers as $customer) {
                $fromService = mb_strtolower($numbers->customerSuffix($customer));
                $lastName    = mb_strtolower($numbers->sanitizeFilenamePart((string) ($customer['last_name'] ?? '')));
                $company     = mb_strtolower($numbers->sanitizeFilenamePart(str_replace(' ', '_', (string) ($customer['company'] ?? ''))));
                $folderName  = mb_strtolower(trim((string) ($customer['invoice_folder'] ?? '')));
                if (
                    $needleLower === $fromService
                    || $needleLower === $lastName
                    || $needleLower === $company
                    || $needleLower === $folderName
                ) {
                    return $customer;
                }
            }
        }

        return null;
    }

    private function isIgnoredInvoiceFolder(string $folder): bool
    {
        return strcasecmp(trim($folder), 'wum.hamburg') === 0;
    }

    /**
     * @return array{
     *     invoice_number: string,
     *     invoice_date: ?string,
     *     amount_cents: ?int,
     *     filename: string,
     *     suffix: string
     * }|null
     */
    private function parsePdfFile(string $path, string $folder, string $filename): ?array
    {
        if (! preg_match('/^(\d{6,12})\s*(.*?)\.pdf$/i', $filename, $m)) {
            return null;
        }

        $number = $m[1];
        $suffix = trim((string) $m[2]);
        $textExtractor = new PdfTextExtractor();
        $text          = $textExtractor->extract($path);
        $yearHint      = strlen($number) >= 4 ? substr($number, 0, 4) : null;

        return [
            'invoice_number' => $number,
            'invoice_date'   => $textExtractor->parseInvoiceDate($text, $yearHint) ?? $this->dateFromInvoiceNumber($number),
            'amount_cents'   => $textExtractor->parseNetAmount($text),
            'filename'       => $filename,
            'suffix'         => $suffix !== '' ? $suffix : $folder,
        ];
    }

    private function dateFromInvoiceNumber(string $number): string
    {
        $year  = (int) substr($number, 0, 4);
        $month = strlen($number) >= 6 ? (int) substr($number, 4, 2) : 1;
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }
        if ($month < 1 || $month > 12) {
            $month = 1;
        }

        return sprintf('%04d-%02d-01', $year, $month);
    }

    private function markRemainingOpenPaid(InvoiceModel $invoiceModel): int
    {
        $open  = $invoiceModel->where('status', Wum::INVOICE_OPEN)->findAll();
        $count = 0;
        foreach ($open as $row) {
            $invoiceModel->update((int) $row['id'], ['status' => Wum::INVOICE_PAID]);
            $count++;
        }

        return $count;
    }
}
