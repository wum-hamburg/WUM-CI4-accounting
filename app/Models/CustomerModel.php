<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table            = 'customers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'customer_number', 'company', 'first_name', 'last_name', 'street',
        'postal_code', 'city', 'phone', 'email', 'invoice_folder', 'note', 'status', 'meta',
    ];

    protected $validationRules = [
        'invoice_folder' => 'permit_empty|alpha_dash|max_length[255]',
    ];

    protected $validationMessages = [
        'invoice_folder' => [
            'alpha_dash'  => 'Rechnungsordner darf nur Buchstaben, Zahlen, Unterstrich und Bindestrich enthalten.',
            'max_length'  => 'Rechnungsordner darf höchstens 255 Zeichen haben.',
        ],
    ];

    public function getGroupedByStatus(): array
    {
        $groups = [];
        foreach ([0, 1, 2] as $status) {
            $rows = $this->where('status', $status)
                ->orderBy('last_name', 'ASC')
                ->orderBy('first_name', 'ASC')
                ->findAll();
            if ($rows !== []) {
                $groups[$status] = $rows;
            }
        }
        return $groups;
    }

    public function findByCustomerNumber($number): ?array
    {
        return $this->where('customer_number', $number)->first();
    }

    public function nextCustomerNumber(): int
    {
        $row = $this->selectMax('customer_number')->first();
        $max = (int) ($row['customer_number'] ?? 0);
        return $max > 0 ? $max + 1 : 1;
    }
}
