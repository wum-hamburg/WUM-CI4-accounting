<?php

namespace App\Models;

use CodeIgniter\Model;

class QuoteConfirmationModel extends Model
{
    protected $table            = 'quote_confirmations';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'quote_id',
        'token',
        'notes',
        'confirmation_date',
        'signature_data',
        'email_copy',
        'privacy_consented_at',
        'pdf_filename',
        'submitted_at',
        'declined_at',
    ];

    public function findByToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        return $this->where('token', $token)->first();
    }

    public function findByQuoteId(int $quoteId): ?array
    {
        return $this->where('quote_id', $quoteId)->first();
    }

    public function isSubmitted(?array $confirmation): bool
    {
        return is_array($confirmation) && ! empty($confirmation['submitted_at']);
    }
}
