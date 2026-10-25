<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Map legacy rights codes to CI4 roles and ensure demo user exists.
 * Legacy: tz67→demo, fp23→superadmin, we89→registered
 */
class MigrateLegacyRightsAndDemoUser extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('user')) {
            return;
        }

        $this->db->table('user')->where('rights', 'fp23')->set(['rights' => 'superadmin'])->update();
        $this->db->table('user')->where('rights', 'tz67')->set(['rights' => 'demo'])->update();
        $this->db->table('user')->where('rights', 'we89')->set(['rights' => 'registered'])->update();

        $existing = $this->db->table('user')->where('username', 'demo')->get()->getRowArray();
        if ($existing !== null) {
            if (($existing['rights'] ?? '') !== 'demo') {
                $this->db->table('user')->where('username', 'demo')->set(['rights' => 'demo'])->update();
            }

            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('user')->insert([
            'username'   => 'demo',
            'password'   => password_hash('demo', PASSWORD_DEFAULT),
            'first_name' => 'Demo',
            'last_name'  => 'Benutzer',
            'rights'     => 'demo',
            'meta'       => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down()
    {
        // Do not revert role renames or delete the demo user automatically.
    }
}
