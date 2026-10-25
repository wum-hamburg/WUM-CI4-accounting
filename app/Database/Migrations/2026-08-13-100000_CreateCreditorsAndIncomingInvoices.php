<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creditors and incoming invoices (non-demo purchase invoices).
 */
class CreateCreditorsAndIncomingInvoices extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('creditors')) {
            $this->forge->addField([
                'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'company_name' => ['type' => 'VARCHAR', 'constraint' => 255],
                'status'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'note'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'meta'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_at'   => ['type' => 'DATETIME', 'null' => true],
                'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('company_name');
            $this->forge->createTable('creditors', true);
        }

        if (! $this->db->tableExists('incoming_invoices')) {
            $this->forge->addField([
                'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'creditor_id'    => ['type' => 'INT', 'unsigned' => true],
                'booking_year'   => ['type' => 'SMALLINT', 'unsigned' => true],
                'invoice_number' => ['type' => 'VARCHAR', 'constraint' => 100],
                'invoice_date'   => ['type' => 'DATE'],
                'amount_cents'   => ['type' => 'INT'],
                'created_at'     => ['type' => 'DATETIME', 'null' => true],
                'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('creditor_id');
            $this->forge->addKey('booking_year');
            $this->forge->addUniqueKey(['creditor_id', 'invoice_number', 'booking_year']);
            $this->forge->createTable('incoming_invoices', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('incoming_invoices', true);
        $this->forge->dropTable('creditors', true);
    }
}
