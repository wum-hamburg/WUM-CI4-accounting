<?php

namespace App\Services;

use App\Models\CustomerModel;
use App\Models\InvoiceModel;

/**
 * Invoice and quote number generation / Nummernvergabe
 */
class DocumentNumberService
{
    /**
     * Next invoice number: YYYYMM + continuous 3-digit sequence (legacy WUM).
     * Sequence does NOT reset on month/year change — only the YYYYMM prefix updates.
     * Example: last 202607021 in August 2026 → 202608022.
     */
    public function nextInvoiceNumber(?string $lastNumber): string
    {
        $year  = date('Y');
        $month = date('m');
        if ($lastNumber === null || $lastNumber === '' || strlen($lastNumber) < 9) {
            return $year . $month . '001';
        }

        $seq = (int) substr($lastNumber, 6, 3);
        $seq++;

        return match (strlen((string) $seq)) {
            1       => $year . $month . '00' . $seq,
            2       => $year . $month . '0' . $seq,
            default => $year . $month . $seq,
        };
    }

    public function nextQuoteNumber(?string $lastNumber): string
    {
        $year = date('Y');
        if ($lastNumber === null || $lastNumber === '' || strlen($lastNumber) < 6 || substr($lastNumber, 0, 4) !== $year) {
            return $year . '01';
        }
        $nr = (int) substr($lastNumber, 4, 2);
        $nr++;
        if ($nr > 99) {
            $nr = 99;
        }
        return $year . str_pad((string) $nr, 2, '0', STR_PAD_LEFT);
    }

    public function sanitizeFilenamePart(string $part): string
    {
        $part = preg_replace('/[^a-zA-Z0-9\-_äöüÄÖÜß]/u', '', $part) ?? '';
        if (strlen($part) > 30) {
            $part = substr($part, 0, 30);
        }
        return $part !== '' ? $part : 'customer';
    }

    /**
     * alpha_dash folder slug for customers.invoice_folder (no umlauts).
     */
    public function invoiceFolderSlug(array $customer): string
    {
        $raw = $this->customerSuffix($customer);
        $map = [
            'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue',
            'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue', 'ß' => 'ss',
        ];
        $raw = strtr($raw, $map);
        $raw = preg_replace('/[^A-Za-z0-9_-]/', '', $raw) ?? '';
        $raw = strtolower($raw);
        if (strlen($raw) > 255) {
            $raw = substr($raw, 0, 255);
        }

        return $raw !== '' ? $raw : 'customer';
    }

    /**
     * Resolve production invoice folder: DB field, correction inherit, disk match, or new slug.
     * Demo returns null (archives path). Empty string is never returned.
     */
    public function resolveInvoiceFolder(array $customer, ?string $correctionOf = null): ?string
    {
        helper('wum');
        if (function_exists('wum_is_demo') && wum_is_demo()) {
            return null;
        }

        $existing = trim((string) ($customer['invoice_folder'] ?? ''));
        if ($existing !== '') {
            return basename($existing);
        }

        if ($correctionOf !== null && $correctionOf !== '') {
            $fromCorrection = $this->folderFromOriginalInvoice($correctionOf);
            if ($fromCorrection !== null) {
                return $fromCorrection;
            }
        }

        $slug = $this->invoiceFolderSlug($customer);
        $matched = $this->matchExistingDiskFolder($slug);
        if ($matched !== null) {
            return $matched;
        }

        return $slug;
    }

    /**
     * Persist folder on customer when missing; create directory.
     *
     * @return string|null Folder name (non-demo) or null in demo
     */
    public function ensureCustomerInvoiceFolder(array &$customer, ?string $correctionOf = null): ?string
    {
        helper('wum');
        if (function_exists('wum_is_demo') && wum_is_demo()) {
            return null;
        }

        $folder = $this->resolveInvoiceFolder($customer, $correctionOf);
        if ($folder === null || $folder === '') {
            return null;
        }

        $customer['invoice_folder'] = $folder;

        $id = (int) ($customer['id'] ?? 0);
        if ($id <= 0) {
            $number = $customer['customer_number'] ?? null;
            if ($number !== null && $number !== '') {
                $row = (new CustomerModel())->findByCustomerNumber($number);
                if ($row) {
                    $id = (int) $row['id'];
                    $customer['id'] = $id;
                }
            }
        }

        if ($id > 0) {
            $row = (new CustomerModel())->find($id);
            $current = trim((string) ($row['invoice_folder'] ?? ''));
            if ($current === '') {
                (new CustomerModel())->update($id, ['invoice_folder' => $folder]);
            }
        }

        wum_ensure_invoice_folder($folder);

        return $folder;
    }

    /**
     * Find which shared invoices/{folder}/ contains a PDF filename.
     */
    public function findFolderForInvoiceFilename(string $filename): ?string
    {
        helper('wum');
        $filename = basename($filename);
        if ($filename === '' || ! str_ends_with(strtolower($filename), '.pdf')) {
            return null;
        }

        $root = wum_shared_invoices_root();
        if (! is_dir($root)) {
            return null;
        }

        foreach (glob($root . '*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (is_file($dir . DIRECTORY_SEPARATOR . $filename)) {
                return basename($dir);
            }
        }

        return null;
    }

    private function folderFromOriginalInvoice(string $invoiceNumber): ?string
    {
        $original = (new InvoiceModel())->findByNumber($invoiceNumber);
        if (! $original) {
            return null;
        }

        $filename = trim((string) ($original['filename'] ?? ''));
        if ($filename !== '') {
            $onDisk = $this->findFolderForInvoiceFilename($filename);
            if ($onDisk !== null) {
                return $onDisk;
            }

            if (preg_match('/^\d{6,12}\s*(.*?)\.pdf$/i', $filename, $m)) {
                $suffix = trim((string) $m[1]);
                if ($suffix !== '') {
                    $slug = $this->toFolderSlug($suffix);
                    $matched = $this->matchExistingDiskFolder($slug);
                    if ($matched !== null) {
                        return $matched;
                    }
                    if ($slug !== '') {
                        return $slug;
                    }
                }
            }
        }

        $origCustomer = (new CustomerModel())->findByCustomerNumber($original['customer_number'] ?? '');
        if ($origCustomer) {
            $folder = trim((string) ($origCustomer['invoice_folder'] ?? ''));
            if ($folder !== '') {
                return basename($folder);
            }
            $slug = $this->invoiceFolderSlug($origCustomer);
            $matched = $this->matchExistingDiskFolder($slug);

            return $matched ?? $slug;
        }

        return null;
    }

    private function matchExistingDiskFolder(string $needle): ?string
    {
        helper('wum');
        $needle = strtolower(trim($needle));
        if ($needle === '') {
            return null;
        }

        $root = wum_shared_invoices_root();
        if (! is_dir($root)) {
            return null;
        }

        foreach (glob($root . '*', GLOB_ONLYDIR) ?: [] as $dir) {
            $name = basename($dir);
            if (strcasecmp($name, $needle) === 0) {
                return $name;
            }
        }

        return null;
    }

    private function toFolderSlug(string $part): string
    {
        $map = [
            'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue',
            'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue', 'ß' => 'ss',
        ];
        $part = strtr($part, $map);
        $part = preg_replace('/[^A-Za-z0-9_-]/', '', $part) ?? '';
        $part = strtolower($part);
        if (strlen($part) > 255) {
            $part = substr($part, 0, 255);
        }

        return $part;
    }

    public function invoiceFilename(string $invoiceNumber, array $customer): string
    {
        $folder = trim((string) ($customer['invoice_folder'] ?? ''));
        if ($folder !== '' && (! function_exists('wum_is_demo') || ! wum_is_demo())) {
            return $invoiceNumber . $this->sanitizeFilenamePart($folder) . '.pdf';
        }

        $suffix = $this->customerSuffix($customer);
        return $invoiceNumber . $suffix . '.pdf';
    }

    public function quoteFilename(string $quoteNumber): string
    {
        return 'Angebot' . $quoteNumber . '.pdf';
    }

    public function customerSuffix(array $customer): string
    {
        $email = trim((string) ($customer['email'] ?? ''));
        if (str_contains($email, '@')) {
            $domain = substr($email, (int) strpos($email, '@') + 1);
            $dot    = strpos($domain, '.');
            if ($dot !== false) {
                return $this->sanitizeFilenamePart(substr($domain, 0, $dot));
            }
            return $this->sanitizeFilenamePart($domain);
        }
        $name = trim((string) ($customer['company'] ?? ''));
        if ($name === '') {
            $name = trim((string) ($customer['last_name'] ?? 'customer'));
        }
        return $this->sanitizeFilenamePart(str_replace(' ', '_', $name));
    }
}
