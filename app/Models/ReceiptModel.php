<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Wum;

class ReceiptModel extends Model
{
    protected $table            = 'receipts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'receipt_number', 'from_text', 'via_text', 'via2_text', 'to_text',
        'time_text', 'waiting_time', 'agent', 'persons', 'price',
        'receipt_date', 'vessel', 'signature_data',
    ];

    public function nextReceiptNumber(): string
    {
        $row = $this->select('receipt_number')
            ->orderBy('id', 'DESC')
            ->first();

        $max = 0;
        if ($row && preg_match('/(\d+)/', (string) ($row['receipt_number'] ?? ''), $m)) {
            $max = (int) $m[1];
        }

        return sprintf('%04d', $max + 1);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listRecent(?int $limit = null): array
    {
        $builder = $this->orderBy('id', 'DESC');
        if ($limit !== null) {
            $builder->limit($limit);
        }

        return $builder->findAll();
    }

    public function demoLimit(): int
    {
        return (int) (config(Wum::class)->demoDocumentLimit ?? 10);
    }
}
