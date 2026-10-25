<?php

namespace App\Database\Migrations;

use App\Services\SchemaUpdateService;
use CodeIgniter\Database\Migration;

/**
 * Shrink creditor_number to VARCHAR(20); also covers leftover INTEGER columns.
 */
class CreditorNumberVarchar20 extends Migration
{
    public function up()
    {
        $service = new SchemaUpdateService($this->db);
        if ($service->creditorNumberNeedsVarchar20()) {
            $service->applyCreditorNumberVarchar20();
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('creditors') || ! $this->db->fieldExists('creditor_number', 'creditors')) {
            return;
        }

        $this->db->query('ALTER TABLE creditors MODIFY creditor_number VARCHAR(100) NOT NULL');
    }
}
