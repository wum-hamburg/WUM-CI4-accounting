<?php

namespace App\Commands;

use App\Services\BackupService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Weekly database-only backup with upload to Hetzner (Wum/).
 *
 * Cron example (weekly, Sunday 03:00):
 *   0 3 * * 0 cd /path/to/wum-ci4-accounting && php spark backup:weekly
 */
class WeeklyBackup extends BaseCommand
{
    protected $group       = 'WUM';
    protected $name        = 'backup:weekly';
    protected $description = 'Create a DB-only backup ZIP and upload it to Hetzner (Wum/).';
    protected $usage       = 'backup:weekly';

    public function run(array $params)
    {
        $backup = new BackupService();
        $result = $backup->createJob('Wöchentliche DB-Sicherung', [
            'includePdfs'   => false,
            'uploadHetzner' => true,
        ]);

        if (empty($result['ok'])) {
            CLI::error($result['message'] ?? 'Backup-Job konnte nicht gestartet werden.');
            return EXIT_ERROR;
        }

        CLI::write('Job ' . $result['job_id'] . ' gestartet …', 'yellow');
        $done = $backup->runToCompletion($result['job_id']);

        if (empty($done['ok'])) {
            CLI::error($done['error'] !== '' ? $done['error'] : ($done['message'] ?? 'Backup fehlgeschlagen.'));
            return EXIT_ERROR;
        }

        CLI::write($done['message'] ?? 'Backup abgeschlossen.', 'green');
        return EXIT_SUCCESS;
    }
}
