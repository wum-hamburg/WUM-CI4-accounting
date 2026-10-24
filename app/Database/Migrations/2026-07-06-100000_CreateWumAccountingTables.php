<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * English schema for WUM accounting modules.
 * Schema für WUM-Buchhaltungsmodule (englische Feldnamen).
 */
class CreateWumAccountingTables extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('user')) {
            $this->forge->addField([
                'id'         => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'auto_increment' => true],
                'username'   => ['type' => 'VARCHAR', 'constraint' => 255],
                'password'   => ['type' => 'VARCHAR', 'constraint' => 255],
                'first_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
                'last_name'  => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
                'rights'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'registered'],
                'meta'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('username');
            $this->forge->createTable('user', true);
        }

        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'customer_number' => ['type' => 'BIGINT', 'null' => true],
            'company'         => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'first_name'      => ['type' => 'VARCHAR', 'constraint' => 50],
            'last_name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'street'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'postal_code'     => ['type' => 'INT', 'constraint' => 11],
            'city'            => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'Hamburg'],
            'phone'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'email'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'note'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'meta'            => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('customer_number');
        $this->forge->createTable('customers', true);

        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'article_number' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'title'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'price_cents'    => ['type' => 'MEDIUMINT', 'constraint' => 8],
            'status'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'note'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'meta'           => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('article_number');
        $this->forge->createTable('articles', true);

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'text_key'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'content'    => ['type' => 'TEXT'],
            'status'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'note'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'meta'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('text_key');
        $this->forge->createTable('text_blocks', true);

        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'invoice_number'     => ['type' => 'VARCHAR', 'constraint' => 20],
            'amount_cents'       => ['type' => 'MEDIUMINT', 'constraint' => 8],
            'invoice_date'       => ['type' => 'DATE'],
            'customer_number'    => ['type' => 'VARCHAR', 'constraint' => 12],
            'customer_last_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'status'             => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'filename'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'meta'               => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('invoice_number');
        $this->forge->createTable('invoices', true);

        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'quote_number'    => ['type' => 'VARCHAR', 'constraint' => 20],
            'amount_cents'    => ['type' => 'MEDIUMINT', 'constraint' => 8],
            'quote_date'      => ['type' => 'DATE'],
            'customer_number' => ['type' => 'VARCHAR', 'constraint' => 12],
            'company_name'    => ['type' => 'VARCHAR', 'constraint' => 50],
            'status'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'filename'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'meta'            => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('quote_number');
        $this->forge->createTable('quotes', true);
    }

    public function down()
    {
        $this->forge->dropTable('quotes', true);
        $this->forge->dropTable('invoices', true);
        $this->forge->dropTable('text_blocks', true);
        $this->forge->dropTable('articles', true);
        $this->forge->dropTable('customers', true);
    }
}
