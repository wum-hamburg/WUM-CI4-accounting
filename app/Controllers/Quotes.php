<?php

namespace App\Controllers;

use App\Libraries\DocumentMailer;
use App\Libraries\TcpdfDocument;
use App\Models\CustomerModel;
use App\Models\QuoteModel;
use App\Models\TextBlockModel;
use App\Services\DocumentNumberService;
use App\Services\DocumentPdfService;
use App\Services\DocumentSessionService;
use App\Services\OrderConfirmationService;
use Config\Wum;

class Quotes extends BaseController
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
            return redirect()->to('/dashboard')->with('info', 'Vorgang abgebrochen.');
        }

        if ($this->request->is('get') && session()->get('quote_preview_ready')) {
            return $this->renderPreviewFromSession();
        }

        if (! $this->request->is('post')) {
            return redirect()->to('/documents');
        }

        $this->syncLineItemsFromPost();
        $this->sessionService->recalculateTotal();
        $payload = $this->buildPayload();
        if (isset($payload['redirect'])) {
            return $payload['redirect'];
        }

        $draftDir  = FCPATH . 'drafts' . DIRECTORY_SEPARATOR;
        if (!is_dir($draftDir)) {
            mkdir($draftDir, 0755, true);
        }
        $draftPath = $draftDir . 'Entwurf-Angebot.pdf';
        (new TcpdfDocument())->generate($payload['html'], 'Angebot Entwurf', $draftPath, true);

        session()->set('quote_preview_ready', true);
        session()->set('quote_preview_payload', $payload);

        return redirect()->to('/quotes/preview');
    }

    public function book()
    {
        if (!session()->get('quote_preview_ready')) {
            return redirect()->to('/documents')->with('error', 'Keine Vorschau vorhanden. Bitte Entwurf erneut erstellen.');
        }

        $payload = session()->get('quote_preview_payload');
        if (!is_array($payload)) {
            return redirect()->to('/documents')->with('error', 'Vorschaudaten nicht gefunden.');
        }

        $quoteModel = new QuoteModel();
        $demoLimit  = (int) config('Wum')->demoDocumentLimit;
        if (wum_is_demo() && $quoteModel->countAllResults() >= $demoLimit) {
            return redirect()->to('/dashboard')
                ->with('error', 'Demo: maximal ' . $demoLimit . ' Angebote erlaubt.');
        }

        $numberService = new DocumentNumberService();
        $number        = $numberService->nextQuoteNumber($quoteModel->getLastQuoteNumber());
        $filename      = $numberService->quoteFilename($number);
        $archiveDir    = wum_archives_dir('quotes');
        if (! is_dir($archiveDir)) {
            mkdir($archiveDir, 0755, true);
        }
        $archivePath   = $archiveDir . $filename;

        $html = (new DocumentPdfService())->buildHtml(
            $payload['customer'],
            $payload['lineItems'],
            $payload['totalCents'],
            'Angebot',
            $number,
            $payload['date'],
            $payload['header'],
            $payload['intro'],
            $payload['footer']
        );

        if (!(new TcpdfDocument())->generate($html, 'Angebot ' . $number, $archivePath)) {
            return redirect()->to('/documents')->with('error', 'PDF konnte nicht erstellt werden.');
        }

        $company = trim((string) ($payload['customer']['company'] ?? ''));
        if ($company === '') {
            $company = trim($payload['customer']['first_name'] . ' ' . $payload['customer']['last_name']);
        }

        $meta = $this->sessionService->positionsToMeta(
            session()->get('line_items') ?? [],
            [
                'intro_header' => $payload['header'],
                'intro_items'  => $payload['intro'],
                'footer'       => $payload['footer'],
            ],
            ['email_sent' => false]
        );

        $quoteModel->insert([
            'quote_number'    => $number,
            'amount_cents'    => $payload['totalCents'],
            'quote_date'      => date('Y-m-d'),
            'customer_number' => (string) $payload['customer']['customer_number'],
            'company_name'    => $company,
            'status'          => Wum::QUOTE_OPEN,
            'filename'        => $filename,
            'meta'            => $meta,
        ]);

        $quoteId = (int) $quoteModel->getInsertID();
        (new OrderConfirmationService())->createTokenForQuote($quoteId);

        if ($this->request->getPost('send_email')) {
            $confirmationUrl = (new OrderConfirmationService())->getPublicUrlForQuote($quoteId);
            $result = (new DocumentMailer())->send(
                $payload['customer'],
                'quote',
                $number,
                $payload['date'],
                $archivePath,
                $filename,
                $confirmationUrl
            );
            session()->setFlashdata($result['ok'] ? 'success' : 'error', $result['message']);
            if ($result['ok']) {
                $quoteModel->update($quoteId, [
                    'meta' => $this->sessionService->markEmailSent($meta),
                ]);
            }
        }

        $this->sessionService->reset();
        return redirect()->to('/dashboard')->with('success', 'Angebot ' . $number . ' gebucht.')
            ->with('pdf_file', wum_archives_web_prefix() . '/quotes/' . $filename);
    }

    public function convert(int $id)
    {
        $quoteModel = new QuoteModel();
        $quote      = $quoteModel->find($id);
        if (!$quote) {
            return redirect()->to('/dashboard')->with('error', 'Angebot nicht gefunden.');
        }
        if ((int) $quote['status'] !== Wum::QUOTE_ORDERED) {
            return redirect()->to('/dashboard')->with('error', 'Nur beauftragte Angebote können in Rechnungen umgewandelt werden.');
        }

        $positions = $this->sessionService->positionsFromMeta($quote['meta'] ?? null);
        if ($positions === []) {
            return redirect()->to('/dashboard')->with('error', 'Keine gespeicherten Positionen für dieses Angebot.');
        }

        $invoiced = $this->sessionService->getInvoicedQuantitiesFromMeta($quote['meta'] ?? null);
        $positions = $this->sessionService->applyInvoicedQuantities($positions, $invoiced);

        if ($this->request->is('post')) {
            $lineItems = $this->sessionService->parseConvertPost($this->request->getPost(), $positions);
            if ($lineItems === [] || ! $this->sessionService->hasBillablePositions($lineItems)) {
                return redirect()->back()->with('error', 'Bitte mindestens eine Position mit Menge größer 0 angeben.');
            }

            $this->sessionService->reset();
            session()->set([
                'document_type'   => 'invoice',
                'document_step'   => 4,
                'customer_number' => $quote['customer_number'],
                'line_items'      => $lineItems,
                'text_keys'       => ['schluss1'],
                'source_quote_id' => $id,
            ]);
            $this->sessionService->recalculateTotal();

            return redirect()->to('/documents');
        }

        return view('Quotes/convert', $this->viewData([
            'moduleTitle' => 'Angebot in Rechnung umwandeln',
            'quote'       => $quote,
            'positions'   => $positions,
            'hasInvoice'  => !empty($quote['invoice_id']),
        ]));
    }

    public function setStatus(int $id)
    {
        if (! $this->request->is('post')) {
            return redirect()->to('/dashboard');
        }

        $quoteModel = new QuoteModel();
        $quote      = $quoteModel->find($id);
        if (!$quote) {
            return redirect()->to('/dashboard')->with('error', 'Angebot nicht gefunden.');
        }

        $status = (int) $this->request->getPost('status');
        if ($status !== Wum::QUOTE_DECLINED) {
            return redirect()->to('/dashboard')->with('error', 'Aufträge werden nur über die Kunden-Auftragsbestätigung gesetzt.');
        }

        if ((int) $quote['status'] !== Wum::QUOTE_OPEN) {
            return redirect()->to('/dashboard')->with('error', 'Status kann nur bei offenen Angeboten geändert werden.');
        }

        $quoteModel->update($id, ['status' => $status]);

        return redirect()->to('/dashboard')->with('success', 'Angebot ' . $quote['quote_number'] . ' als „Kein Auftrag" markiert.');
    }

    public function declineConfirmation(int $id)
    {
        if (! $this->request->is('post')) {
            return redirect()->to('/dashboard');
        }

        $result = (new OrderConfirmationService())->declineSubmitted($id);
        $type   = ($result['ok'] ?? false) ? 'success' : 'error';

        return redirect()->to('/dashboard')->with($type, $result['message'] ?? 'Fehler.');
    }

    public function sendEmail(int $id)
    {
        if (! $this->request->is('post')) {
            return redirect()->to('/dashboard');
        }

        $quoteModel = new QuoteModel();
        $quote      = $quoteModel->find($id);
        if (! $quote) {
            return redirect()->to('/dashboard')->with('error', 'Angebot nicht gefunden.');
        }

        if (! $this->sessionService->quoteEmailPending($quote['meta'] ?? null)) {
            return redirect()->to('/dashboard')->with('error', 'E-Mail wurde bereits versendet.');
        }

        $filename = trim((string) ($quote['filename'] ?? ''));
        if ($filename === '') {
            return redirect()->to('/dashboard')->with('error', 'Keine PDF-Datei für dieses Angebot vorhanden.');
        }

        $customer = (new CustomerModel())->findByCustomerNumber($quote['customer_number']);
        if (! $customer) {
            return redirect()->to('/dashboard')->with('error', 'Kunde nicht gefunden.');
        }

        $archivePath = wum_archives_dir('quotes') . $filename;
        if (! is_file($archivePath)) {
            return redirect()->to('/dashboard')->with('error', 'PDF-Datei nicht gefunden.');
        }

        $date            = date('d.m.Y', strtotime($quote['quote_date']));
        $confirmationUrl = (new OrderConfirmationService())->getPublicUrlForQuote($id);

        $result = (new DocumentMailer())->send(
            $customer,
            'quote',
            $quote['quote_number'],
            $date,
            $archivePath,
            $filename,
            $confirmationUrl
        );
        if (! $result['ok']) {
            return redirect()->to('/dashboard')->with('error', $result['message']);
        }

        $quoteModel->update($id, ['meta' => $this->sessionService->markEmailSent($quote['meta'] ?? null)]);

        return redirect()->to('/dashboard')->with('success', $result['message']);
    }

    private function renderPreviewFromSession()
    {
        $payload = session()->get('quote_preview_payload');
        if (!is_array($payload)) {
            return redirect()->to('/documents')->with('error', 'Vorschaudaten nicht gefunden.');
        }

        return view('Quotes/preview', $this->viewData([
            'moduleTitle' => 'Angebot',
            'pdfUrl'      => site_url('files/draft/Entwurf-Angebot.pdf'),
            'payload'     => $payload,
        ]));
    }

    private function syncLineItemsFromPost(): void
    {
        $post = $this->request->getPost();
        if (!empty($post['line_position_number']) || !empty($post['custom_title']) || !empty($post['quantity'])) {
            $this->sessionService->setLineItems($this->sessionService->parseLineItemsFromPost($post), true);
        } else {
            $this->sessionService->syncQuantitiesFromPost($post['quantity'] ?? []);
        }
    }

    private function buildPayload(): array
    {
        if (session()->get('document_type') !== 'quote') {
            return ['redirect' => redirect()->to('/documents')->with('error', 'Kein Angebots-Wizard aktiv.')];
        }
        $customer = (new CustomerModel())->findByCustomerNumber(session()->get('customer_number'));
        if (!$customer) {
            return ['redirect' => redirect()->to('/documents')->with('error', 'Kunde nicht gefunden.')];
        }

        $pdfService = new DocumentPdfService();
        $lineItems  = $pdfService->buildLineItems(session()->get('line_items') ?? []);
        $total      = (int) session()->get('total_cents');
        $date       = date('d.m.Y');
        $header     = $this->request->getPost('intro_header') ?? '';
        $intro      = $this->request->getPost('intro_items') ?? 'Angebotene Leistungen:';
        $footer     = $this->buildFooterFromPost();

        return [
            'customer'   => $customer,
            'lineItems'  => $lineItems,
            'totalCents' => $total,
            'date'       => $date,
            'header'     => $header,
            'intro'      => $intro,
            'footer'     => $footer,
            'html'       => $pdfService->buildHtml(
                $customer,
                $lineItems,
                $total,
                'Angebot',
                null,
                $date,
                $header,
                $intro,
                $footer
            ),
            'postData' => $this->request->getPost(),
        ];
    }

    private function buildFooterFromPost(): string
    {
        $parts = [];
        $textModel = new TextBlockModel();
        foreach (session()->get('text_keys') ?? [] as $key) {
            if (!str_starts_with($key, 'hin')) {
                continue;
            }
            $val = $this->request->getPost($key);
            if ($val) {
                $parts[] = $val;
                continue;
            }
            $block = $textModel->where('text_key', $key)->first();
            if ($block && !empty($block['content'])) {
                $parts[] = $block['content'];
            }
        }
        return implode("\n\n", $parts);
    }
}
