<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Unique creditor number (Kundennummer) and unique company name.
 */
class AddCreditorNumber extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('creditors')) {
            return;
        }

        if (! $this->db->fieldExists('creditor_number', 'creditors')) {
            $this->forge->addColumn('creditors', [
                'creditor_number' => [
                    'type' => 'BIGINT',
                    'null' => true,
                    'after' => 'id',
                ],
            ]);
        }

        $column = $this->db->query("SHOW COLUMNS FROM creditors LIKE 'creditor_number'")->getRowArray();
        $type   = strtolower((string) ($column['Type'] ?? ''));
        $isText = (bool) preg_match('/char|text/', $type);

        if (! $isText) {
            $rows = $this->db->table('creditors')->select('id, creditor_number')->get()->getResultArray();
            $used = [];
            foreach ($rows as $row) {
                $num = (int) ($row['creditor_number'] ?? 0);
                if ($num > 0) {
                    $used[$num] = true;
                }
            }
            $next = 1;
            foreach ($rows as $row) {
                if ((int) ($row['creditor_number'] ?? 0) > 0) {
                    continue;
                }
                while (isset($used[$next])) {
                    $next++;
                }
                $this->db->table('creditors')->where('id', $row['id'])->update(['creditor_number' => $next]);
                $used[$next] = true;
                $next++;
            }

            $this->db->query('ALTER TABLE creditors MODIFY creditor_number BIGINT NOT NULL');
        }

        if (! $this->indexExists('creditors', 'creditor_number')) {
            $this->db->query('ALTER TABLE creditors ADD UNIQUE KEY creditor_number (creditor_number)');
        }

        $dupes = $this->db->query(
            'SELECT company_name FROM creditors GROUP BY company_name HAVING COUNT(*) > 1'
        )->getResultArray();
        if ($dupes === [] && ! $this->indexExists('creditors', 'company_name_unique')) {
            $this->db->query('ALTER TABLE creditors ADD UNIQUE KEY company_name_unique (company_name)');
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('creditors')) {
            return;
        }
        if ($this->indexExists('creditors', 'company_name_unique')) {
            $this->db->query('ALTER TABLE creditors DROP INDEX company_name_unique');
        }
        if ($this->indexExists('creditors', 'creditor_number')) {
            $this->db->query('ALTER TABLE creditors DROP INDEX creditor_number');
        }
        if ($this->db->fieldExists('creditor_number', 'creditors')) {
            $this->forge->dropColumn('creditors', 'creditor_number');
        }
    }

    private function indexExists(string $table, string $name): bool
    {
        $rows = $this->db->query('SHOW INDEX FROM ' . $table . ' WHERE Key_name = ' . $this->db->escape($name))->getResultArray();

        return $rows !== [];
    }
}
