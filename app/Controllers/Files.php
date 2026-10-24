<?php

namespace App\Controllers;

class Files extends BaseController
{
    /**
     * Serve archived PDF as download / PDF aus Archiv herunterladen
     */
    public function archive(...$segments)
    {
        $path = $this->resolveArchivePath($segments);
        if ($path === null) {
            return $this->response->setStatusCode(404)->setBody('Datei nicht gefunden.');
        }
        return $this->response->download($path, null);
    }

    /**
     * Serve archived PDF inline in browser / PDF inline anzeigen
     */
    public function view(...$segments)
    {
        $path = $this->resolveArchivePath($segments);
        if ($path === null) {
            return $this->response->setStatusCode(404)->setBody('Datei nicht gefunden.');
        }
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($path) . '"')
            ->setBody(file_get_contents($path));
    }

    public function draft($filename)
    {
        $path = FCPATH . 'drafts' . DIRECTORY_SEPARATOR . basename($filename);
        if (!is_file($path)) {
            return $this->response->setStatusCode(404)->setBody('Entwurf nicht gefunden.');
        }
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($path) . '"')
            ->setBody(file_get_contents($path));
    }

    private function resolveArchivePath(array $segments): ?string
    {
        // Route may pass one capture ("shared/invoices/a/b.pdf") or multiple segments.
        $relative = implode('/', array_map(
            static fn ($s) => is_string($s) ? rawurldecode($s) : '',
            $segments
        ));
        $relative = str_replace(['..', '\\'], '', $relative);
        $relative = trim(rawurldecode($relative), '/');

        // Shared webroot invoices/{folder}/{file}.pdf
        if (str_starts_with($relative, 'shared/invoices/')) {
            $parts = explode('/', $relative);
            // shared / invoices / {folder} / {file}
            if (count($parts) !== 4) {
                return null;
            }
            $folder   = basename($parts[2]);
            $filename = basename($parts[3]);
            if ($folder === '' || $folder === '.' || $folder === '..'
                || $filename === '' || $filename === '.' || $filename === '..'
                || ! str_ends_with(strtolower($filename), '.pdf')) {
                return null;
            }
            $path = wum_shared_invoices_root() . $folder . DIRECTORY_SEPARATOR . $filename;
            return is_file($path) ? $path : null;
        }

        // Only allow access to PDF archives under WRITEPATH (quotes + demo invoices).
        $allowed = [
            'archives/quotes/',
            'archives/quote-confirmations/',
            'archives/guv/',
            'demo/archives/invoices/',
            'demo/archives/quotes/',
            'demo/archives/quote-confirmations/',
        ];
        $permitted = false;
        foreach ($allowed as $prefix) {
            if (str_starts_with($relative, $prefix)) {
                $permitted = true;
                break;
            }
        }
        if (! $permitted) {
            return null;
        }

        $path = WRITEPATH . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        return is_file($path) ? $path : null;
    }
}
