<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Expand meta column to TEXT on all business tables.
 */
class ExpandMetaColumn extends Migration
{
    private const TABLES = ['customers', 'articles', 'text_blocks', 'invoices', 'quotes'];

    public function up()
    {
        foreach (self::TABLES as $table) {
            if (!$this->db->tableExists($table) || !$this->db->fieldExists('meta', $table)) {
                continue;
            }

            $this->forge->modifyColumn($table, [
                'meta' => ['type' => 'TEXT', 'null' => true],
            ]);
        }
    }

    public function down()
    {
        foreach (self::TABLES as $table) {
            if (!$this->db->tableExists($table) || !$this->db->fieldExists('meta', $table)) {
                continue;
            }

            $this->forge->modifyColumn($table, [
                'meta' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            ]);
        }
    }
}
