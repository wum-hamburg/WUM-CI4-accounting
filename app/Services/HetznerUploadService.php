<?php

namespace App\Services;

use Config\Wum;

/**
 * Upload backup ZIPs to Hetzner Storage Box via FTPS/FTP.
 */
class HetznerUploadService
{
    private string $host;
    private string $user;
    private string $password;
    private string $remoteDir;

    public function __construct(?Wum $config = null)
    {
        $config ??= config(Wum::class);
        $this->host      = trim((string) $config->hetznerHost);
        $this->user      = trim((string) $config->hetznerUser);
        $this->password  = (string) $config->hetznerPassword;
        $this->remoteDir = trim((string) $config->hetznerRemoteDir, '/');
    }

    public function isConfigured(): bool
    {
        return $this->host !== '' && $this->user !== '' && $this->password !== '';
    }

    /**
     * Upload a local file into the configured remote directory (default: Wum/).
     *
     * @return array{ok: bool, message: string}
     */
    public function upload(string $localFile, ?string $remoteFilename = null): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'Hetzner-Zugangsdaten fehlen (wum.hetzner*).'];
        }
        if (! is_file($localFile)) {
            return ['ok' => false, 'message' => 'Lokale Datei nicht gefunden.'];
        }

        $remoteFilename = $remoteFilename !== null && $remoteFilename !== ''
            ? basename($remoteFilename)
            : basename($localFile);
        $remotePath = $this->remoteDir !== ''
            ? $this->remoteDir . '/' . $remoteFilename
            : $remoteFilename;

        return $this->uploadToPath($localFile, $remotePath);
    }

    /**
     * @return array{ok: bool, message: string}
     */
    private function uploadToPath(string $localFile, string $remotePath): array
    {
        $uploadOk    = false;
        $uploadError = '';

        if (function_exists('curl_init')) {
            $fh = @fopen($localFile, 'rb');
            if ($fh !== false) {
                $ch       = curl_init('ftp://' . $this->host . '/' . $remotePath);
                $filesize = @filesize($localFile);
                curl_setopt($ch, CURLOPT_PORT, 21);
                curl_setopt($ch, CURLOPT_USERPWD, $this->user . ':' . $this->password);
                curl_setopt($ch, CURLOPT_UPLOAD, true);
                curl_setopt($ch, CURLOPT_INFILE, $fh);
                if ($filesize !== false) {
                    curl_setopt($ch, CURLOPT_INFILESIZE, (int) $filesize);
                }
                curl_setopt($ch, CURLOPT_USE_SSL, CURLUSESSL_ALL);
                curl_setopt($ch, CURLOPT_FTPSSLAUTH, CURLFTPAUTH_TLS);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_FTP_CREATE_MISSING_DIRS, CURLFTP_CREATE_DIR);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 12);
                curl_setopt($ch, CURLOPT_TIMEOUT, 600);
                $execOk = curl_exec($ch);
                if ($execOk === true) {
                    $uploadOk = true;
                } else {
                    $uploadError = 'cURL: ' . curl_error($ch);
                }
                curl_close($ch);
                @fclose($fh);
            } else {
                $uploadError = 'Lokale ZIP konnte nicht geöffnet werden.';
            }
        }

        if (! $uploadOk && function_exists('ftp_ssl_connect')) {
            $conn = @ftp_ssl_connect($this->host, 21, 12);
            if ($conn !== false && @ftp_login($conn, $this->user, $this->password)) {
                @ftp_pasv($conn, true);
                $this->ensureRemoteDir($conn, $remotePath);
                if (@ftp_put($conn, $remotePath, $localFile, FTP_BINARY)) {
                    $uploadOk = true;
                } else {
                    $uploadError = 'FTP-Upload fehlgeschlagen (ftp_put).';
                }
                @ftp_close($conn);
            } else {
                $uploadError = 'TLS-FTP-Verbindung/Login fehlgeschlagen.';
            }
        }

        if (! $uploadOk && function_exists('ftp_connect')) {
            $conn = @ftp_connect($this->host, 21, 12);
            if ($conn !== false && @ftp_login($conn, $this->user, $this->password)) {
                @ftp_pasv($conn, true);
                $this->ensureRemoteDir($conn, $remotePath);
                if (@ftp_put($conn, $remotePath, $localFile, FTP_BINARY)) {
                    $uploadOk = true;
                } else {
                    $uploadError = 'FTP-Upload fehlgeschlagen (ftp_put).';
                }
                @ftp_close($conn);
            } else {
                $uploadError = 'FTP-Verbindung/Login fehlgeschlagen.';
            }
        }

        return ['ok' => $uploadOk, 'message' => $uploadError];
    }

    /**
     * @param resource|\FTP\Connection $conn
     */
    private function ensureRemoteDir($conn, string $remotePath): void
    {
        $dir = dirname(str_replace('\\', '/', $remotePath));
        if ($dir === '.' || $dir === '/' || $dir === '') {
            return;
        }
        $parts = explode('/', $dir);
        $path  = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $path .= ($path === '' ? '' : '/') . $part;
            @ftp_mkdir($conn, $path);
        }
    }
}
