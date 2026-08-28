<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * WUM business constants / WUM-Geschäftskonstanten
 */
class Wum extends BaseConfig
{
    public const STATUS_DELETED  = 0;
    public const STATUS_ACTIVE   = 1;
    public const STATUS_ONE_TIME = 2;

    public const QUOTE_OPEN     = 1;
    public const QUOTE_ORDERED  = 2;
    public const QUOTE_DECLINED = 3;

    public const INVOICE_OPEN       = 1;
    public const INVOICE_PAID       = 2;
    public const INVOICE_CORRECTION = 9;

    public array $quoteStatusLabels = [
        self::QUOTE_OPEN     => 'Offen',
        self::QUOTE_ORDERED  => 'Auftrag',
        self::QUOTE_DECLINED => 'Kein Auftrag',
    ];

    public array $invoiceStatusLabels = [
        self::INVOICE_OPEN       => 'Offen',
        self::INVOICE_PAID       => 'Bezahlt',
        self::INVOICE_CORRECTION => 'Korrekturrechnung',
    ];

    public array $customerStatusLabels = [
        self::STATUS_ACTIVE   => 'Aktive Kunden',
        self::STATUS_ONE_TIME => 'Einmalkunden',
    ];

    public array $articleStatusLabels = [
        self::STATUS_ACTIVE   => 'Aktive Artikel',
        self::STATUS_ONE_TIME => 'Einmalartikel',
    ];

    public array $textBlockStatusLabels = [
        self::STATUS_ACTIVE => 'Aktive Texte',
    ];

    public array $menuLabels = [
        'login'              => 'Login',
        'dashboard'          => 'Startseite',
        'master_data'        => 'Stammdaten',
        'customers'          => 'Debitoren',
        'creditors'          => 'Kreditoren',
        'accounting'         => 'Buchhaltung',
        'incoming_invoices'  => 'Eingangsrechnungen',
        'profit_loss'        => 'GuV',
        'update'             => 'Update',
        'articles'           => 'Artikel',
        'documents'          => 'Dokument erstellen',
        'text_blocks'        => 'Texte',
        'receipts'           => 'Quittungsblock',
        'backup'             => 'Datensicherung',
        'restore'            => 'Wiederherstellung',
        'user_management'    => 'Benutzerverwaltung',
        'profile'            => 'Profileinstellung',
        'logout'             => 'Abmelden',
    ];

    public array $menuIcons = [
        'dashboard'         => 'fa-solid fa-house',
        'master_data'       => 'fa-solid fa-folder-open',
        'customers'         => 'fa-solid fa-users',
        'creditors'         => 'fa-solid fa-building',
        'accounting'        => 'fa-solid fa-calculator',
        'incoming_invoices' => 'fa-solid fa-file-invoice',
        'profit_loss'       => 'fa-solid fa-scale-balanced',
        'update'            => 'fa-solid fa-rotate',
        'articles'          => 'fa-solid fa-database',
        'documents'         => 'fa-solid fa-file-lines',
        'text_blocks'       => 'fa-solid fa-book-skull',
        'receipts'          => 'fa-solid fa-receipt',
        'backup'            => 'fa-solid fa-folder-tree',
        'restore'           => 'fa-solid fa-arrow-left',
        'user_management'   => 'fas fa-users-cog',
        'profile'           => 'fas fa-person',
        'logout'            => 'fa-solid fa-door-open',
    ];

    /** Max quotes / invoices shown and bookable in demo mode */
    public int $demoDocumentLimit = 10;

    /** Username for /start auto-login (must exist in default DB with rights=demo) */
    public string $demoUsername = 'demo';

    /** Letterhead / company data for PDF generation */
    public string $companyName = 'Webseiten und mehr - Hamburg';
    public string $companyOwner = 'Inhaber: Marko Jorissen';
    public string $companyStreet = 'Börnestr. 54';
    public string $companyCity = '22089 Hamburg';
    public string $companyPhone = 'Telefon: 0177 / 33 11 590';
    public string $companyFax = 'Fax: 040 / 20981341';
    public string $companyEmail = 'E-Mail: info@wum-hamburg.de';
    public string $companyTaxNumber = 'Steuernummer: 43/109/01429';
    public string $companyBankLine = 'Steuernummer: 43/109/01429 | Postbank: IBAN DE52 4401 0046 0977 3184 68 | BIC: PBNKDEFFXXX';
    public string $companyIban = 'DE52440100460977318468';
    public string $companyBic = 'PBNKDEFFXXX';
    public string $companyEmailPlain = 'info@wum-hamburg.de';
    public string $kleinunternehmerNote = 'Kein Ausweis von Umsatzsteuer, da Kleinunternehmer gemäß § 19 UStG';
    public string $logoPath = 'images/logo-office.png';

    /**
     * Unused for staff UI (PDFs are served via files/view while logged in).
     * Kept empty so env leftovers never build public invoice links.
     * Do not set this to a Hetzner control-panel host.
     */
    public string $invoicesPublicBaseUrl = '';

    /** Hetzner Storage Box (online backup upload) — set via .env */
    public string $hetznerHost = '';
    public string $hetznerUser = '';
    public string $hetznerPassword = '';
    public string $hetznerRemoteDir = 'Wum';

    public function __construct()
    {
        parent::__construct();
        // Staff always uses files/view; ignore env so public invoice URLs stay disabled.
        $this->invoicesPublicBaseUrl = '';
        $this->hetznerHost           = (string) env('wum.hetznerHost', $this->hetznerHost);
        $this->hetznerUser           = (string) env('wum.hetznerUser', $this->hetznerUser);
        $this->hetznerPassword       = (string) env('wum.hetznerPassword', $this->hetznerPassword);
        $this->hetznerRemoteDir      = (string) env('wum.hetznerRemoteDir', $this->hetznerRemoteDir);
        $this->companyIban           = (string) env('wum.companyIban', $this->companyIban);
        $this->companyBic            = (string) env('wum.companyBic', $this->companyBic);
        $this->companyEmailPlain     = (string) env('wum.companyEmailPlain', $this->companyEmailPlain);
        $this->kleinunternehmerNote  = (string) env('wum.kleinunternehmerNote', $this->kleinunternehmerNote);
    }
}
