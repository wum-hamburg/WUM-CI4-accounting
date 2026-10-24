<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Per-customer slug for shared invoices/{folder}/ PDFs.
 */
class AddCustomerInvoiceFolder extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('invoice_folder', 'customers')) {
            return;
        }

        $this->forge->addColumn('customers', [
            'invoice_folder' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'email',
            ],
        ]);
    }

    public function down()
    {
        if ($this->db->fieldExists('invoice_folder', 'customers')) {
            $this->forge->dropColumn('customers', 'invoice_folder');
        }
    }
}
