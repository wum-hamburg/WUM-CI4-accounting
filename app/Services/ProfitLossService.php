<?php

namespace App\Services;

use App\Models\CustomerModel;
use App\Models\IncomingInvoiceModel;
use App\Models\InvoiceModel;
use Config\Wum;

/**
 * Kleinunternehmer profit-and-loss (EÜR-style) for a tax year.
 */
class ProfitLossService
{
    public const FIRST_TAX_YEAR = 2025;

    /**
     * @return list<int>
     */
    public function taxYears(): array
    {
        $current = (int) date('Y');
        $from    = self::FIRST_TAX_YEAR;
        if ($current < $from) {
            return [$from];
        }

        $years = [];
        for ($y = $from; $y <= $current; $y++) {
            $years[] = $y;
        }

        return $years;
    }

    public function isAllowedYear(int $year): bool
    {
        return in_array($year, $this->taxYears(), true);
    }

    /**
     * @return array{
     *     year: int,
     *     income: list<array<string, mixed>>,
     *     expenses: list<array<string, mixed>>,
     *     income_cents: int,
     *     expense_cents: int,
     *     profit_cents: int
     * }
     */
    public function build(int $year): array
    {
        $income   = $this->incomeRows($year);
        $expenses = $this->expenseRows($year);

        $incomeCents = 0;
        foreach ($income as $row) {
            // EÜR: nur zugeflossene Beträge. Offene Rechnungen zählen nicht.
            if ((int) ($row['status'] ?? 0) === Wum::INVOICE_OPEN) {
                continue;
            }
            $incomeCents += (int) $row['amount_cents'];
        }
        $expenseCents = 0;
        foreach ($expenses as $row) {
            $expenseCents += (int) $row['amount_cents'];
        }

        return [
            'year'          => $year,
            'income'        => $income,
            'expenses'      => $expenses,
            'income_cents'  => $incomeCents,
            'expense_cents' => $expenseCents,
            'profit_cents'  => $incomeCents - $expenseCents,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function incomeRows(int $year): array
    {
        $wum    = config(Wum::class);
        $labels = $wum->invoiceStatusLabels;
        $from   = sprintf('%04d-01-01', $year);
        $to     = sprintf('%04d-12-31', $year);

        $rows = (new InvoiceModel())
            ->where('invoice_date >=', $from)
            ->where('invoice_date <=', $to)
            ->orderBy('invoice_date', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $customersByNumber = [];
        $customersByFolder = [];
        foreach ((new CustomerModel())->findAll() as $customer) {
            $customersByNumber[(string) $customer['customer_number']] = $customer;
            $folder = trim((string) ($customer['invoice_folder'] ?? ''));
            if ($folder !== '') {
                $customersByFolder[mb_strtolower($folder)] = $customer;
            }
        }

        $out = [];
        foreach ($rows as $row) {
            $status = (int) ($row['status'] ?? 0);
            $out[]  = [
                'id'             => (int) ($row['id'] ?? 0),
                'date'           => (string) ($row['invoice_date'] ?? ''),
                'number'         => (string) ($row['invoice_number'] ?? ''),
                'name'           => $this->customerDisplayName($row, $customersByNumber, $customersByFolder),
                'amount_cents'   => (int) ($row['amount_cents'] ?? 0),
                'status'         => $status,
                'status_label'   => $labels[$status] ?? (string) $status,
                'pdf_path'       => $this->invoicePdfPath($row, $customersByNumber, $customersByFolder),
            ];
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, array<string, mixed>> $customersByNumber
     * @param array<string, array<string, mixed>> $customersByFolder
     */
    private function customerDisplayName(array $row, array $customersByNumber, array $customersByFolder): string
    {
        $customer = $this->resolveCustomer($row, $customersByNumber, $customersByFolder);
        if ($customer !== null) {
            $company = trim((string) ($customer['company'] ?? ''));
            if ($company !== '') {
                return $company;
            }
            $person = trim(
                trim((string) ($customer['first_name'] ?? '')) . ' ' . trim((string) ($customer['last_name'] ?? ''))
            );
            if ($person !== '') {
                return $person;
            }
        }

        return (string) ($row['customer_last_name'] ?? '');
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, array<string, mixed>> $customersByNumber
     * @param array<string, array<string, mixed>> $customersByFolder
     * @return array<string, mixed>|null
     */
    private function resolveCustomer(array $row, array $customersByNumber, array $customersByFolder): ?array
    {
        $byNumber = $customersByNumber[(string) ($row['customer_number'] ?? '')] ?? null;
        if ($byNumber !== null && (string) ($row['customer_number'] ?? '') !== '0') {
            return $byNumber;
        }

        $filename = trim((string) ($row['filename'] ?? ''));
        if ($filename !== '' && preg_match('/^\d{6,12}\s*(.+)\.pdf$/i', $filename, $m)) {
            $folderKey = mb_strtolower(trim($m[1]));
            if (isset($customersByFolder[$folderKey])) {
                return $customersByFolder[$folderKey];
            }
        }

        $hints = [];
        $lastHint = mb_strtolower(trim((string) ($row['customer_last_name'] ?? '')));
        if ($lastHint !== '') {
            $hints[] = $lastHint;
        }
        if ($filename !== '' && preg_match('/^\d{6,12}\s*(.+)\.pdf$/i', $filename, $m)) {
            $hints[] = mb_strtolower(trim($m[1]));
        }

        foreach ($hints as $hint) {
            if (isset($customersByFolder[$hint])) {
                return $customersByFolder[$hint];
            }
        }

        $numbers = new DocumentNumberService();
        foreach ($customersByNumber as $customer) {
            $candidates = [
                mb_strtolower(trim((string) ($customer['invoice_folder'] ?? ''))),
                mb_strtolower($numbers->sanitizeFilenamePart((string) ($customer['last_name'] ?? ''))),
                mb_strtolower($numbers->sanitizeFilenamePart(str_replace(' ', '_', (string) ($customer['company'] ?? '')))),
                mb_strtolower($numbers->customerSuffix($customer)),
            ];
            foreach ($hints as $hint) {
                if ($hint !== '' && in_array($hint, $candidates, true)) {
                    return $customer;
                }
            }
        }

        return $byNumber;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, array<string, mixed>> $customersByNumber
     * @param array<string, array<string, mixed>> $customersByFolder
     */
    private function invoicePdfPath(array $row, array $customersByNumber, array $customersByFolder): ?string
    {
        helper('wum');

        $filename = trim((string) ($row['filename'] ?? ''));
        $customer = $this->resolveCustomer($row, $customersByNumber, $customersByFolder);
        $folder   = trim((string) ($customer['invoice_folder'] ?? ''));

        if ($folder === '' && $filename !== '' && preg_match('/^\d{6,12}\s*(.+)\.pdf$/i', $filename, $m)) {
            $folder = trim($m[1]);
        }

        if ($filename !== '' && $folder !== '') {
            $absolute = wum_invoice_dir($folder) . $filename;
            if (is_file($absolute)) {
                return wum_invoice_web_path($folder, $filename);
            }
        }

        $found = $this->findInvoicePdfOnDisk((string) ($row['invoice_number'] ?? ''), $folder);
        if ($found === null) {
            if ($filename !== '' && $folder !== '') {
                return wum_invoice_web_path($folder, $filename);
            }

            return wum_is_demo() && $filename !== '' ? wum_invoice_web_path(null, $filename) : null;
        }

        return wum_invoice_web_path($found['folder'], $found['filename']);
    }

    /**
     * @return array{folder: string, filename: string}|null
     */
    private function findInvoicePdfOnDisk(string $invoiceNumber, string $preferredFolder = ''): ?array
    {
        helper('wum');
        $invoiceNumber = trim($invoiceNumber);
        if ($invoiceNumber === '' || ! preg_match('/^\d{6,12}$/', $invoiceNumber)) {
            return null;
        }

        $root = wum_shared_invoices_root();
        if (! is_dir($root)) {
            return null;
        }

        $folders = [];
        if ($preferredFolder !== '') {
            $folders[] = $preferredFolder;
        }
        foreach (glob($root . '*', GLOB_ONLYDIR) ?: [] as $dir) {
            $name = basename($dir);
            if ($name === '' || $name === '.' || $name === '..' || strcasecmp($name, 'wum.hamburg') === 0) {
                continue;
            }
            if (! in_array($name, $folders, true)) {
                $folders[] = $name;
            }
        }

        $numberQuoted = preg_quote($invoiceNumber, '/');
        foreach ($folders as $folder) {
            $dir = $root . $folder . DIRECTORY_SEPARATOR;
            foreach (glob($dir . $invoiceNumber . '*.pdf') ?: [] as $path) {
                if (! is_file($path)) {
                    continue;
                }
                $basename = basename($path);
                if (strncasecmp($basename, 'Angebot', 7) === 0) {
                    continue;
                }
                // Exact number prefix: "202601017 …" or "202601017folder.pdf", not "2026010170…".
                if (! preg_match('/^' . $numberQuoted . '(?:\s|[^0-9]|$)/', $basename)) {
                    continue;
                }

                return [
                    'folder'   => $folder,
                    'filename' => $basename,
                ];
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function expenseRows(int $year): array
    {
        $rows = (new IncomingInvoiceModel())->getByBookingYear($year);
        $out  = [];
        foreach ($rows as $row) {
            $out[] = [
                'date'         => (string) ($row['invoice_date'] ?? ''),
                'number'       => (string) ($row['invoice_number'] ?? ''),
                'name'         => (string) ($row['company_name'] ?? ''),
                'creditor_no'  => (string) ($row['creditor_number'] ?? ''),
                'amount_cents' => (int) ($row['amount_cents'] ?? 0),
            ];
        }

        usort($out, static function (array $a, array $b): int {
            $cmp = strcmp($a['date'], $b['date']);
            return $cmp !== 0 ? $cmp : strcmp($a['number'], $b['number']);
        });

        return $out;
    }
}
