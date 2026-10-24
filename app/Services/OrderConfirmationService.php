<?php

namespace App\Services;

use App\Libraries\DocumentMailer;
use App\Models\CustomerModel;
use App\Models\QuoteConfirmationModel;
use App\Models\QuoteModel;
use Config\Database;
use Config\Wum;

/**
 * Token lifecycle, validation and submission for public quote order confirmations.
 */
class OrderConfirmationService
{
    private string $dbGroup = 'default';
    private QuoteConfirmationModel $confirmationModel;
    private QuoteModel $quoteModel;

    public function __construct(
        ?QuoteConfirmationModel $confirmationModel = null,
        ?QuoteModel $quoteModel = null
    ) {
        $this->confirmationModel = $confirmationModel ?? new QuoteConfirmationModel();
        $this->quoteModel        = $quoteModel ?? new QuoteModel();
    }

    public function getDbGroup(): string
    {
        return $this->dbGroup;
    }

    /**
     * Create a pending confirmation row with a unique token for a newly booked quote.
     */
    public function createTokenForQuote(int $quoteId): string
    {
        $existing = $this->confirmationModel->findByQuoteId($quoteId);
        if ($existing !== null) {
            return (string) $existing['token'];
        }

        $token = $this->generateUniqueToken();
        $this->confirmationModel->insert([
            'quote_id' => $quoteId,
            'token'    => $token,
        ]);

        return $token;
    }

    public function ensureTokenForQuote(int $quoteId): string
    {
        return $this->createTokenForQuote($quoteId);
    }

    public function getPublicUrl(string $token): string
    {
        return site_url('auftragsbestaetigung/' . rawurlencode($token));
    }

    public function getPublicUrlForQuote(int $quoteId): string
    {
        return $this->getPublicUrl($this->ensureTokenForQuote($quoteId));
    }

    public function getDownloadUrl(string $token): string
    {
        return site_url('auftragsbestaetigung/' . rawurlencode($token) . '/download');
    }

    /**
     * @return array{
     *     ok: bool,
     *     error?: string,
     *     quote?: array,
     *     confirmation?: array,
     *     db_group?: string
     * }
     */
    public function resolveFormContext(string $token): array
    {
        $resolved = $this->resolveToken($token);
        if ($resolved === null) {
            return ['ok' => false, 'error' => 'Ungültiger oder abgelaufener Link.'];
        }

        ['confirmation' => $confirmation, 'quote' => $quote, 'db_group' => $dbGroup] = $resolved;

        if ($this->confirmationModel->isSubmitted($confirmation)) {
            return [
                'ok'           => false,
                'error'        => 'already_submitted',
                'quote'        => $quote,
                'confirmation' => $confirmation,
                'db_group'     => $dbGroup,
            ];
        }

        if (! empty($confirmation['declined_at'])) {
            return ['ok' => false, 'error' => 'Diese Auftragsbestätigung ist nicht mehr gültig.'];
        }

        $status = (int) ($quote['status'] ?? 0);
        if ($status === Wum::QUOTE_DECLINED) {
            return ['ok' => false, 'error' => 'Für dieses Angebot liegt kein Auftrag mehr vor.'];
        }

        if ($status !== Wum::QUOTE_OPEN) {
            return ['ok' => false, 'error' => 'Dieses Angebot kann nicht mehr online bestätigt werden.'];
        }

        return [
            'ok'           => true,
            'quote'        => $quote,
            'confirmation' => $confirmation,
            'db_group'     => $dbGroup,
        ];
    }

    /**
     * @return array{ok: bool, message?: string, download_url?: string}
     */
    public function submit(string $token, array $input): array
    {
        $context = $this->resolveFormContext($token);
        if (! ($context['ok'] ?? false)) {
            if (($context['error'] ?? '') === 'already_submitted') {
                return ['ok' => false, 'message' => 'Dieses Angebot wurde bereits bestätigt.'];
            }

            return ['ok' => false, 'message' => $context['error'] ?? 'Ungültige Anfrage.'];
        }

        $quote        = $context['quote'];
        $confirmation = $context['confirmation'];
        $quoteId      = (int) $quote['id'];

        $validation = $this->validateSubmissionInput($input);
        if ($validation !== null) {
            return ['ok' => false, 'message' => $validation];
        }

        $customer = (new CustomerModel())->findByCustomerNumber((string) ($quote['customer_number'] ?? ''));
        if ($customer === null) {
            return ['ok' => false, 'message' => 'Kunde zum Angebot nicht gefunden.'];
        }

        $formData = [
            'notes'              => trim((string) ($input['notes'] ?? '')),
            'confirmation_date'  => (string) $input['confirmation_date'],
            'signature_data'     => (string) $input['signature_data'],
            'email_copy'         => ($this->dbGroup === 'demo' || (function_exists('wum_is_demo') && wum_is_demo()))
                ? 0
                : (! empty($input['email_copy']) ? 1 : 0),
            'privacy_consented'  => ! empty($input['privacy_consent']),
            'submitted_at'       => date('Y-m-d H:i:s'),
        ];

        $pdfService = new OrderConfirmationPdfService();
        $filename   = $pdfService->confirmationFilename((string) $quote['quote_number']);
        $archiveDir = $this->archivesDir('quote-confirmations');
        if (! is_dir($archiveDir)) {
            mkdir($archiveDir, 0755, true);
        }
        $archivePath = $archiveDir . $filename;

        if (! $pdfService->generate($quote, $customer, $formData, $archivePath)) {
            return ['ok' => false, 'message' => 'PDF konnte nicht erstellt werden.'];
        }

        $now = date('Y-m-d H:i:s');
        $this->confirmationModel->update((int) $confirmation['id'], [
            'notes'                => $formData['notes'] !== '' ? $formData['notes'] : null,
            'confirmation_date'    => $formData['confirmation_date'],
            'signature_data'       => $formData['signature_data'],
            'email_copy'           => $formData['email_copy'],
            'privacy_consented_at' => $now,
            'pdf_filename'         => $filename,
            'submitted_at'         => $now,
        ]);

        $this->quoteModel->update($quoteId, ['status' => Wum::QUOTE_ORDERED]);

        $this->sendNotifications($customer, $quote, $archivePath, $filename, (bool) $formData['email_copy']);

        return [
            'ok'           => true,
            'message'      => 'Vielen Dank – Ihr Auftrag wurde übermittelt.',
            'download_url' => $this->getDownloadUrl($token),
        ];
    }

    /**
     * Preview PDF from current form values without changing status.
     */
    public function generatePreviewPdf(string $token, array $input): ?string
    {
        $context = $this->resolveFormContext($token);
        if (! ($context['ok'] ?? false)) {
            return null;
        }

        $validation = $this->validateSubmissionInput($input, false);
        if ($validation !== null) {
            return null;
        }

        $quote    = $context['quote'];
        $customer = (new CustomerModel())->findByCustomerNumber((string) ($quote['customer_number'] ?? ''));
        if ($customer === null) {
            return null;
        }

        $formData = [
            'notes'             => trim((string) ($input['notes'] ?? '')),
            'confirmation_date' => (string) $input['confirmation_date'],
            'signature_data'    => (string) ($input['signature_data'] ?? ''),
            'privacy_consented' => ! empty($input['privacy_consent']),
            'submitted_at'      => date('Y-m-d H:i:s'),
        ];

        $tempPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'preview_' . bin2hex(random_bytes(8)) . '.pdf';
        if (! (new OrderConfirmationPdfService())->generate($quote, $customer, $formData, $tempPath)) {
            return null;
        }

        return $tempPath;
    }

    /**
     * @return array{path: string, filename: string}|null
     */
    public function resolveDownload(string $token): ?array
    {
        $resolved = $this->resolveToken($token);
        if ($resolved === null) {
            return null;
        }

        $confirmation = $resolved['confirmation'];
        if (! $this->confirmationModel->isSubmitted($confirmation)) {
            return null;
        }

        $filename = trim((string) ($confirmation['pdf_filename'] ?? ''));
        if ($filename === '') {
            return null;
        }

        $path = $this->archivesDir('quote-confirmations') . $filename;
        if (! is_file($path)) {
            return null;
        }

        return ['path' => $path, 'filename' => $filename];
    }

    /**
     * Staff declines a submitted confirmation — keeps PDF for audit.
     */
    public function declineSubmitted(int $quoteId): array
    {
        $quote = $this->quoteModel->find($quoteId);
        if ($quote === null) {
            return ['ok' => false, 'message' => 'Angebot nicht gefunden.'];
        }

        if ((int) $quote['status'] !== Wum::QUOTE_ORDERED) {
            return ['ok' => false, 'message' => 'Nur bestätigte Aufträge können abgelehnt werden.'];
        }

        $confirmation = $this->confirmationModel->findByQuoteId($quoteId);
        if ($confirmation === null || ! $this->confirmationModel->isSubmitted($confirmation)) {
            return ['ok' => false, 'message' => 'Keine eingegangene Auftragsbestätigung vorhanden.'];
        }

        $now = date('Y-m-d H:i:s');
        $this->quoteModel->update($quoteId, ['status' => Wum::QUOTE_DECLINED]);
        $this->confirmationModel->update((int) $confirmation['id'], ['declined_at' => $now]);

        return [
            'ok'      => true,
            'message' => 'Auftragsbestätigung zu Angebot ' . $quote['quote_number'] . ' abgelehnt (Kein Auftrag).',
        ];
    }

    /**
     * @return array{confirmation: array, quote: array, db_group: string}|null
     */
    private function resolveToken(string $token): ?array
    {
        foreach (['default', 'demo'] as $group) {
            $this->useDbGroup($group);
            $confirmation = $this->confirmationModel->findByToken($token);
            if ($confirmation === null) {
                continue;
            }

            $quote = $this->quoteModel->find((int) $confirmation['quote_id']);
            if ($quote === null) {
                continue;
            }

            return [
                'confirmation' => $confirmation,
                'quote'        => $quote,
                'db_group'     => $group,
            ];
        }

        return null;
    }

    private function useDbGroup(string $group): void
    {
        $this->dbGroup = $group;
        $config        = config(Database::class);
        $config->defaultGroup = $group;
        Database::connect();

        $this->confirmationModel = new QuoteConfirmationModel();
        $this->quoteModel        = new QuoteModel();
    }

    private function validateSubmissionInput(array $input, bool $requirePrivacy = true): ?string
    {
        $signature = (string) ($input['signature_data'] ?? '');
        if ($signature === '' || ! str_starts_with($signature, 'data:image/')) {
            return 'Bitte unterschreiben Sie im Unterschriftsfeld.';
        }

        if ($requirePrivacy && empty($input['privacy_consent'])) {
            return 'Bitte stimmen Sie der Datenschutzerklärung zu.';
        }

        $date = (string) ($input['confirmation_date'] ?? '');
        if ($date === '' || strtotime($date) === false) {
            return 'Bitte geben Sie ein gültiges Datum an.';
        }

        return null;
    }

    private function sendNotifications(
        array $customer,
        array $quote,
        string $pdfPath,
        string $pdfName,
        bool $emailCopy
    ): void {
        if ($this->dbGroup === 'demo' || (function_exists('wum_is_demo') && wum_is_demo())) {
            return;
        }

        $mailer = new DocumentMailer();
        $mailer->sendOrderConfirmationToOwner($customer, $quote, $pdfPath, $pdfName);

        if ($emailCopy) {
            $mailer->sendOrderConfirmationCopyToCustomer($customer, $quote, $pdfPath, $pdfName);
        }
    }

    public function archivesWebPrefix(): string
    {
        return $this->dbGroup === 'demo' ? 'demo/archives' : 'archives';
    }

    public function archivesDir(string $type): string
    {
        $type = basename($type);
        $base = $this->dbGroup === 'demo'
            ? WRITEPATH . 'demo' . DIRECTORY_SEPARATOR . 'archives'
            : WRITEPATH . 'archives';

        return $base . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR;
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = bin2hex(random_bytes(32));
        } while ($this->confirmationModel->findByToken($token) !== null);

        return $token;
    }
}
