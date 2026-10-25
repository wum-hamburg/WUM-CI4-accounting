<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use ZipArchive;

/**
 * Restore from backup ZIP / Wiederherstellung
 */
class RestoreService
{
    private string $backupDir;
    private BaseConnection $db;

    public function __construct()
    {
        $this->backupDir = WRITEPATH . 'backups' . DIRECTORY_SEPARATOR;
        $this->db        = Database::connect();
    }

    public function getRestorableTables(): array
    {
        return [
            'all'               => 'Alle Tabellen',
            'master'            => 'Nur Stammdaten (customers, creditors, articles, text_blocks)',
            'documents'         => 'Nur Rechnungen & Angebote',
            'incoming'          => 'Nur Eingangsrechnungen',
            'customers'         => 'customers',
            'creditors'         => 'creditors',
            'articles'          => 'articles',
            'text_blocks'       => 'text_blocks',
            'invoices'          => 'invoices',
            'quotes'              => 'quotes',
            'quote_confirmations' => 'Auftragsbestätigungen',
            'incoming_invoices' => 'incoming_invoices',
            'user'              => 'user',
        ];
    }

    public function resolveTables(string $scope): array
    {
        return match ($scope) {
            'all'       => ['user', 'customers', 'creditors', 'articles', 'text_blocks', 'invoices', 'quotes', 'quote_confirmations', 'incoming_invoices'],
            'master'    => ['customers', 'creditors', 'articles', 'text_blocks'],
            'documents' => ['invoices', 'quotes', 'quote_confirmations'],
            'incoming'  => ['incoming_invoices'],
            default     => in_array($scope, ['customers', 'creditors', 'articles', 'text_blocks', 'invoices', 'quotes', 'quote_confirmations', 'incoming_invoices', 'user'], true)
                ? [$scope]
                : [],
        };
    }

    /**
     * @return array{ok:bool,message:string,restored?:int}
     */
    public function restore(string $zipFilename, string $scope, string $conflict = 'truncate', bool $includePdfs = false): array
    {
        $zipPath = $this->backupDir . basename($zipFilename);
        if (!is_file($zipPath)) {
            return ['ok' => false, 'message' => 'Backup-Datei nicht gefunden.'];
        }

        $tables = $this->resolveTables($scope);
        if ($tables === []) {
            return ['ok' => false, 'message' => 'Ungültiger Wiederherstellungs-Umfang.'];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['ok' => false, 'message' => 'ZIP konnte nicht geöffnet werden.'];
        }

        $sqlContent = $zip->getFromName('database.sql');
        if ($sqlContent === false) {
            $zip->close();
            return ['ok' => false, 'message' => 'database.sql fehlt im Backup.'];
        }

        $tempSql = WRITEPATH . 'backups' . DIRECTORY_SEPARATOR . 'restore_' . time() . '.sql';
        file_put_contents($tempSql, $sqlContent);

        $this->db->transStart();
        try {
            $restored = $this->importSqlForTables($tempSql, $tables, $conflict);
            if ($includePdfs) {
                $this->extractPdfs($zip);
            }
            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                return ['ok' => false, 'message' => 'Datenbank-Transaktion fehlgeschlagen.'];
            }
        } catch (\Throwable $e) {
            $this->db->transRollback();
            $zip->close();
            @unlink($tempSql);
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        $zip->close();
        @unlink($tempSql);
        return ['ok' => true, 'message' => 'Wiederherstellung abgeschlossen.', 'restored' => $restored];
    }

    private function importSqlForTables(string $sqlFile, array $tables, string $conflict): int
    {
        $sql     = file_get_contents($sqlFile);
        $count   = 0;
        $allowed = array_flip($tables);

        if ($conflict === 'truncate') {
            foreach ($tables as $table) {
                if ($this->db->tableExists($table)) {
                    $this->db->query('SET FOREIGN_KEY_CHECKS=0');
                    $this->db->table($table)->truncate();
                    $this->db->query('SET FOREIGN_KEY_CHECKS=1');
                }
            }
        }

        $statements = $this->splitSqlStatements($sql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '' || str_starts_with($statement, '--')) {
                continue;
            }
            if (preg_match('/^INSERT\s+INTO\s+`?([a-zA-Z0-9_]+)`?/i', $statement, $m)) {
                $table = $m[1];
                if (!isset($allowed[$table])) {
                    continue;
                }
                if ($conflict === 'skip_existing') {
                    continue;
                }
                $this->db->query($statement);
                $count++;
            } elseif (preg_match('/^CREATE\s+TABLE/i', $statement)) {
                continue;
            } elseif (preg_match('/^DROP\s+TABLE/i', $statement)) {
                continue;
            }
        }
        return $count;
    }

    private function splitSqlStatements(string $sql): array
    {
        return preg_split('/;\s*\n/', $sql) ?: [];
    }

    private function extractPdfs(ZipArchive $zip): void
    {
        helper('wum');

        $archivesDir = WRITEPATH . 'archives' . DIRECTORY_SEPARATOR;
        if (!is_dir($archivesDir)) {
            mkdir($archivesDir, 0755, true);
        }

        $archivesRoot = realpath($archivesDir);
        if ($archivesRoot === false) {
            throw new \RuntimeException('Archiv-Verzeichnis nicht verfügbar.');
        }
        $archivesRoot = rtrim($archivesRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $sharedRoot = wum_shared_invoices_root();
        if (! is_dir($sharedRoot)) {
            mkdir($sharedRoot, 0755, true);
        }
        $sharedReal = realpath($sharedRoot);
        if ($sharedReal === false) {
            throw new \RuntimeException('Gemeinsames Rechnungsverzeichnis nicht verfügbar.');
        }
        $sharedReal = rtrim($sharedReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!is_string($name)) {
                continue;
            }

            $name = str_replace('\\', '/', $name);
            if (str_contains($name, '..')) {
                continue;
            }

            // Shared invoices/{folder}/{file}.pdf
            if (str_starts_with($name, 'invoices/')) {
                $relative = substr($name, strlen('invoices/'));
                if ($relative === '' || substr_count($relative, '/') !== 1) {
                    continue;
                }
                [$folder, $file] = explode('/', $relative, 2);
                $folder = basename($folder);
                $file   = basename($file);
                if ($folder === '' || $file === '' || ! str_ends_with(strtolower($file), '.pdf')) {
                    continue;
                }

                $targetDir = $sharedReal . $folder . DIRECTORY_SEPARATOR;
                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                $resolvedDir = realpath($targetDir);
                if ($resolvedDir === false || ! str_starts_with($resolvedDir . DIRECTORY_SEPARATOR, $sharedReal)) {
                    continue;
                }
                file_put_contents($resolvedDir . DIRECTORY_SEPARATOR . $file, $zip->getFromIndex($i));
                continue;
            }

            // Quotes (and legacy archives/invoices flat entries → ignored for invoices root)
            if (! str_starts_with($name, 'archives/')) {
                continue;
            }

            $relative = substr($name, strlen('archives/'));
            if ($relative === '' || str_starts_with($relative, 'invoices/')) {
                // Legacy flat invoice backups are skipped; use shared invoices/ tree.
                continue;
            }

            $target = $archivesRoot . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $targetDir = dirname($target);
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $resolvedDir = realpath($targetDir);
            if ($resolvedDir === false || !str_starts_with($resolvedDir . DIRECTORY_SEPARATOR, $archivesRoot)) {
                continue;
            }

            file_put_contents($target, $zip->getFromIndex($i));
        }
    }
}
