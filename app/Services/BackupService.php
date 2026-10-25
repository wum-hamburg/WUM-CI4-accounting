<?php

namespace App\Services;

use ZipArchive;

/**
 * Database backup jobs / Datensicherung
 */
class BackupService
{
    private string $backupDir;
    private string $jobsDir;

    public function __construct()
    {
        $this->backupDir = WRITEPATH . 'backups' . DIRECTORY_SEPARATOR;
        $this->jobsDir   = $this->backupDir . 'jobs' . DIRECTORY_SEPARATOR;
        foreach ([$this->backupDir, $this->jobsDir] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    public function listBackups(): array
    {
        $files = glob($this->backupDir . 'backup_*.zip') ?: [];
        usort($files, static fn ($a, $b) => filemtime($b) <=> filemtime($a));
        $out = [];
        foreach ($files as $path) {
            $out[] = [
                'filename' => basename($path),
                'path'     => $path,
                'size'     => filesize($path),
                'mtime'    => filemtime($path),
            ];
        }
        return $out;
    }

    public function getBackupDir(): string
    {
        return $this->backupDir;
    }

    /**
     * @param array{includePdfs?: bool, uploadHetzner?: bool} $options
     */
    public function createJob(string $label = 'Komplette Datenbank', array $options = []): array
    {
        $jobId = date('YmdHis') . '_' . bin2hex(random_bytes(4));
        $job   = [
            'job_id'         => $jobId,
            'state'          => 'queued',
            'progress'       => 5,
            'message'        => 'Sicherung gestartet',
            'created_at'     => date('c'),
            'label'          => $label,
            'include_pdfs'   => (bool) ($options['includePdfs'] ?? true),
            'upload_hetzner' => (bool) ($options['uploadHetzner'] ?? false),
            'done'           => false,
            'error'          => false,
            'artifacts'      => [],
        ];
        if (!$this->saveJob($jobId, $job)) {
            return ['ok' => false, 'message' => 'Job-Datei konnte nicht erstellt werden.'];
        }
        return ['ok' => true, 'job_id' => $jobId];
    }

    /**
     * Run worker steps until the job finishes (CLI / sync restore snapshot).
     */
    public function runToCompletion(string $jobId, int $maxSteps = 50): array
    {
        $last = ['ok' => false, 'done' => true, 'message' => 'Job nicht gestartet.'];
        for ($i = 0; $i < $maxSteps; $i++) {
            $last = $this->workerStep($jobId);
            if (! empty($last['done'])) {
                return $last;
            }
        }
        return [
            'ok'       => false,
            'done'     => true,
            'progress' => (int) ($last['progress'] ?? 0),
            'message'  => 'Backup-Job abgebrochen (Timeout).',
            'error'    => 'Timeout',
        ];
    }

    public function workerStep(string $jobId): array
    {
        $job = $this->loadJob($jobId);
        if ($job === null) {
            return ['ok' => false, 'done' => true, 'message' => 'Job nicht gefunden.'];
        }
        if (!empty($job['done'])) {
            return ['ok' => true, 'done' => true, 'progress' => 100, 'message' => $job['message'] ?? 'Fertig'];
        }

        $state = $job['state'] ?? 'queued';
        if ($state === 'queued') {
            $job['state']    = 'dump_sql';
            $job['progress'] = 25;
            $job['message']  = 'SQL-Dump wird erstellt …';
            $this->saveJob($jobId, $job);
            return $this->workerResponse($job);
        }

        if ($state === 'dump_sql') {
            $sqlFile = $this->jobsDir . $jobId . '.sql';
            $dump    = $this->runMysqldump($sqlFile);
            if (!$dump['ok']) {
                $job['done']           = true;
                $job['error']          = true;
                $job['error_message']  = $dump['message'];
                $job['message']        = 'Fehler beim SQL-Dump';
                $this->saveJob($jobId, $job);
                return $this->workerResponse($job);
            }
            $job['artifacts']['sql'] = $sqlFile;
            $job['state']            = 'build_zip';
            $job['progress']         = 50;
            $job['message']          = 'ZIP wird erstellt …';
            $this->saveJob($jobId, $job);
            return $this->workerResponse($job);
        }

        if ($state === 'build_zip') {
            $prefix  = ! empty($job['include_pdfs']) ? 'backup_' : 'backup_db_';
            $zipName = $prefix . date('Y-m-d_H-i-s') . '.zip';
            $zipPath = $this->backupDir . $zipName;
            $zip     = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                $job['done']          = true;
                $job['error']         = true;
                $job['error_message'] = 'ZIP konnte nicht erstellt werden.';
                $this->saveJob($jobId, $job);
                return $this->workerResponse($job);
            }
            if (!empty($job['artifacts']['sql']) && is_file($job['artifacts']['sql'])) {
                $zip->addFile($job['artifacts']['sql'], 'database.sql');
            }
            if (! empty($job['include_pdfs'])) {
                helper('wum');
                $this->addSharedInvoicesTree($zip);
                $this->addArchiveDir($zip, WRITEPATH . 'archives/quotes', 'archives/quotes');
                $this->addArchiveDir($zip, WRITEPATH . 'archives/quote-confirmations', 'archives/quote-confirmations');
                $this->addArchiveDir($zip, WRITEPATH . 'demo/archives/quote-confirmations', 'demo/archives/quote-confirmations');
            }
            $zip->close();

            $job['artifacts']['zip'] = $zipPath;
            $job['artifacts']['zip_name'] = $zipName;

            if (! empty($job['upload_hetzner'])) {
                $job['state']    = 'upload_hetzner';
                $job['progress'] = 80;
                $job['message']  = 'Upload zu Hetzner …';
                $this->saveJob($jobId, $job);
                return $this->workerResponse($job);
            }

            $job['state']    = 'done';
            $job['progress'] = 100;
            $job['done']     = true;
            $job['message']  = 'Sicherung abgeschlossen: ' . $zipName;
            $this->saveJob($jobId, $job);
            return $this->workerResponse($job);
        }

        if ($state === 'upload_hetzner') {
            $zipPath = (string) ($job['artifacts']['zip'] ?? '');
            $zipName = (string) ($job['artifacts']['zip_name'] ?? basename($zipPath));
            $uploader = new HetznerUploadService();
            $up = $uploader->upload($zipPath, $zipName);
            if (! $up['ok']) {
                $job['done']          = true;
                $job['error']         = true;
                $job['error_message'] = $up['message'] !== '' ? $up['message'] : 'Hetzner-Upload fehlgeschlagen.';
                $job['message']       = 'Fehler beim Hetzner-Upload';
                $this->saveJob($jobId, $job);
                return $this->workerResponse($job);
            }
            $job['state']    = 'done';
            $job['progress'] = 100;
            $job['done']     = true;
            $job['message']  = 'Sicherung online: ' . $zipName;
            $this->saveJob($jobId, $job);
            return $this->workerResponse($job);
        }

        $job['done']    = true;
        $job['progress'] = 100;
        $this->saveJob($jobId, $job);
        return $this->workerResponse($job);
    }

    public function createPreRestoreSnapshot(): array
    {
        return $this->createJob('Pre-Restore Snapshot ' . date('Y-m-d H:i:s'), [
            'includePdfs'   => true,
            'uploadHetzner' => false,
        ]);
    }

    private function workerResponse(array $job): array
    {
        return [
            'ok'       => empty($job['error']),
            'done'     => !empty($job['done']),
            'progress' => (int) ($job['progress'] ?? 0),
            'message'  => (string) ($job['message'] ?? ''),
            'error'    => (string) ($job['error_message'] ?? ''),
        ];
    }

    private function addArchiveDir(ZipArchive $zip, string $dir, string $zipPrefix): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            if (is_file($file)) {
                $zip->addFile($file, $zipPrefix . '/' . basename($file));
            }
        }
    }

    /**
     * Add shared invoices/{folder}/*.pdf into the ZIP under invoices/{folder}/.
     */
    private function addSharedInvoicesTree(ZipArchive $zip): void
    {
        $root = wum_shared_invoices_root();
        if (! is_dir($root)) {
            return;
        }

        foreach (glob($root . '*', GLOB_ONLYDIR) ?: [] as $folderPath) {
            $folder = basename($folderPath);
            if ($folder === '' || $folder === '.' || $folder === '..') {
                continue;
            }
            foreach (glob($folderPath . DIRECTORY_SEPARATOR . '*.pdf') ?: [] as $file) {
                if (is_file($file)) {
                    $zip->addFile($file, 'invoices/' . $folder . '/' . basename($file));
                }
            }
        }
    }

    private function runMysqldump(string $outpath): array
    {
        $db = config('Database')->default;
        if (!function_exists('proc_open')) {
            return $this->phpDump($outpath, $db);
        }
        $binary = (stripos(PHP_OS, 'WIN') === 0) ? 'mysqldump.exe' : 'mysqldump';
        $cmd    = [
            $binary,
            '--default-character-set=utf8mb4',
            '-h' . $db['hostname'],
            '-u' . $db['username'],
            '-p' . $db['password'],
            $db['database'],
        ];
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc        = proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($proc)) {
            return $this->phpDump($outpath, $db);
        }
        $sql = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);
        if ($code !== 0 || $sql === false || $sql === '') {
            return $this->phpDump($outpath, $db);
        }
        file_put_contents($outpath, $sql);
        return ['ok' => true, 'message' => ''];
    }

    private function phpDump(string $outpath, array $db): array
    {
        try {
            $conn = new \mysqli($db['hostname'], $db['username'], $db['password'], $db['database'], (int) $db['port']);
            if ($conn->connect_error) {
                return ['ok' => false, 'message' => $conn->connect_error];
            }
            $tables = [];
            $res    = $conn->query('SHOW TABLES');
            while ($row = $res->fetch_row()) {
                $tables[] = $row[0];
            }
            $sql = "-- WUM CI4 PHP backup\nSET NAMES utf8mb4;\n";
            foreach ($tables as $table) {
                $sql .= "\nDROP TABLE IF EXISTS `{$table}`;\n";
                $create = $conn->query("SHOW CREATE TABLE `{$table}`")->fetch_row();
                $sql .= $create[1] . ";\n";
                $rows = $conn->query("SELECT * FROM `{$table}`");
                while ($r = $rows->fetch_assoc()) {
                    $vals = array_map(static fn ($v) => $v === null ? 'NULL' : "'" . $conn->real_escape_string((string) $v) . "'", array_values($r));
                    $sql .= 'INSERT INTO `' . $table . '` VALUES (' . implode(',', $vals) . ");\n";
                }
            }
            file_put_contents($outpath, $sql);
            $conn->close();
            return ['ok' => true, 'message' => ''];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function jobPath(string $jobId): string
    {
        return $this->jobsDir . $jobId . '.json';
    }

    private function loadJob(string $jobId): ?array
    {
        if (!preg_match('/^[A-Za-z0-9_-]{8,80}$/', $jobId)) {
            return null;
        }
        $path = $this->jobPath($jobId);
        if (!is_file($path)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($path), true);
        return is_array($data) ? $data : null;
    }

    private function saveJob(string $jobId, array $job): bool
    {
        $path = $this->jobPath($jobId);
        $tmp  = $path . '.tmp';
        $json = json_encode($job, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false) {
            return false;
        }
        if (file_put_contents($tmp, $json, LOCK_EX) === false) {
            return false;
        }
        return rename($tmp, $path);
    }
}
