<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creditor number is typed (supplier's customer number), not sequential.
 */
class CreditorNumberAsText extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('creditors') || ! $this->db->fieldExists('creditor_number', 'creditors')) {
            return;
        }

        $this->db->query('ALTER TABLE creditors MODIFY creditor_number VARCHAR(20) NOT NULL');
    }

    public function down()
    {
        if (! $this->db->tableExists('creditors') || ! $this->db->fieldExists('creditor_number', 'creditors')) {
            return;
        }

        $this->db->query('ALTER TABLE creditors MODIFY creditor_number BIGINT NOT NULL');
    }
}
