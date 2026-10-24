<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Customer order confirmations for quotes (Auftragsbestätigung).
 */
class CreateQuoteConfirmations extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('quote_confirmations')) {
            return;
        }

        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'quote_id'              => ['type' => 'INT', 'unsigned' => true],
            'token'                 => ['type' => 'VARCHAR', 'constraint' => 64],
            'notes'                 => ['type' => 'TEXT', 'null' => true],
            'confirmation_date'     => ['type' => 'DATE', 'null' => true],
            'signature_data'        => ['type' => 'MEDIUMTEXT', 'null' => true],
            'email_copy'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'privacy_consented_at'  => ['type' => 'DATETIME', 'null' => true],
            'pdf_filename'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'submitted_at'          => ['type' => 'DATETIME', 'null' => true],
            'declined_at'           => ['type' => 'DATETIME', 'null' => true],
            'created_at'            => ['type' => 'DATETIME', 'null' => true],
            'updated_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('quote_id');
        $this->forge->addUniqueKey('token');
        $this->forge->createTable('quote_confirmations', true);
    }

    public function down()
    {
        $this->forge->dropTable('quote_confirmations', true);
    }
}
