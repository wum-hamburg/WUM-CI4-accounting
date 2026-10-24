<?php

namespace App\Services;

use App\Controllers\IncomingInvoices;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Temporary PDF storage for incoming invoice review during booking.
 */
class IncomingInvoiceTempUploadService
{
    private string $uploadDir;

    public function __construct()
    {
        $this->uploadDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'incoming' . DIRECTORY_SEPARATOR;
    }

    /**
     * Store uploaded PDF temporarily and return the token (filename without extension path).
     */
    public function store(UploadedFile $file): string
    {
        $this->ensureUploadDir();
        $this->clear();
        $this->cleanupStale();

        $token = bin2hex(random_bytes(16));
        $dest  = $this->uploadDir . $token . '.pdf';
        $tempPath = $file->getTempName();

        if ($tempPath === '' || ! is_file($tempPath)) {
            throw new \RuntimeException('Temporäre Upload-Datei nicht gefunden.');
        }

        if (! copy($tempPath, $dest)) {
            throw new \RuntimeException('Temporäre Datei konnte nicht gespeichert werden.');
        }

        session()->set(IncomingInvoices::SESSION_UPLOAD_TOKEN, $token);

        return $token;
    }

    public function getStoredToken(): ?string
    {
        $token = session()->get(IncomingInvoices::SESSION_UPLOAD_TOKEN);
        if (! is_string($token) || ! preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }

        return $token;
    }

    public function getAbsolutePath(): ?string
    {
        $token = $this->getStoredToken();
        if ($token === null) {
            return null;
        }

        $path = $this->uploadDir . $token . '.pdf';

        return is_file($path) ? $path : null;
    }

    public function getPreviewUrl(): ?string
    {
        $token = $this->getStoredToken();
        if ($token === null || $this->getAbsolutePath() === null) {
            return null;
        }

        return site_url('incoming-invoices/preview-upload/' . $token);
    }

    public function clear(): void
    {
        $path = $this->getAbsolutePath();
        if ($path !== null) {
            @unlink($path);
        }
        session()->remove(IncomingInvoices::SESSION_UPLOAD_TOKEN);
    }

    public function cleanupStale(int $maxAgeSeconds = 86400): void
    {
        if (! is_dir($this->uploadDir)) {
            return;
        }

        $cutoff = time() - $maxAgeSeconds;
        foreach (glob($this->uploadDir . '*.pdf') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }

    public function resolvePathForToken(string $token): ?string
    {
        if (! preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }

        if ($this->getStoredToken() !== $token) {
            return null;
        }

        $path = $this->uploadDir . $token . '.pdf';

        return is_file($path) ? $path : null;
    }

    private function ensureUploadDir(): void
    {
        if (! is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
}
