<?php

namespace App\Services;

use CodeIgniter\Database\MigrationRunner;
use Config\Database;
use Config\Migrations;

/**
 * Reset demo database schema/data and clear demo PDF archives.
 */
class DemoResetService
{
    /** @var list<string> */
    private array $tables = [
        'quote_confirmations',
        'receipts',
        'invoices',
        'quotes',
        'incoming_invoices',
        'creditors',
        'text_blocks',
        'articles',
        'customers',
        'user',
    ];

    /**
     * @return array{ok: bool, message: string}
     */
    public function reset(): array
    {
        if (! $this->ensureDemoSchema()) {
            return ['ok' => false, 'message' => 'Demo-Migrationen fehlgeschlagen.'];
        }

        $db = Database::connect('demo', false);
        $schema = new SchemaUpdateService($db);
        $schema->ensureCreditorsAndIncomingInvoices();
        $schema->ensureQuoteConfirmationsTable();

        $db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($this->tables as $table) {
            if ($db->tableExists($table)) {
                $db->table($table)->truncate();
            }
        }
        $db->query('SET FOREIGN_KEY_CHECKS=1');

        $seeder = Database::seeder();
        $seeder->call('DemoDataSeeder');

        $this->clearDemoArchives();

        return ['ok' => true, 'message' => 'Demo wurde zurückgesetzt.'];
    }

    private function ensureDemoSchema(): bool
    {
        $runner = new MigrationRunner(config(Migrations::class), 'demo');
        $runner->clearCliMessages();
        $runner->setNamespace('App');

        return (bool) $runner->latest('demo');
    }

    private function clearDemoArchives(): void
    {
        $base = WRITEPATH . 'demo' . DIRECTORY_SEPARATOR . 'archives';
        foreach (['invoices', 'quotes', 'quote-confirmations'] as $type) {
            $dir = $base . DIRECTORY_SEPARATOR . $type;
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
                continue;
            }
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
}
