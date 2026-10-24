<?php

namespace App\Controllers;

use App\Services\BackupService;
use App\Services\RestoreService;

class Backup extends BaseController
{
    private const MAX_UPLOAD_BYTES = 536870912; // 512 MB

    public function index()
    {
        $backup = new BackupService();
        return view('Backup/index', $this->viewData([
            'moduleTitle' => 'Datensicherung',
            'backups'     => $backup->listBackups(),
            'jobId'       => $this->request->getGet('job'),
        ]));
    }

    public function start()
    {
        $backup = new BackupService();
        $result = $backup->createJob('Online-Datensicherung', [
            'includePdfs'   => true,
            'uploadHetzner' => true,
        ]);
        if (empty($result['ok'])) {
            return redirect()->to('/backup')->with('error', $result['message'] ?? 'Job konnte nicht gestartet werden.');
        }
        return redirect()->to('/backup?job=' . urlencode($result['job_id']));
    }

    public function worker($jobId)
    {
        $backup = new BackupService();
        $result = $backup->workerStep($jobId);
        return $this->response->setJSON($result);
    }

    public function restore()
    {
        $backup  = new BackupService();
        $restore = new RestoreService();
        return view('Backup/restore', $this->viewData([
            'moduleTitle' => 'Wiederherstellung',
            'backups'     => $backup->listBackups(),
            'scopes'      => $restore->getRestorableTables(),
        ]));
    }

    public function runRestore()
    {
        $scope      = $this->request->getPost('scope') ?? 'all';
        $conflict   = $this->request->getPost('conflict') ?? 'truncate';
        $includePdf = $this->request->getPost('include_pdfs') !== null
            && $this->request->getPost('include_pdfs') !== '0';

        $backupFile = $this->resolveBackupFile();
        if ($backupFile === null) {
            return redirect()->to('/restore')->with('error', 'Bitte ein lokales Backup wählen oder eine ZIP-Datei hochladen.');
        }

        $restore = new RestoreService();
        $result  = $restore->restore($backupFile, (string) $scope, (string) $conflict, $includePdf);

        return redirect()->to('/restore')->with(
            $result['ok'] ? 'success' : 'error',
            $result['message']
        );
    }

    /**
     * Prefer uploaded ZIP; otherwise use selected local backup filename.
     */
    private function resolveBackupFile(): ?string
    {
        $file = $this->request->getFile('backup_upload');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            if (strtolower($file->getExtension()) !== 'zip') {
                return null;
            }
            if ($file->getSize() > self::MAX_UPLOAD_BYTES) {
                return null;
            }

            $safeBase = preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientName()) ?: 'upload.zip';
            if (! str_ends_with(strtolower($safeBase), '.zip')) {
                $safeBase .= '.zip';
            }
            if (! str_starts_with($safeBase, 'backup_')) {
                $safeBase = 'backup_upload_' . date('Y-m-d_H-i-s') . '_' . $safeBase;
            }

            $backup = new BackupService();
            if (! $file->move($backup->getBackupDir(), $safeBase, true)) {
                return null;
            }

            return $safeBase;
        }

        $selected = (string) $this->request->getPost('backup_file');
        $selected = basename($selected);
        if ($selected === '' || ! preg_match('/^backup_[\w.-]+\.zip$/i', $selected)) {
            return null;
        }

        $path = (new BackupService())->getBackupDir() . $selected;
        return is_file($path) ? $selected : null;
    }
}
