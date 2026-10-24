<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Wum;

/**
 * Online schema patches that cannot rely on CLI `spark migrate`.
 */
class SchemaUpdateService
{
    public const PATCH_CREDITOR_NUMBER_VARCHAR20 = 'creditor_number_varchar20';
    public const PATCH_QUOTE_CONFIRMATIONS       = 'quote_confirmations';
    public const PATCH_SYNC_INVOICES_FROM_PDFS   = 'sync_invoices_from_pdfs';
    public const PATCH_SYNC_INVOICES_FROM_PDFS_V2 = 'sync_invoices_from_pdfs_v2';
    public const PATCH_SYNC_INVOICES_FROM_PDFS_V3 = 'sync_invoices_from_pdfs_v3';
    public const PATCH_SYNC_INVOICES_FROM_PDFS_V4 = 'sync_invoices_from_pdfs_v4';

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public function pending(): array
    {
        $pending = [];
        if (! $this->db->tableExists('quote_confirmations')) {
            $pending[] = [
                'id'    => self::PATCH_QUOTE_CONFIRMATIONS,
                'label' => 'Auftragsbestätigungen: Tabelle quote_confirmations anlegen',
            ];
        }
        if ($this->creditorNumberNeedsVarchar20()) {
            $pending[] = [
                'id'    => self::PATCH_CREDITOR_NUMBER_VARCHAR20,
                'label' => 'Kreditoren-Kundennummer: INTEGER/TEXT → VARCHAR(20)',
            ];
        }
        if (! $this->isPatchApplied(self::PATCH_SYNC_INVOICES_FROM_PDFS_V4)) {
            $pending[] = [
                'id'    => self::PATCH_SYNC_INVOICES_FROM_PDFS_V4,
                'label' => 'Ausgangsrechnungen: PDF-Abgleich (Beträge, Kundenzuordnung; Ordner wum.hamburg wird übersprungen)',
            ];
        }

        return $pending;
    }

    public function hasPending(): bool
    {
        return $this->pending() !== [];
    }

    /**
     * @return array{ok: bool, message: string, applied: list<string>}
     */
    public function apply(): array
    {
        $applied = [];
        $errors  = [];

        foreach ($this->pending() as $patch) {
            try {
                if ($patch['id'] === self::PATCH_QUOTE_CONFIRMATIONS) {
                    $this->ensureQuoteConfirmationsTable();
                    $applied[] = $patch['label'];
                } elseif ($patch['id'] === self::PATCH_CREDITOR_NUMBER_VARCHAR20) {
                    $this->applyCreditorNumberVarchar20();
                    $applied[] = $patch['label'];
                } elseif (
                    $patch['id'] === self::PATCH_SYNC_INVOICES_FROM_PDFS
                    || $patch['id'] === self::PATCH_SYNC_INVOICES_FROM_PDFS_V2
                    || $patch['id'] === self::PATCH_SYNC_INVOICES_FROM_PDFS_V3
                    || $patch['id'] === self::PATCH_SYNC_INVOICES_FROM_PDFS_V4
                ) {
                    $result    = $this->applyInvoicePdfSync($patch['id']);
                    $applied[] = $patch['label'] . ' (' . $result['summary'] . ')';
                }
            } catch (\Throwable $e) {
                $errors[] = $patch['label'] . ': ' . $e->getMessage();
            }
        }

        if ($errors !== []) {
            return [
                'ok'      => false,
                'message' => 'Update unvollständig: ' . implode(' ', $errors),
                'applied' => $applied,
            ];
        }

        if ($applied === []) {
            return [
                'ok'      => true,
                'message' => 'Kein Datenbank-Update erforderlich.',
                'applied' => [],
            ];
        }

        return [
            'ok'      => true,
            'message' => 'Datenbank aktualisiert: ' . implode('; ', $applied),
            'applied' => $applied,
        ];
    }

    public function ensureQuoteConfirmationsTable(): void
    {
        if ($this->db->tableExists('quote_confirmations')) {
            return;
        }

        $this->db->query(
            'CREATE TABLE quote_confirmations (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                quote_id INT UNSIGNED NOT NULL,
                token VARCHAR(64) NOT NULL,
                notes TEXT NULL,
                confirmation_date DATE NULL,
                signature_data MEDIUMTEXT NULL,
                email_copy TINYINT(1) NOT NULL DEFAULT 0,
                privacy_consented_at DATETIME NULL,
                pdf_filename VARCHAR(100) NULL,
                submitted_at DATETIME NULL,
                declined_at DATETIME NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                PRIMARY KEY (id),
                UNIQUE KEY quote_id (quote_id),
                UNIQUE KEY token (token)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    /**
     * Create creditors / incoming_invoices if missing and normalize creditor_number.
     */
    public function ensureCreditorsAndIncomingInvoices(): void
    {
        if (! $this->db->tableExists('creditors')) {
            $this->db->query(
                'CREATE TABLE creditors (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    creditor_number VARCHAR(20) NOT NULL,
                    company_name VARCHAR(255) NOT NULL,
                    status TINYINT(1) NOT NULL DEFAULT 1,
                    note VARCHAR(255) NULL,
                    meta VARCHAR(255) NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY creditor_number (creditor_number),
                    UNIQUE KEY company_name_unique (company_name)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );
        } elseif (! $this->db->fieldExists('creditor_number', 'creditors')) {
            $this->db->query(
                'ALTER TABLE creditors ADD creditor_number VARCHAR(20) NULL AFTER id'
            );
            $this->db->query(
                'UPDATE creditors SET creditor_number = CONCAT(\'K-\', LPAD(id, 5, \'0\')) WHERE creditor_number IS NULL OR creditor_number = \'\''
            );
            $this->db->query('ALTER TABLE creditors MODIFY creditor_number VARCHAR(20) NOT NULL');
            $this->db->query('ALTER TABLE creditors ADD UNIQUE KEY creditor_number (creditor_number)');
        } elseif ($this->creditorNumberNeedsVarchar20()) {
            $this->applyCreditorNumberVarchar20();
        }

        if (! $this->db->tableExists('incoming_invoices')) {
            $this->db->query(
                'CREATE TABLE incoming_invoices (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    creditor_id INT UNSIGNED NOT NULL,
                    booking_year SMALLINT UNSIGNED NOT NULL,
                    invoice_number VARCHAR(100) NOT NULL,
                    invoice_date DATE NOT NULL,
                    amount_cents INT NOT NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    PRIMARY KEY (id),
                    KEY creditor_id (creditor_id),
                    KEY booking_year (booking_year),
                    UNIQUE KEY creditor_invoice_year (creditor_id, invoice_number, booking_year)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );
        }
    }

    /**
     * Seed fantasy creditors + 2026 incoming invoices when demo tables are empty.
     * Safe for online demo without Update button / waiting for cron reset.
     *
     * @return array{seeded: bool, creditors: int, incoming: int}
     */
    public function ensureDemoFantasyAccountingData(): array
    {
        $result = ['seeded' => false, 'creditors' => 0, 'incoming' => 0];

        if (! $this->db->tableExists('creditors') || ! $this->db->tableExists('incoming_invoices')) {
            return $result;
        }

        $creditorCount = (int) $this->db->table('creditors')->countAllResults();
        $incoming2026  = (int) $this->db->table('incoming_invoices')
            ->where('booking_year', 2026)
            ->countAllResults();

        if ($creditorCount > 0 && $incoming2026 > 0) {
            $result['creditors'] = $creditorCount;
            $result['incoming']  = $incoming2026;

            return $result;
        }

        $now = date('Y-m-d H:i:s');
        $creditorIds = [];

        if ($creditorCount === 0) {
            $creditors = [
                ['K-10001', 'Papierwerk Nord GmbH', 'Büromaterial'],
                ['K-10002', 'CloudHost AG', 'Hosting'],
                ['K-10003', 'Druckerei Elbe', 'Druck'],
                ['K-10004', 'BüroExpress KG', 'Verbrauchsmaterial'],
                ['K-10005', 'Technik Partner OHG', 'IT-Wartung'],
            ];

            foreach ($creditors as $creditor) {
                $this->db->table('creditors')->insert([
                    'creditor_number' => $creditor[0],
                    'company_name'    => $creditor[1],
                    'status'          => Wum::STATUS_ACTIVE,
                    'note'            => $creditor[2],
                    'meta'            => null,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
                $creditorIds[] = (int) $this->db->insertID();
            }
        } else {
            $creditorIds = array_map(
                static fn (array $row): int => (int) $row['id'],
                $this->db->table('creditors')->select('id')->orderBy('id', 'ASC')->get()->getResultArray()
            );
        }

        if ($incoming2026 === 0 && $creditorIds !== []) {
            $amountCents = [
                12900, 24900, 8900, 45900, 15900,
                32900, 7800, 19900, 54900, 11200,
                27500, 9800, 41500, 16700, 22300,
                61000, 13400, 8700, 29800, 18500,
            ];

            for ($i = 1; $i <= 20; $i++) {
                $creditorId = $creditorIds[($i - 1) % count($creditorIds)];
                $month      = (($i - 1) % 12) + 1;
                $day        = min(28, 1 + (($i * 3) % 27));

                $this->db->table('incoming_invoices')->insert([
                    'creditor_id'    => $creditorId,
                    'booking_year'   => 2026,
                    'invoice_number' => sprintf('ER-2026-%03d', $i),
                    'invoice_date'   => sprintf('2026-%02d-%02d', $month, $day),
                    'amount_cents'   => $amountCents[$i - 1],
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }
        }

        $result['seeded']    = true;
        $result['creditors'] = (int) $this->db->table('creditors')->countAllResults();
        $result['incoming']  = (int) $this->db->table('incoming_invoices')
            ->where('booking_year', 2026)
            ->countAllResults();

        return $result;
    }

    public function creditorNumberNeedsVarchar20(): bool
    {
        if (! $this->db->tableExists('creditors') || ! $this->db->fieldExists('creditor_number', 'creditors')) {
            return false;
        }

        $row = $this->db->query("SHOW COLUMNS FROM creditors LIKE 'creditor_number'")->getRowArray();
        if ($row === null) {
            return false;
        }

        $type = strtolower((string) ($row['Type'] ?? ''));

        return ! preg_match('/^varchar\(20\)/', $type);
    }

    public function applyCreditorNumberVarchar20(): void
    {
        if (! $this->db->tableExists('creditors') || ! $this->db->fieldExists('creditor_number', 'creditors')) {
            return;
        }

        $this->db->query('ALTER TABLE creditors MODIFY creditor_number VARCHAR(20) NOT NULL');
    }

    /**
     * @return array{summary: string}
     */
    public function applyInvoicePdfSync(?string $patchId = null): array
    {
        $result = (new InvoicePdfSyncService())->sync();
        $extra  = '';
        if ($result['errors'] !== []) {
            $preview = array_slice($result['errors'], 0, 5);
            $extra   = ' Hinweise: ' . implode(' ', $preview);
            if (count($result['errors']) > 5) {
                $extra .= ' …';
            }
        }
        $this->markPatchApplied($patchId ?? self::PATCH_SYNC_INVOICES_FROM_PDFS_V4, $result['summary'] . $extra);

        return ['summary' => $result['summary'] . $extra];
    }

    public function isPatchApplied(string $id): bool
    {
        $this->ensurePatchesTable();
        if (! $this->db->tableExists('schema_patches')) {
            return false;
        }

        $row = $this->db->table('schema_patches')->where('id', $id)->get()->getRowArray();

        return $row !== null;
    }

    public function markPatchApplied(string $id, string $message = ''): void
    {
        $this->ensurePatchesTable();
        $this->db->table('schema_patches')->replace([
            'id'         => $id,
            'applied_at' => date('Y-m-d H:i:s'),
            'message'    => $message !== '' ? $message : null,
        ]);
    }

    private function ensurePatchesTable(): void
    {
        if ($this->db->tableExists('schema_patches')) {
            return;
        }

        $this->db->query(
            'CREATE TABLE schema_patches (
                id VARCHAR(64) NOT NULL,
                applied_at DATETIME NOT NULL,
                message TEXT NULL,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }
}
