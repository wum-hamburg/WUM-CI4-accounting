<?php

namespace App\Commands;

use App\Services\DemoResetService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Ensure demo schema, truncate, re-seed; clear demo PDF archives.
 *
 * Cron example (hourly):
 *   0 * * * * cd /path/to/wum-ci4-accounting && php spark demo:reset
 *
 * Note: CI4 `migrate -g demo` only filters Migration::$DBGroup; it does not
 * switch the connection. This command migrates against the demo connection.
 */
class ResetDemo extends BaseCommand
{
    protected $group       = 'WUM';
    protected $name        = 'demo:reset';
    protected $description = 'Reset the demo database (wum-demo) and clear demo archives.';
    protected $usage       = 'demo:reset';

    public function run(array $params)
    {
        CLI::write('Resetting demo database…', 'yellow');

        $result = (new DemoResetService())->reset();

        if (! $result['ok']) {
            CLI::error($result['message']);
            return;
        }

        CLI::write($result['message'], 'green');
    }
}
