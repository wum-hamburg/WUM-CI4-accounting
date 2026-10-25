<?php

namespace App\Controllers;

use App\Libraries\DocumentMailer;
use App\Libraries\TcpdfDocument;
use App\Models\CustomerModel;
use App\Models\InvoiceModel;
use App\Models\QuoteModel;
use App\Services\DocumentNumberService;
use App\Services\DocumentPdfService;
use App\Services\DocumentSessionService;
use App\Services\XRechnungXmlService;
use Config\Wum;

class Invoices extends BaseController
{
    private DocumentSessionService $sessionService;

    public function __construct()
    {
        $this->sessionService = new DocumentSessionService();
    }

    public function preview()
    {
        if ($this->request->getPost('abort')) {
            $this->sessionService->reset();
            return redirect()->to('/documents')->with('info', 'Vorgang abgebrochen. Bitte von vorne beginnen.');
        }

        if ($this->request->is('get') && session()->get('invoice_preview_ready')) {
            return $this->renderPreviewFromSession(false);
        }

        if (! $this->request->is('post')) {
            return redirect()->to('/documents');
        }

        $this->syncLineItemsFromPost();
        $this->sessionService->recalculateTotal();
        if (! $this->sessionService->hasBillablePositions(session()->get('line_items') ?? [])) {
            return redirect()->to('/documents')->with('error', 'Bitte mindestens eine Position mit Menge größer 0 angeben.');
        }
        $payload = $this->buildPayload(false);
        if (isset($payload['redirect'])) {
            return $payload['redirect'];
        }

        return $this->storePreviewAndRedirect($payload, false);
    }

    public function previewFromConvert()
    {
        if ($this->request->getPost('abort')) {
            $this->sessionService->reset();
            return redirect()->to('/dashboard')->with('info', 'Umwandlung abgebrochen.');
        }

        if ($this->request->is('get') && session()->get('invoice_preview_ready')) {
            return $this->renderPreviewFromSession(true);
        }

        if (!session()->get('convert_post_data') && session()->get('document_type') !== 'invoice') {
            return redirect()->to('/dashboard')->with('error', 'Keine Umwandlungsdaten in der Session.');
        }

        if ($this->request->is('post')) {
            $this->syncLineItemsFromPost();
            $this->sessionService->recalculateTotal();
        }

        $payload = $this->buildPayload(false);
        if (isset($payload['redirect'])) {
            return $payload['redirect'];
        }

        return $this->storePreviewAndRedirect($payload, true);
    }

    public function book()
    {
        if (!session()->get('invoice_preview_ready')) {
            return redirect()->to('/documents')->with('error', 'Keine Vorschau vorhanden. Bitte Entwurf erneut erstellen.');
        }

        $payload = session()->get('invoice_preview_payload');
        if (!is_array($payload)) {
            return redirect()->to('/documents')->with('error', 'Vorschaudaten nicht gefunden.');
        }

        $invoiceModel = new InvoiceModel();
        $demoLimit    = (int) config('Wum')->demoDocumentLimit;
        if (wum_is_demo() && $invoiceModel->countAllResults() >= $demoLimit) {
            return redirect()->to('/dashboard')
                ->with('error', 'Demo: maximal ' . $demoLimit . ' Rechnungen erlaubt.');
        }

        $numberService = new DocumentNumberService();
        $customer      = $payload['customer'];
        $correctionOf  = session()->get('correction_of');
        $correctionOf  = is_string($correctionOf) && $correctionOf !== '' ? $correctionOf : null;

        $invoiceFolder = null;
        if (! wum_is_demo()) {
            $invoiceFolder = $numberService->ensureCustomerInvoiceFolder($customer, $correctionOf);
            if ($invoiceFolder === null || $invoiceFolder === '') {
                return redirect()->to('/documents')->with('error', 'Rechnungsordner konnte nicht ermittelt werden.');
            }
            $payload['customer'] = $customer;
        }

        $number   = $numberService->nextInvoiceNumber($invoiceModel->getLastInvoiceNumber());
        $filename = $numberService->invoiceFilename($number, $customer);

        $archiveDir = wum_invoice_dir($invoiceFolder);
        if (! is_dir($archiveDir)) {
            if (! wum_is_demo() && $invoiceFolder !== null && $invoiceFolder !== '') {
                if (! wum_ensure_invoice_folder($invoiceFolder)) {
                    return redirect()->to('/documents')->with('error', 'Rechnungsordner konnte nicht angelegt werden.');
                }
                $archiveDir = wum_invoice_dir($invoiceFolder);
            } else {
                mkdir($archiveDir, 0755, true);
            }
        }
        $archivePath = $archiveDir . $filename;

        $status   = $correctionOf ? Wum::INVOICE_CORRECTION : Wum::INVOICE_OPEN;
        $docLabel = 'Rechnung';

        $html = (new DocumentPdfService())->buildHtml(
            $payload['customer'],
            $payload['lineItems'],
            $payload['totalCents'],
            $docLabel,
            $number,
            $payload['date'],
            $payload['header'],
            $payload['intro'],
            $payload['footer'],
            $payload['discountCents'],
            $payload['discountPercent'],
            $correctionOf
        );

        if (!(new TcpdfDocument())->generate(
            $html,
            'Rechnung ' . $number,
            $archivePath,
            false,
            40.0,
            false,
            (new XRechnungXmlService())->buildFromPayload($payload, $number, $correctionOf)
        )) {
            return redirect()->to('/documents')->with('error', 'PDF konnte nicht erstellt werden.');
        }

        $extra = [];
        if ($correctionOf) {
            $extra['correction_of'] = $correctionOf;
        }

        if ($this->request->getPost('send_email')) {
            $result = (new DocumentMailer())->send($payload['customer'], 'invoice', $number, $payload['date'], $archivePath, $filename);
            session()->setFlashdata($result['ok'] ? 'success' : 'error', $result['message']);
            $extra['email_sent'] = $result['ok'];
        }

        $meta = $this->sessionService->positionsToMeta(
            session()->get('line_items') ?? [],
            [
                'intro_header' => $payload['header'],
                'intro_items'  => $payload['intro'],
                'footer'       => $payload['footer'],
            ],
            $extra
        );

        $sourceQuoteId = session()->get('source_quote_id');

        $invoiceModel->insert([
            'invoice_number'     => $number,
            'amount_cents'       => $payload['totalCents'],
            'invoice_date'       => date('Y-m-d'),
            'customer_number'    => (string) $payload['customer']['customer_number'],
            'customer_last_name' => $payload['customer']['last_name'],
            'status'             => $status,
            'filename'           => $filename,
            'meta'               => $meta,
            'source_quote_id'    => $sourceQuoteId ?: null,
        ]);

        $invoiceId = $invoiceModel->getInsertID();

        if ($sourceQuoteId) {
            $quoteModel = new QuoteModel();
            $quote      = $quoteModel->find($sourceQuoteId);
            if ($quote) {
                $quoteMeta = $this->sessionService->recordInvoicedQuantities(
                    $quote['meta'] ?? '{}',
                    $this->sessionService->normalizeLineItems(session()->get('line_items') ?? [])
                );
                $quoteModel->update($sourceQuoteId, [
                    'invoice_id' => $invoiceId,
                    'meta'       => $quoteMeta,
                ]);
            }
        }

        $flashSuccess = 'Rechnung ' . $number . ' gebucht.';
        if ($sourceQuoteId) {
            $flashSuccess .= ' Weitere Rechnung aus dem Angebot ist jederzeit möglich.';
        }

        $this->sessionService->reset();
        return redirect()->to('/dashboard')->with('success', $flashSuccess)
            ->with('pdf_file', wum_invoice_web_path($invoiceFolder, $filename));
    }

    public function setStatus(int $id)
    {
        if (! $this->request->is('post')) {
            return $this->statusRedirect();
        }

        $invoiceModel = new InvoiceModel();
        $invoice      = $invoiceModel->find($id);
        if (! $invoice) {
            return $this->statusRedirect('error', 'Rechnung nicht gefunden.');
        }

        $status = (int) $this->request->getPost('status');
        if (! in_array($status, [Wum::INVOICE_OPEN, Wum::INVOICE_PAID], true)) {
            return $this->statusRedirect('error', 'Ungültiger Status.');
        }

        $current = (int) $invoice['status'];
        if ($current === Wum::INVOICE_CORRECTION) {
            return $this->statusRedirect('error', 'Korrekturrechnungen können nicht als bezahlt markiert werden.');
        }

        if ($current !== Wum::INVOICE_OPEN && $current !== Wum::INVOICE_PAID) {
            return $this->statusRedirect('error', 'Status kann nicht geändert werden.');
        }

        if ($status === $current) {
            return $this->statusRedirect();
        }

        $invoiceModel->update($id, ['status' => $status]);

        $label = $status === Wum::INVOICE_PAID ? 'bezahlt' : 'offen';
        return $this->statusRedirect('success', 'Rechnung ' . $invoice['invoice_number'] . ' als ' . $label . ' markiert.');
    }

    private function statusRedirect(string $type = '', string $message = '')
    {
        $target = '/dashboard';
        if ($this->request->getPost('redirect') === 'guv') {
            $year = (int) $this->request->getPost('year');
            $target = ($year >= 2025 && $year <= 2100) ? '/guv?year=' . $year : '/guv';
        }

        $redirect = redirect()->to($target);
        if ($type !== '' && $message !== '') {
            $redirect = $redirect->with($type, $message);
        }

        return $redirect;
    }

    public function sendEmail(int $id)
    {
        if (! $this->request->is('post')) {
            return redirect()->to('/dashboard');
        }

        $invoiceModel = new InvoiceModel();
        $invoice      = $invoiceModel->find($id);
        if (! $invoice) {
            return redirect()->to('/dashboard')->with('error', 'Rechnung nicht gefunden.');
        }

        if (! $this->sessionService->invoiceEmailRetryNeeded($invoice['meta'] ?? null)) {
            return redirect()->to('/dashboard')->with('error', 'E-Mail wurde bereits versendet.');
        }

        $filename = trim((string) ($invoice['filename'] ?? ''));
        if ($filename === '') {
            return redirect()->to('/dashboard')->with('error', 'Keine PDF-Datei für diese Rechnung vorhanden.');
        }

        $customer = (new CustomerModel())->findByCustomerNumber($invoice['customer_number']);
        if (! $customer) {
            return redirect()->to('/dashboard')->with('error', 'Kunde nicht gefunden.');
        }

        $numberService = new DocumentNumberService();
        $folder        = null;
        if (! wum_is_demo()) {
            $folder = trim((string) ($customer['invoice_folder'] ?? ''));
            if ($folder === '') {
                $folder = $numberService->findFolderForInvoiceFilename($filename)
                    ?? $numberService->ensureCustomerInvoiceFolder($customer);
            }
            if ($folder === null || $folder === '') {
                return redirect()->to('/dashboard')->with('error', 'Rechnungsordner konnte nicht ermittelt werden.');
            }
        }

        $archivePath = wum_invoice_dir($folder) . $filename;
        if (! is_file($archivePath)) {
            return redirect()->to('/dashboard')->with('error', 'PDF-Datei nicht gefunden.');
        }

        $date   = date('d.m.Y', strtotime($invoice['invoice_date']));
        $result = (new DocumentMailer())->send($customer, 'invoice', $invoice['invoice_number'], $date, $archivePath, $filename);
        if (! $result['ok']) {
            return redirect()->to('/dashboard')->with('error', $result['message']);
        }

        $invoiceModel->update($id, ['meta' => $this->sessionService->markEmailSent($invoice['meta'] ?? null)]);

        return redirect()->to('/dashboard')->with('success', $result['message']);
    }

    public function correction(int $id)
    {
        $invoiceModel = new InvoiceModel();
        $invoice      = $invoiceModel->find($id);
        if (!$invoice) {
            return redirect()->to('/dashboard')->with('error', 'Rechnung nicht gefunden.');
        }
        if ((int) $invoice['status'] === Wum::INVOICE_CORRECTION) {
            return redirect()->to('/dashboard')->with('error', 'Von einer Korrekturrechnung kann keine weitere Korrektur erstellt werden.');
        }

        $positions = $this->sessionService->positionsFromMeta($invoice['meta'] ?? null);

        $this->sessionService->reset();
        session()->set([
            'document_type'   => 'invoice',
            'document_step'   => 4,
            'customer_number' => $invoice['customer_number'],
            'line_items'      => $positions,
            'text_keys'       => ['schluss1'],
            'correction_of'   => $invoice['invoice_number'],
        ]);
        $this->sessionService->recalculateTotal();

        return redirect()->to('/documents');
    }

    private function storePreviewAndRedirect(array $payload, bool $fromConvert)
    {
        $draftDir  = FCPATH . 'drafts' . DIRECTORY_SEPARATOR;
        if (!is_dir($draftDir)) {
            mkdir($draftDir, 0755, true);
        }
        $draftPath = $draftDir . 'Entwurf.pdf';
        (new TcpdfDocument())->generate($payload['html'], 'Rechnung Entwurf', $draftPath, true);

        session()->set('invoice_preview_ready', true);
        session()->set('invoice_preview_payload', $payload);
        session()->set('invoice_preview_from_convert', $fromConvert);

        return redirect()->to($fromConvert ? '/invoices/preview-from-convert' : '/invoices/preview');
    }

    private function renderPreviewFromSession(bool $fromConvert)
    {
        $payload = session()->get('invoice_preview_payload');
        if (!is_array($payload)) {
            return redirect()->to('/documents')->with('error', 'Vorschaudaten nicht gefunden.');
        }

        return view('Invoices/preview', $this->viewData([
            'moduleTitle' => 'Rechnung',
            'pdfUrl'      => site_url('files/draft/Entwurf.pdf'),
            'payload'     => $payload,
            'fromConvert' => $fromConvert || session()->get('invoice_preview_from_convert'),
        ]));
    }

    private function syncLineItemsFromPost(): void
    {
        $post = $this->request->getPost();
        if ($post === [] && session()->get('convert_post_data')) {
            return;
        }
        if (!empty($post['line_position_number']) || !empty($post['custom_title']) || !empty($post['quantity'])) {
            $this->sessionService->setLineItems($this->sessionService->parseLineItemsFromPost($post), true);
        } elseif (!empty($post['quantity'])) {
            $this->sessionService->syncQuantitiesFromPost($post['quantity']);
        }
    }

    private function buildPayload(bool $book): array
    {
        if (!session()->get('document_type') || session()->get('document_type') !== 'invoice') {
            return ['redirect' => redirect()->to('/documents')->with('error', 'Kein Rechnungs-Wizard aktiv.')];
        }
        $customer = (new CustomerModel())->findByCustomerNumber(session()->get('customer_number'));
        if (!$customer) {
            return ['redirect' => redirect()->to('/documents')->with('error', 'Kunde nicht gefunden.')];
        }

        $pdfService   = new DocumentPdfService();
        $lineItems    = $pdfService->buildLineItems(session()->get('line_items') ?? []);
        $total        = (int) session()->get('total_cents');
        $date         = date('d.m.Y');
        $header       = $this->postValue('intro_header') ?? '';
        $intro        = $this->postValue('intro_items') ?? 'Aktuelle Rechnungsartikel:';
        $footer       = $this->buildFooterFromPost();
        $correctionOf = session()->get('correction_of');

        return [
            'customer'        => $customer,
            'lineItems'       => $lineItems,
            'totalCents'      => $total,
            'date'            => $date,
            'header'          => $header,
            'intro'           => $intro,
            'footer'          => $footer,
            'discountCents'   => session()->get('discount_cents'),
            'discountPercent' => session()->get('discount_percent'),
            'html'            => $pdfService->buildHtml(
                $customer,
                $lineItems,
                $total,
                'Rechnung',
                $book ? 'X' : null,
                $date,
                $header,
                $intro,
                $footer,
                session()->get('discount_cents'),
                session()->get('discount_percent'),
                $correctionOf
            ),
            'postData' => $this->getEffectivePostData(),
        ];
    }

    private function getEffectivePostData(): array
    {
        $post = $this->request->getPost();
        if ($post === [] && session()->get('convert_post_data')) {
            return session()->get('convert_post_data');
        }
        return $post;
    }

    private function postValue(string $key): ?string
    {
        $val = $this->request->getPost($key);
        if ($val !== null && $val !== '') {
            return (string) $val;
        }
        $convert = session()->get('convert_post_data');
        if (is_array($convert) && isset($convert[$key])) {
            return (string) $convert[$key];
        }
        return null;
    }

    private function buildFooterFromPost(): string
    {
        $parts = [];
        foreach (session()->get('text_keys') ?? [] as $key) {
            if (!str_starts_with($key, 'schluss')) {
                continue;
            }
            $val = $this->postValue($key);
            if ($val) {
                $parts[] = $val;
            }
        }
        if ($parts === [] && $this->postValue('schluss1')) {
            $parts[] = $this->postValue('schluss1');
        }
        return implode("\n\n", $parts);
    }
}
