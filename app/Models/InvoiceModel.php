<?php

namespace App\Models;

use CodeIgniter\Model;

class InvoiceModel extends Model
{
    protected $table            = 'invoices';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'invoice_number', 'amount_cents', 'invoice_date', 'customer_number',
        'customer_last_name', 'status', 'filename', 'meta', 'source_quote_id',
    ];

    public function getRecent(int $limit = 5): array
    {
        return $this->orderBy('invoice_date', 'DESC')->orderBy('id', 'DESC')->findAll($limit);
    }

    public function findByNumber(string $number): ?array
    {
        return $this->where('invoice_number', $number)->first();
    }

    public function getLastInvoiceNumber(): ?string
    {
        // Lexicographic max of YYYYMMNNN matches chronological/sequential order.
        $row = $this->select('invoice_number')->orderBy('invoice_number', 'DESC')->first();
        return $row['invoice_number'] ?? null;
    }
}
