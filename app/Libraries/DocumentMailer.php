<?php

namespace App\Libraries;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Send invoice/quote PDFs via SMTP / E-Mail-Versand
 */
class DocumentMailer
{
    public function send(array $customer, string $documentType, string $number, string $date, string $pdfPath, string $pdfName, ?string $confirmationUrl = null): array
    {
        if (function_exists('wum_is_demo') ? wum_is_demo() : ((bool) (session()->get('is_demo') ?? false))) {
            return ['ok' => false, 'message' => 'E-Mail-Versand ist in der Demo-Version deaktiviert.'];
        }

        $email = trim((string) ($customer['email'] ?? ''));
        if ($email === '' || !str_contains($email, '@')) {
            return ['ok' => false, 'message' => 'Keine gültige E-Mail-Adresse beim Kunden.'];
        }

        $mail = new PHPMailer(true);
        try {
            $mail->CharSet    = 'UTF-8';
            $mail->isSMTP();
            $mail->Host       = env('email.SMTPHost', '');
            $mail->SMTPAuth   = true;
            $mail->Username   = env('email.SMTPUser', '');
            $mail->Password   = env('email.SMTPPass', '');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) env('email.SMTPPort', 587);
            $mail->SMTPOptions = [
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            ];

            $fromEmail = env('email.fromEmail', 'info@wum.hamburg');
            $fromName  = env('email.fromName', 'Webseiten und mehr - Hamburg');
            $mail->setFrom($fromEmail, $fromName);
            $recipientName = trim((string) ($customer['company'] ?? ''));
            if ($recipientName === '') {
                $recipientName = trim($customer['first_name'] . ' ' . $customer['last_name']);
            }
            $mail->addAddress($email, $recipientName);
            $bcc = env('email.bcc', 'rechnungsversand@wum-hamburg.de');
            if ($bcc !== '') {
                $mail->addBCC($bcc);
            }
            $mail->addCustomHeader('Disposition-Notification-To: ' . $fromEmail);
            $mail->addAttachment($pdfPath, $pdfName);

            $label = $documentType === 'quote' ? 'Angebot' : 'Rechnung';
            $mail->isHTML(true);
            $mail->Subject = 'Ihr aktuelles ' . $label . ' Nummer: ' . $number;
            $mail->Body    = $this->buildBody($customer, $label, $number, $date, $confirmationUrl);

            $logoPath = FCPATH . 'images' . DIRECTORY_SEPARATOR . 'logo-klein.jpg';
            if (is_file($logoPath)) {
                $mail->addEmbeddedImage($logoPath, 'firmenlogo', 'logo-klein.jpg');
            }

            $mail->send();
            return ['ok' => true, 'message' => 'E-Mail wurde erfolgreich gesendet.'];
        } catch (Exception $e) {
            return ['ok' => false, 'message' => 'E-Mail konnte nicht gesendet werden: ' . $mail->ErrorInfo];
        }
    }

    private function buildBody(array $customer, string $label, string $number, string $date, ?string $confirmationUrl = null): string
    {
        $firstName = esc($customer['first_name'] ?? '');

        $confirmationBlock = '';
        if ($confirmationUrl !== null && $confirmationUrl !== '' && $label === 'Angebot') {
            $confirmationBlock = '<p><strong>Online beauftragen:</strong><br>'
                . 'Hiermit können Sie das Angebot online bestätigen:<br>'
                . '<a href="' . esc($confirmationUrl) . '">Auftragsbestätigung für Angebot Nr. ' . esc($number) . '</a></p>';
        }

        return '<html><head><meta charset="UTF-8"></head><body>
            <h4>Automatisch generierte E-Mail</h4>
            <p>Hallo ' . $firstName . ',</p>
            <p>anbei erhalten Sie Ihr ' . esc($label) . ' Nr. ' . esc($number) . ' vom ' . esc($date) . '.</p>
            ' . $confirmationBlock . '
            <p>Bei Fragen wenden Sie sich bitte per E-Mail an mich.</p>
            <br>
            <p>Ihr ' . esc($label) . ' wurde im PDF-Format erstellt. Durch einen Doppelklick auf den Anhang wird es automatisch<br>
            mit einem PDF-Reader, z. B. Adobe Acrobat Reader, geöffnet. Sollten Sie keinen PDF-Reader installiert haben,<br>
            können Sie den kostenlosen Adobe Acrobat Reader unter folgendem Link herunterladen:</p>
            <p><a href="http://www.adobe.de/products/acrobat/readstep.html" target="_blank">http://www.adobe.de/products/acrobat/readstep.html</a></p>
            <p>Bei Rückfragen stehe ich Ihnen gerne zur Verfügung.</p>
            <br>
            <div style="text-align: left;">
                <p>Mit freundlichen Grüßen</p>
                <p>Marko Jorissen</p>
                <img src="cid:firmenlogo" alt="Firmenlogo" style="width:200px;">
                <br>
                ' . $this->buildSignatureFooter() . '
            </div>
        </body></html>';
    }

    /**
     * @param array<string, mixed> $customer
     * @param array<string, mixed> $quote
     */
    public function sendOrderConfirmationToOwner(array $customer, array $quote, string $pdfPath, string $pdfName): array
    {
        if (function_exists('wum_is_demo') && wum_is_demo()) {
            return ['ok' => false, 'message' => 'Demo: E-Mail deaktiviert.'];
        }

        $wum     = config('Wum');
        $toEmail = trim($wum->orderConfirmationOwnerEmail);
        if ($toEmail === '') {
            $toEmail = (string) env('email.fromEmail', 'info@wum.hamburg');
        }

        $company = trim((string) ($customer['company'] ?? ''));
        if ($company === '') {
            $company = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        }

        $number = (string) ($quote['quote_number'] ?? '');

        return $this->sendSimpleMail(
            $toEmail,
            'Marko Jorissen',
            'Auftragsbestätigung: Angebot Nr. ' . $number . ' – ' . $company,
            '<p>Es liegt eine neue Auftragsbestätigung zu Angebot Nr. <strong>' . esc($number) . '</strong> vor.</p>'
            . '<p>Kunde: ' . esc($company) . '</p>'
            . '<p>Die bestätigte PDF ist im Anhang.</p>',
            $pdfPath,
            $pdfName
        );
    }

    /**
     * @param array<string, mixed> $customer
     * @param array<string, mixed> $quote
     */
    public function sendOrderConfirmationCopyToCustomer(array $customer, array $quote, string $pdfPath, string $pdfName): array
    {
        if (function_exists('wum_is_demo') && wum_is_demo()) {
            return ['ok' => false, 'message' => 'Demo: E-Mail deaktiviert.'];
        }

        $email = trim((string) ($customer['email'] ?? ''));
        if ($email === '' || ! str_contains($email, '@')) {
            return ['ok' => false, 'message' => 'Keine gültige Kunden-E-Mail.'];
        }

        $recipientName = trim((string) ($customer['company'] ?? ''));
        if ($recipientName === '') {
            $recipientName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        }

        $number = (string) ($quote['quote_number'] ?? '');

        return $this->sendSimpleMail(
            $email,
            $recipientName,
            'Ihre Auftragsbestätigung zu Angebot Nr. ' . $number,
            '<p>Hallo ' . esc($customer['first_name'] ?? '') . ',</p>'
            . '<p>anbei erhalten Sie die Kopie Ihrer Auftragsbestätigung zu Angebot Nr. ' . esc($number) . '.</p>',
            $pdfPath,
            $pdfName
        );
    }

    private function sendSimpleMail(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $pdfPath,
        string $pdfName
    ): array {
        $mail = new PHPMailer(true);
        try {
            $mail->CharSet    = 'UTF-8';
            $mail->isSMTP();
            $mail->Host       = env('email.SMTPHost', '');
            $mail->SMTPAuth   = true;
            $mail->Username   = env('email.SMTPUser', '');
            $mail->Password   = env('email.SMTPPass', '');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) env('email.SMTPPort', 587);
            $mail->SMTPOptions = [
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            ];

            $fromEmail = env('email.fromEmail', 'info@wum.hamburg');
            $fromName  = env('email.fromName', 'Webseiten und mehr - Hamburg');
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName);
            $mail->addAttachment($pdfPath, $pdfName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = '<html><head><meta charset="UTF-8"></head><body>' . $htmlBody . $this->buildSignatureFooter() . '</body></html>';

            $logoPath = FCPATH . 'images' . DIRECTORY_SEPARATOR . 'logo-klein.jpg';
            if (is_file($logoPath)) {
                $mail->addEmbeddedImage($logoPath, 'firmenlogo', 'logo-klein.jpg');
            }

            $mail->send();

            return ['ok' => true, 'message' => 'E-Mail gesendet.'];
        } catch (Exception $e) {
            return ['ok' => false, 'message' => 'E-Mail konnte nicht gesendet werden: ' . $mail->ErrorInfo];
        }
    }

    private function buildSignatureFooter(): string
    {
        $wum = config('Wum');

        return '<p>Webseiten und mehr, Hamburg</p>
            <p>' . esc($wum->companyOwner) . '</p>
            <p>' . esc($wum->companyStreet) . '</p>
            <p>' . esc($wum->companyCity) . '</p>
            <p>Tel. +49177/33 11 590</p>
            <p>Fax: +4940 / 20 98 13 41</p>
            <p>Email: <a href="mailto:info@wum.hamburg">info@wum.hamburg</a></p>
            <br>
            <p><b>* Denken Sie vor dem Drucken dieser Nachricht an unsere Umwelt. *</b></p>
            <br>
            <div style="background-color: #e2ebef; border: 2px solid #666666; margin: 5px; padding: 15px; text-align: left;">
                <span style="color: #333333; font-size: 12px;">
                    <p>Diese E-Mail enthält vertrauliche und/oder rechtlich geschützte Informationen. Wenn Sie nicht der richtige Adressat sind oder diese E-Mail irrtümlich erhalten haben, informieren Sie bitte sofort den Absender und vernichten Sie diese Mail. Das unerlaubte Kopieren sowie die unbefugte Weitergabe dieser Mail ist nicht gestattet und kann ggf. Schadensersatzansprüche auslösen.</p>
                    <p><b>Informationen zur Datenerhebung gemäß Artikel 13 DSGVO</b></p>
                    <p>WUM – Webseiten und mehr erhebt Ihre Daten zum Zweck der Vertragsdurchführung, zur Erfüllung ihrer vertraglichen und vorvertraglichen Pflichten, sowie zur Direktwerbung.</p>
                    <p>Die Datenerhebung und Datenverarbeitung ist für die Durchführung des Vertrags erforderlich und beruht auf Artikel 6 Abs. 1 b) DSGVO. Eine Weitergabe der Daten an Dritte erfolgt ausschließlich zur Vertragserfüllung. Die Daten werden gelöscht, sobald sie für den Zweck ihrer Verarbeitung nicht mehr erforderlich sind.</p>
                    <p>Sie haben das Recht, der Verwendung Ihrer Daten zum Zweck der Direktwerbung jederzeit zu widersprechen. Zudem sind Sie berechtigt, Auskunft der bei uns über Sie gespeicherten Daten zu beantragen, sowie bei Unrichtigkeit der Daten die Berichtigung oder bei unzulässiger Datenspeicherung die Löschung der Daten zu fordern.</p>
                    <p>Ihnen steht des Weiteren ein Beschwerderecht bei der Aufsichtsbehörde zu.</p>
                </span>
            </div>';
    }
}
