<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Config\Wum;
use App\Services\SchemaUpdateService;

/**
 * Sample data for the shared demo database
 * (5 master-data rows; 10 quotes/invoices; 2 order confirmations;
 * 5 creditors; 20 incoming invoices for 2026).
 */
class DemoDataSeeder extends Seeder
{
    protected $DBGroup = 'demo';

    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $customers = [
            [10001, 'Muster GmbH', 'Anna', 'Muster', 'Hauptstr. 1', 20095, 'Hamburg', '040-111111', 'anna@muster-demo.de', 'Stammkunde'],
            [10002, 'Beispiel AG', 'Bernd', 'Beispiel', 'Nebenweg 2', 20144, 'Hamburg', '040-222222', 'bernd@beispiel-demo.de', null],
            [10003, null, 'Carla', 'Demo', 'Demoallee 3', 22765, 'Hamburg', '040-333333', 'carla@demo.local', 'Einmalkunde'],
            [10004, 'WebTest KG', 'Dirk', 'Tester', 'Hafenstr. 4', 20457, 'Hamburg', '040-444444', 'dirk@webtest-demo.de', null],
            [10005, 'Nord Soft', 'Eva', 'Nord', 'Elbchaussee 5', 22605, 'Hamburg', '040-555555', 'eva@nordsoft-demo.de', 'VIP'],
        ];

        foreach ($customers as $i => $c) {
            $this->db->table('customers')->insert([
                'customer_number' => $c[0],
                'company'         => $c[1],
                'first_name'      => $c[2],
                'last_name'       => $c[3],
                'street'          => $c[4],
                'postal_code'     => $c[5],
                'city'            => $c[6],
                'phone'           => $c[7],
                'email'           => $c[8],
                'note'            => $c[9],
                'status'          => $i === 2 ? Wum::STATUS_ONE_TIME : Wum::STATUS_ACTIVE,
                'meta'            => null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }

        $articles = [
            ['A-100', 'Webseite Basis', 89000, 'Responsive Startseite'],
            ['A-200', 'Webseite Premium', 149000, 'Mit CMS'],
            ['A-300', 'SEO Paket', 45000, 'Onpage Optimierung'],
            ['A-400', 'Hosting Jahr', 12000, 'inkl. SSL'],
            ['A-500', 'Support Stunde', 8500, 'Fernwartung'],
        ];

        foreach ($articles as $a) {
            $this->db->table('articles')->insert([
                'article_number' => $a[0],
                'title'          => $a[1],
                'price_cents'    => $a[2],
                'status'         => Wum::STATUS_ACTIVE,
                'note'           => $a[3],
                'meta'           => null,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }

        $texts = [
            ['T-INTRO', 'vielen Dank für Ihre Anfrage. Gerne unterbreiten wir Ihnen folgendes Angebot:'],
            ['T-FOOTER', 'Zahlungsziel: 14 Tage netto. Es gelten unsere AGB.'],
            ['T-HEADER', 'Angebot / Auftragsbestätigung'],
            ['T-THANKS', 'Vielen Dank für Ihren Auftrag. Die Umsetzung erfolgt nach Freigabe.'],
            ['T-REMIND', 'Freundliche Zahlungserinnerung zu unserer Rechnung.'],
        ];

        foreach ($texts as $t) {
            $this->db->table('text_blocks')->insert([
                'text_key'   => $t[0],
                'content'    => $t[1],
                'status'     => Wum::STATUS_ACTIVE,
                'note'       => null,
                'meta'       => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        (new SchemaUpdateService($this->db))->ensureDemoFantasyAccountingData();

        $docLimit = (int) (config(Wum::class)->demoDocumentLimit ?? 10);
        $customerCount = count($customers);

        for ($i = 1; $i <= $docLimit; $i++) {
            $cIdx  = ($i - 1) % $customerCount;
            $price = 50000 + ($i * 10000);
            $meta  = $this->demoMeta('Demo-Position ' . $i, $price, true);

            $this->db->table('quotes')->insert([
                'quote_number'    => sprintf('D-A-%04d', $i),
                'amount_cents'    => $price,
                'quote_date'      => date('Y-m-d', strtotime('-' . $i . ' days')),
                'customer_number' => (string) $customers[$cIdx][0],
                'company_name'    => $customers[$cIdx][1] ?? ($customers[$cIdx][2] . ' ' . $customers[$cIdx][3]),
                'status'          => $i <= 2 ? Wum::QUOTE_OPEN : ($i === 3 ? Wum::QUOTE_ORDERED : Wum::QUOTE_DECLINED),
                'filename'        => null,
                'meta'            => $meta,
                'invoice_id'      => null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }

        for ($i = 1; $i <= $docLimit; $i++) {
            $cIdx  = ($i - 1) % $customerCount;
            $price = 60000 + ($i * 8000);
            $meta  = $this->demoMeta('Demo-Leistung ' . $i, $price, true);

            $this->db->table('invoices')->insert([
                'invoice_number'     => sprintf('D-R-%04d', $i),
                'amount_cents'       => $price,
                'invoice_date'       => date('Y-m-d', strtotime('-' . ($i + 2) . ' days')),
                'customer_number'    => (string) $customers[$cIdx][0],
                'customer_last_name' => $customers[$cIdx][3],
                'status'             => $i <= 3 ? Wum::INVOICE_OPEN : Wum::INVOICE_PAID,
                'filename'           => null,
                'meta'               => $meta,
                'source_quote_id'    => null,
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);
        }

        // Fixed tokens for the two open demo quotes (D-A-0001, D-A-0002) — stable across resets.
        $openQuotes = $this->db->table('quotes')
            ->select('id, quote_number')
            ->whereIn('quote_number', ['D-A-0001', 'D-A-0002'])
            ->orderBy('quote_number', 'ASC')
            ->get()
            ->getResultArray();

        $demoTokens = [
            'D-A-0001' => 'a1b2c3d4e5f6789012345678abcdef01demo0001confirmpermanentlink01aa',
            'D-A-0002' => 'a1b2c3d4e5f6789012345678abcdef02demo0002confirmpermanentlink02aa',
        ];

        foreach ($openQuotes as $quote) {
            $number = (string) ($quote['quote_number'] ?? '');
            $token  = $demoTokens[$number] ?? null;
            if ($token === null) {
                continue;
            }
            $this->db->table('quote_confirmations')->insert([
                'quote_id'             => (int) $quote['id'],
                'token'                => $token,
                'notes'                => null,
                'confirmation_date'    => null,
                'signature_data'       => null,
                'email_copy'           => 0,
                'privacy_consented_at' => null,
                'pdf_filename'         => null,
                'submitted_at'         => null,
                'declined_at'          => null,
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);
        }
    }

    private function demoMeta(string $title, int $priceCents, bool $emailSent): string
    {
        return json_encode([
            'positions' => [[
                'type'            => 'custom',
                'title'           => $title,
                'qty'             => 1,
                'price_cents'     => $priceCents,
                'unit'            => 'psch.',
                'position_number' => '1',
                'position_source' => 'quote',
            ]],
            'texts' => [
                'intro_header' => 'Demo-Dokument',
                'intro_items'  => ['Beispieltext für die Demo-Version.'],
                'footer'       => ['Dies ist eine Demo – Daten werden stündlich zurückgesetzt.'],
            ],
            'email_sent' => $emailSent,
        ], JSON_UNESCAPED_UNICODE);
    }
}
