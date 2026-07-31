<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Convert receipt_date from VARCHAR (legacy "20__" year suffix) to DATETIME.
 * Safe no-op when table missing or column already DATETIME.
 */
class AlterReceiptsDateToDatetime extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('receipts') || ! $this->db->fieldExists('receipt_date', 'receipts')) {
            return;
        }

        $fields = $this->db->getFieldData('receipts');
        foreach ($fields as $field) {
            if ($field->name !== 'receipt_date') {
                continue;
            }
            $type = strtolower((string) ($field->type ?? ''));
            if (str_contains($type, 'date') || str_contains($type, 'time')) {
                return;
            }
        }

        // Best-effort convert short year values like "26" → 2026-01-01 00:00:00
        $this->db->query(
            "UPDATE receipts SET receipt_date = CONCAT('20', LPAD(TRIM(receipt_date), 2, '0'), '-01-01 00:00:00')
             WHERE receipt_date IS NOT NULL
               AND receipt_date REGEXP '^[0-9]{1,2}$'"
        );
        $this->db->query(
            "UPDATE receipts SET receipt_date = CONCAT(receipt_date, ' 00:00:00')
             WHERE receipt_date IS NOT NULL
               AND receipt_date REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'"
        );

        $this->forge->modifyColumn('receipts', [
            'receipt_date' => [
                'name'    => 'receipt_date',
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Y-m-d 00:00:00',
            ],
        ]);
    }

    public function down()
    {
        if (! $this->db->tableExists('receipts') || ! $this->db->fieldExists('receipt_date', 'receipts')) {
            return;
        }

        $this->forge->modifyColumn('receipts', [
            'receipt_date' => [
                'name'       => 'receipt_date',
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
        ]);
    }
}
