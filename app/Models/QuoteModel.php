<?php

namespace App\Models;

use CodeIgniter\Model;

class QuoteModel extends Model
{
    protected $table            = 'quotes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'quote_number', 'amount_cents', 'quote_date', 'customer_number',
        'company_name', 'status', 'filename', 'meta', 'invoice_id',
    ];

    public function getRecent(int $limit = 5): array
    {
        return $this->orderBy('quote_date', 'DESC')->orderBy('id', 'DESC')->findAll($limit);
    }

    public function findByNumber(string $number): ?array
    {
        return $this->where('quote_number', $number)->first();
    }

    public function getLastQuoteNumber(): ?string
    {
        $row = $this->select('quote_number')->orderBy('id', 'DESC')->first();
        return $row['quote_number'] ?? null;
    }
}
