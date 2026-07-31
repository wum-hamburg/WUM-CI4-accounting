<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Truncate and re-seed Quittungsblock demo data.
 *
 *   php spark receipts:reset
 */
class ResetReceipts extends BaseCommand
{
    protected $group       = 'WUM';
    protected $name        = 'receipts:reset';
    protected $description = 'Reset receipts table and seed 10 demo Quittungen.';
    protected $usage       = 'receipts:reset';

    public function run(array $params)
    {
        $db = Database::connect();

        if (! $db->tableExists('receipts')) {
            CLI::error('Table receipts missing. Run: php spark migrate');
            return;
        }

        $db->table('receipts')->truncate();
        CLI::write('truncated: receipts', 'yellow');

        $seeder = Database::seeder();
        $seeder->call('ReceiptsSeeder');
        CLI::write('ReceiptsSeeder applied (10 rows).', 'green');
    }
}
