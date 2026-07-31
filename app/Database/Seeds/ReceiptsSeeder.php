<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * 10 sample Quittungsblock rows (maritime demo data).
 */
class ReceiptsSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $receiptSamples = [
            ['Port of Hamburg', 'Köhlbrand Bridge', 'Container Terminal Burchardkai', '08:30', '0.5', 'Hansen', '2', '85,00', '2026-07-26 00:00:00', 'MV Nordlicht'],
            ['Fischmarkt', 'Landungsbrücken', 'Cruise Terminal Altona', '09:15', '1', 'Meyer', '4', '120,00', '2026-07-25 00:00:00', 'MS Elbe Queen'],
            ['Blankenese', 'Övelgönne', 'St. Pauli Landungsbrücken', '10:00', '0', 'Hansen', '1', '55,00', '2026-07-24 00:00:00', 'Tug Atlas'],
            ['Waltershof', 'Neuhof', 'Altenwerder', '07:45', '1.5', 'Schulz', '3', '150,00', '2026-07-23 00:00:00', 'MV Hansa Star'],
            ['HafenCity', 'Speicherstadt', 'Überseebrücke', '14:20', '0.25', 'Hansen', '2', '70,00', '2026-07-22 00:00:00', 'Yacht Windrose'],
            ['Finkenwerder', 'Teufelsbrück', 'Neumühlen', '11:10', '2', 'Becker', '6', '210,00', '2026-07-21 00:00:00', 'MS Harbor Pilot'],
            ['Ballinkai', 'Steinwerder', 'Veddel', '16:40', '0.75', 'Hansen', '1', '95,00', '2026-07-20 00:00:00', 'MV Baltic Wind'],
            ['Billwerder Bucht', 'Rothenburgsort', 'Grasbrook', '06:50', '1', 'Peters', '5', '175,00', '2026-07-19 00:00:00', 'Tug Hermes'],
            ['Bubendey-Ufer', 'Neuhof', 'Köhlfleethafen', '13:05', '0.5', 'Hansen', '2', '110,00', '2026-07-18 00:00:00', 'MV Seamen One'],
            ['Landungsbrücken', 'Überseebrücke', 'Cruise Center HafenCity', '18:30', '0', 'Hansen', '8', '240,00', '2026-07-17 00:00:00', 'MS Hansa Spirit'],
        ];

        foreach ($receiptSamples as $i => $r) {
            $exists = $this->db->table('receipts')
                ->where('receipt_number', sprintf('%04d', $i + 1))
                ->countAllResults();
            if ($exists > 0) {
                continue;
            }

            $this->db->table('receipts')->insert([
                'receipt_number' => sprintf('%04d', $i + 1),
                'from_text'      => $r[0],
                'via_text'       => $r[1],
                'via2_text'      => null,
                'to_text'        => $r[2],
                'time_text'      => $r[3],
                'waiting_time'   => $r[4],
                'agent'          => $r[5],
                'persons'        => $r[6],
                'price'          => $r[7],
                'receipt_date'   => $r[8],
                'vessel'         => $r[9],
                'signature_data' => null,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }
    }
}
