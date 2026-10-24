<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Document positions meta + quote/invoice linkage
 */
class AddDocumentPositions extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('quotes')) {
            $this->forge->modifyColumn('quotes', [
                'meta' => ['type' => 'TEXT', 'null' => true],
            ]);
            if (!$this->db->fieldExists('invoice_id', 'quotes')) {
                $this->forge->addColumn('quotes', [
                    'invoice_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'meta'],
                ]);
            }
        }

        if ($this->db->tableExists('invoices')) {
            $this->forge->modifyColumn('invoices', [
                'meta' => ['type' => 'TEXT', 'null' => true],
            ]);
            if (!$this->db->fieldExists('source_quote_id', 'invoices')) {
                $this->forge->addColumn('invoices', [
                    'source_quote_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'meta'],
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('quotes') && $this->db->fieldExists('invoice_id', 'quotes')) {
            $this->forge->dropColumn('quotes', 'invoice_id');
        }
        if ($this->db->tableExists('invoices') && $this->db->fieldExists('source_quote_id', 'invoices')) {
            $this->forge->dropColumn('invoices', 'source_quote_id');
        }
    }
}
