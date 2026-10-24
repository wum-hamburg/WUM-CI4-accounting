<?php

namespace App\Models;

use CodeIgniter\Model;

class TextBlockModel extends Model
{
    protected $table            = 'text_blocks';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'text_key', 'content', 'status', 'note', 'meta',
    ];

    public function getByPrefix(string $prefix): array
    {
        return $this->like('text_key', $prefix, 'after')
            ->orderBy('text_key', 'ASC')
            ->findAll();
    }
}
