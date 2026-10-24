<?php

namespace App\Models;

use CodeIgniter\Model;

class ArticleModel extends Model
{
    protected $table            = 'articles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'article_number', 'title', 'price_cents', 'status', 'note', 'meta',
    ];

    public function findByArticleNumber(string $number): ?array
    {
        return $this->where('article_number', $number)->first();
    }

    public function getAllSorted(): array
    {
        return $this->orderBy('article_number', 'ASC')->orderBy('title', 'ASC')->findAll();
    }
}
