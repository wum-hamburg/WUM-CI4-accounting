<?php

namespace App\Controllers;

use App\Services\OrderConfirmationService;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Public order confirmation form (no login required).
 */
class OrderConfirmation extends Controller
{
    public function show(string $token): string|ResponseInterface
    {
        helper(['form', 'wum']);
        $service = new OrderConfirmationService();
        $context = $service->resolveFormContext($token);

        if (($context['error'] ?? '') === 'already_submitted') {
            return view('OrderConfirmation/already', [
                'pageTitle'    => 'Bereits bestätigt',
                'quote'        => $context['quote'],
                'downloadUrl'  => $service->getDownloadUrl($token),
                'isDemo'       => ($context['db_group'] ?? '') === 'demo',
            ]);
        }

        if (! ($context['ok'] ?? false)) {
            return view('OrderConfirmation/already', [
                'pageTitle'   => 'Nicht verfügbar',
                'message'     => $context['error'] ?? 'Link ungültig.',
                'downloadUrl' => null,
                'isDemo'      => ($context['db_group'] ?? '') === 'demo',
            ]);
        }

        return view('OrderConfirmation/form', [
            'pageTitle'       => 'Auftragsbestätigung',
            'token'           => $token,
            'quote'           => $context['quote'],
            'confirmationDate'=> old('confirmation_date') ?: date('Y-m-d'),
            'privacyPolicyUrl'=> config('Wum')->privacyPolicyUrl,
            'isDemoBanner'    => ($context['db_group'] ?? '') === 'demo',
        ]);
    }

    public function submit(string $token): string|ResponseInterface
    {
        $service = new OrderConfirmationService();
        $result  = $service->submit($token, [
            'notes'              => $this->request->getPost('notes'),
            'confirmation_date'  => $this->request->getPost('confirmation_date'),
            'signature_data'     => $this->request->getPost('signature_data'),
            'email_copy'         => $this->request->getPost('email_copy'),
            'privacy_consent'    => $this->request->getPost('privacy_consent'),
        ]);

        if (! ($result['ok'] ?? false)) {
            return redirect()->back()->withInput()->with('error', $result['message'] ?? 'Fehler beim Senden.');
        }

        return view('OrderConfirmation/success', [
            'pageTitle'   => 'Auftrag übermittelt',
            'message'     => $result['message'],
            'downloadUrl' => $result['download_url'] ?? '',
            'isDemo'      => $service->getDbGroup() === 'demo',
        ]);
    }

    public function pdfPreview(string $token): ResponseInterface
    {
        $service = new OrderConfirmationService();
        $path    = $service->generatePreviewPdf($token, [
            'notes'             => $this->request->getPost('notes'),
            'confirmation_date' => $this->request->getPost('confirmation_date'),
            'signature_data'    => $this->request->getPost('signature_data'),
            'privacy_consent'   => $this->request->getPost('privacy_consent'),
        ]);

        if ($path === null || ! is_file($path)) {
            return redirect()->back()->withInput()->with('error', 'PDF-Vorschau konnte nicht erstellt werden. Bitte alle Pflichtfelder ausfüllen.');
        }

        $content = file_get_contents($path);
        @unlink($path);

        if ($content === false || $content === '') {
            return redirect()->back()->withInput()->with('error', 'PDF-Vorschau konnte nicht gelesen werden.');
        }

        return $this->response->download('Auftragsbestaetigung-Vorschau.pdf', $content);
    }

    public function download(string $token): ResponseInterface
    {
        $service  = new OrderConfirmationService();
        $resolved = $service->resolveDownload($token);
        if ($resolved === null) {
            return $this->response->setStatusCode(404)->setBody('PDF nicht gefunden.');
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $resolved['filename'] . '"')
            ->setBody(file_get_contents($resolved['path']));
    }
}
