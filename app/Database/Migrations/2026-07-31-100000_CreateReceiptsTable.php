<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Quittungsblock / receipt pad for Hermann Hansen Seamen-Service demo.
 */
class CreateReceiptsTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('receipts')) {
            return;
        }

        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'receipt_number' => ['type' => 'VARCHAR', 'constraint' => 10],
            'from_text'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'via_text'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'via2_text'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'to_text'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'time_text'      => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true, 'comment' => 'hh:mm'],
            'waiting_time'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'agent'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'persons'        => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'price'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'receipt_date'   => ['type' => 'DATETIME', 'null' => true, 'comment' => 'Y-m-d 00:00:00'],
            'vessel'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'signature_data' => ['type' => 'MEDIUMTEXT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('receipt_number');
        $this->forge->createTable('receipts', true);
    }

    public function down()
    {
        $this->forge->dropTable('receipts', true);
    }
}
