<?php

namespace App\Models;

use CodeIgniter\Model;

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

    public const DEMO_LIMIT = 10;

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
        return self::DEMO_LIMIT;
    }

    /**
     * Normalize calendar date (Y-m-d or d.m.Y) to DATETIME at midnight.
     */
    public static function normalizeDate(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d 00:00:00', (int) $m[1], (int) $m[2], (int) $m[3]);
        }

        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d 00:00:00', (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        $ts = strtotime($raw);
        if ($ts === false) {
            return null;
        }

        return date('Y-m-d 00:00:00', $ts);
    }

    /**
     * Normalize time to hh:mm.
     */
    public static function normalizeTime(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $raw, $m)) {
            $h = (int) $m[1];
            $i = (int) $m[2];
            if ($h >= 0 && $h <= 23 && $i >= 0 && $i <= 59) {
                return sprintf('%02d:%02d', $h, $i);
            }
        }

        return null;
    }

    public static function formatDateDe(?string $datetime): string
    {
        if ($datetime === null || $datetime === '') {
            return '';
        }
        $ts = strtotime($datetime);
        if ($ts === false) {
            return '';
        }

        return date('d.m.Y', $ts);
    }

    public static function dateInputValue(?string $datetime): string
    {
        if ($datetime === null || $datetime === '') {
            return '';
        }
        $ts = strtotime($datetime);
        if ($ts === false) {
            return '';
        }

        return date('Y-m-d', $ts);
    }
}
