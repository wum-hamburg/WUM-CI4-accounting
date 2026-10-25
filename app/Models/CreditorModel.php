<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Wum;

class CreditorModel extends Model
{
    protected $table            = 'creditors';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'creditor_number', 'company_name', 'status', 'note', 'meta',
    ];

    protected $validationRules = [
        'company_name'    => 'required|max_length[255]',
        'creditor_number' => 'required|max_length[20]',
    ];

    protected $validationMessages = [
        'company_name' => [
            'required'   => 'Firmenname ist erforderlich.',
            'max_length' => 'Firmenname darf höchstens 255 Zeichen haben.',
        ],
        'creditor_number' => [
            'required'   => 'Kundennummer ist erforderlich.',
            'max_length' => 'Kundennummer darf höchstens 20 Zeichen haben.',
        ],
    ];

    public function findByCompanyName(string $name): ?array
    {
        $row = $this->where('company_name', $name)->first();

        return $row ?: null;
    }

    public function findByCreditorNumber(string $number): ?array
    {
        $row = $this->where('creditor_number', $number)->first();

        return $row ?: null;
    }

    /**
     * @return array<int, list<array<string, mixed>>>
     */
    public function getGroupedByStatus(): array
    {
        $groups = [];
        foreach ([0, 1, 2] as $status) {
            $rows = $this->where('status', $status)
                ->orderBy('company_name', 'ASC')
                ->findAll();
            if ($rows !== []) {
                $groups[$status] = $rows;
            }
        }
        return $groups;
    }

    /**
     * Active creditors A–Z for select dropdowns.
     *
     * @return list<array<string, mixed>>
     */
    public function getActiveSorted(): array
    {
        return $this->whereIn('status', [Wum::STATUS_ACTIVE, Wum::STATUS_ONE_TIME])
            ->orderBy('company_name', 'ASC')
            ->findAll();
    }
}
