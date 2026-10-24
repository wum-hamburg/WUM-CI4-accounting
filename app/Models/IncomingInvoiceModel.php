<?php

namespace App\Models;

use CodeIgniter\Model;

class IncomingInvoiceModel extends Model
{
    protected $table            = 'incoming_invoices';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'creditor_id', 'booking_year', 'invoice_number', 'invoice_date', 'amount_cents',
    ];

    protected $validationRules = [
        'creditor_id'    => 'required|is_natural_no_zero',
        'booking_year'   => 'required|is_natural_no_zero',
        'invoice_number' => 'required|max_length[100]',
        'invoice_date'   => 'required|valid_date',
        'amount_cents'   => 'required|integer',
    ];

    protected $validationMessages = [
        'creditor_id' => [
            'required' => 'Kreditor ist erforderlich.',
        ],
        'invoice_number' => [
            'required'   => 'Rechnungsnummer ist erforderlich.',
            'max_length' => 'Rechnungsnummer darf höchstens 100 Zeichen haben.',
        ],
        'invoice_date' => [
            'required'   => 'Rechnungsdatum ist erforderlich.',
            'valid_date' => 'Rechnungsdatum ist ungültig.',
        ],
        'amount_cents' => [
            'required' => 'Betrag ist erforderlich.',
        ],
    ];

    /**
     * @param 'date'|'company'|'amount' $sort
     * @param 'ASC'|'DESC'              $dateDir Only applied when $sort === 'date'; date secondary sort is always ASC.
     *
     * @return list<array<string, mixed>>
     */
    public function getByBookingYear(int $year, string $sort = 'date', string $dateDir = 'ASC'): array
    {
        $builder = $this->select('incoming_invoices.*, creditors.company_name, creditors.creditor_number')
            ->join('creditors', 'creditors.id = incoming_invoices.creditor_id', 'left')
            ->where('incoming_invoices.booking_year', $year);

        $dateDir = strtoupper($dateDir) === 'DESC' ? 'DESC' : 'ASC';

        if ($sort === 'company') {
            $builder->orderBy('creditors.company_name', 'ASC')
                ->orderBy('incoming_invoices.invoice_date', 'ASC')
                ->orderBy('incoming_invoices.id', 'ASC');
        } elseif ($sort === 'amount') {
            $builder->orderBy('incoming_invoices.amount_cents', 'ASC')
                ->orderBy('incoming_invoices.invoice_date', 'ASC')
                ->orderBy('incoming_invoices.id', 'ASC');
        } else {
            $builder->orderBy('incoming_invoices.invoice_date', $dateDir)
                ->orderBy('incoming_invoices.id', $dateDir);
        }

        return $builder->findAll();
    }

    public function findWithCreditor(int $id): ?array
    {
        $row = $this->select('incoming_invoices.*, creditors.company_name, creditors.creditor_number')
            ->join('creditors', 'creditors.id = incoming_invoices.creditor_id', 'left')
            ->where('incoming_invoices.id', $id)
            ->first();

        return $row ?: null;
    }
}
