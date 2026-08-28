<?php

namespace App\Controllers;

use App\Models\CreditorModel;
use App\Models\IncomingInvoiceModel;
use Config\Wum;

class IncomingInvoices extends BaseController
{
    public const SESSION_BOOKING_YEAR = 'incoming_booking_year';

    public function index()
    {
        $current = (int) date('Y');
        $years   = [$current - 2, $current - 1, $current];

        $year = (int) ($this->request->getGet('year') ?? 0);
        if ($year === 0) {
            $sessionYear = (int) (session()->get(self::SESSION_BOOKING_YEAR) ?? 0);
            $year        = in_array($sessionYear, $years, true) ? $sessionYear : $current;
        }
        if (! in_array($year, $years, true)) {
            $year = $current;
        }

        $model = new IncomingInvoiceModel();
        $rows  = $model->getByBookingYear($year);

        return view('IncomingInvoices/index', $this->viewData([
            'moduleTitle' => 'Eingangsrechnungen',
            'year'        => $year,
            'years'       => $years,
            'rows'        => $rows,
        ]));
    }

    public function capture()
    {
        $year = $this->bookingYearFromSession();
        if ($year === null) {
            return view('IncomingInvoices/year', $this->viewData([
                'moduleTitle' => 'Erfassung Eingangsrechnungen',
                'years'       => $this->allowedBookingYears(),
            ]));
        }

        $creditors = (new CreditorModel())->getActiveSorted();

        return view('IncomingInvoices/select_creditor', $this->viewData([
            'moduleTitle'  => 'Erfassung Eingangsrechnungen',
            'bookingYear'  => $year,
            'creditors'    => $creditors,
        ]));
    }

    public function setYear()
    {
        $year  = (int) $this->request->getPost('booking_year');
        $years = $this->allowedBookingYears();
        if (! in_array($year, $years, true)) {
            return redirect()->to('/incoming-invoices/capture')
                ->with('error', 'Ungültiges Buchungsjahr.');
        }

        session()->set(self::SESSION_BOOKING_YEAR, $year);

        return redirect()->to('/incoming-invoices/capture');
    }

    public function selectCreditor()
    {
        $year = $this->requireBookingYear();
        if ($year instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $year;
        }

        $choice = (string) $this->request->getPost('creditor_id');
        $defaultDate = $this->defaultInvoiceDate($year);
        if ($choice === '' || $choice === 'NEU') {
            return view('IncomingInvoices/book_form', $this->viewData([
                'moduleTitle' => 'Erfassung Eingangsrechnungen',
                'bookingYear' => $year,
                'creditor'    => null,
                'isNew'       => true,
                'invoice'     => ['invoice_date' => $defaultDate],
                'action'      => site_url('incoming-invoices/book'),
            ]));
        }

        $creditor = (new CreditorModel())->find((int) $choice);
        if (! $creditor || (int) $creditor['status'] === Wum::STATUS_DELETED) {
            return redirect()->to('/incoming-invoices/capture')
                ->with('error', 'Kreditor nicht gefunden.');
        }

        return view('IncomingInvoices/book_form', $this->viewData([
            'moduleTitle'  => 'Erfassung Eingangsrechnungen',
            'bookingYear'  => $year,
            'creditor'     => $creditor,
            'isNew'        => false,
            'invoice'      => ['invoice_date' => $defaultDate],
            'action'       => site_url('incoming-invoices/book'),
        ]));
    }

    public function book()
    {
        $year = $this->requireBookingYear();
        if ($year instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $year;
        }

        $creditorModel = new CreditorModel();
        $creditorId    = (int) $this->request->getPost('creditor_id');
        $companyName   = trim((string) $this->request->getPost('company_name'));

        $isNew = $creditorId === 0;
        $creditorRow = null;

        if ($isNew) {
            $creditorNumber = trim((string) $this->request->getPost('creditor_number'));
            if ($companyName === '') {
                return $this->bookFormWithErrors($year, null, true, ['Firmenname ist erforderlich.']);
            }
            if ($creditorNumber === '') {
                return $this->bookFormWithErrors($year, null, true, ['Kundennummer ist erforderlich.']);
            }
            $existingSameName = $creditorModel->where('company_name', $companyName)->findAll();
            if ($existingSameName !== []) {
                return $this->bookFormWithErrors($year, null, true, ['Dieser Firmenname ist bereits vergeben. Bitte den bestehenden Kreditor auswählen.']);
            }
            if ($creditorModel->findByCreditorNumber($creditorNumber)) {
                return $this->bookFormWithErrors($year, null, true, ['Diese Kundennummer ist bereits vergeben.']);
            }
            $insertId = $creditorModel->insert([
                'creditor_number' => $creditorNumber,
                'company_name'    => $companyName,
                'status'          => Wum::STATUS_ACTIVE,
            ]);
            if (! $insertId) {
                return $this->bookFormWithErrors($year, null, true, $creditorModel->errors());
            }
            $creditorId  = (int) $insertId;
            $creditorRow = $creditorModel->find($creditorId);
        } else {
            $creditorRow = $creditorModel->find($creditorId);
            if (! $creditorRow || (int) $creditorRow['status'] === Wum::STATUS_DELETED) {
                return redirect()->to('/incoming-invoices/capture')
                    ->with('error', 'Kreditor nicht gefunden.');
            }
            $companyName = (string) $creditorRow['company_name'];
        }

        $invoiceDate = (string) $this->request->getPost('invoice_date');
        if (! $this->invoiceDateMatchesYear($invoiceDate, $year)) {
            return $this->bookFormWithErrors($year, $creditorRow, $isNew, ['Rechnungsdatum muss im Buchungsjahr ' . $year . ' liegen.']);
        }

        $data = [
            'creditor_id'    => $creditorId,
            'booking_year'   => $year,
            'invoice_number' => trim((string) $this->request->getPost('invoice_number')),
            'invoice_date'   => $invoiceDate,
            'amount_cents'   => $this->parseEuroToCents($this->request->getPost('amount_euro')),
        ];

        $invoiceModel = new IncomingInvoiceModel();
        $duplicate    = $invoiceModel
            ->where('creditor_id', $creditorId)
            ->where('invoice_number', $data['invoice_number'])
            ->where('booking_year', $year)
            ->first();
        if ($duplicate) {
            return $this->bookFormWithErrors(
                $year,
                $creditorRow,
                false,
                ['Diese Rechnungsnummer ist für den Kreditor im Buchungsjahr bereits vorhanden.']
            );
        }

        if (! $invoiceModel->insert($data)) {
            return $this->bookFormWithErrors($year, $creditorRow, false, $invoiceModel->errors());
        }

        $id = (int) $invoiceModel->getInsertID();

        return redirect()->to('/incoming-invoices/show/' . $id)
            ->with('success', 'Eingangsrechnung gebucht.');
    }

    public function show($id)
    {
        $row = (new IncomingInvoiceModel())->findWithCreditor((int) $id);
        if (! $row) {
            return redirect()->to('/incoming-invoices')->with('error', 'Eingangsrechnung nicht gefunden.');
        }

        return view('IncomingInvoices/show', $this->viewData([
            'moduleTitle' => 'Eingangsrechnung',
            'invoice'     => $row,
        ]));
    }

    public function edit($id)
    {
        $row = (new IncomingInvoiceModel())->findWithCreditor((int) $id);
        if (! $row) {
            return redirect()->to('/incoming-invoices')->with('error', 'Eingangsrechnung nicht gefunden.');
        }

        return view('IncomingInvoices/edit_form', $this->viewData([
            'moduleTitle' => 'Eingangsrechnung ändern',
            'invoice'     => $row,
            'action'      => site_url('incoming-invoices/update/' . $id),
        ]));
    }

    public function update($id)
    {
        $invoiceModel = new IncomingInvoiceModel();
        $row          = $invoiceModel->find((int) $id);
        if (! $row) {
            return redirect()->to('/incoming-invoices')->with('error', 'Eingangsrechnung nicht gefunden.');
        }

        $invoiceDate = (string) $this->request->getPost('invoice_date');
        $bookingYear = (int) $row['booking_year'];
        if (! $this->invoiceDateMatchesYear($invoiceDate, $bookingYear)) {
            return redirect()->back()->withInput()
                ->with('error', 'Rechnungsdatum muss im Buchungsjahr ' . $bookingYear . ' liegen.');
        }

        $data = [
            'invoice_number' => trim((string) $this->request->getPost('invoice_number')),
            'invoice_date'   => $invoiceDate,
            'amount_cents'   => $this->parseEuroToCents($this->request->getPost('amount_euro')),
        ];

        $duplicate = $invoiceModel
            ->where('creditor_id', (int) $row['creditor_id'])
            ->where('invoice_number', $data['invoice_number'])
            ->where('booking_year', (int) $row['booking_year'])
            ->where('id !=', (int) $id)
            ->first();
        if ($duplicate) {
            return redirect()->back()->withInput()
                ->with('error', 'Diese Rechnungsnummer ist für den Kreditor im Buchungsjahr bereits vorhanden.');
        }

        if (! $invoiceModel->update((int) $id, $data)) {
            return redirect()->back()->withInput()->with('errors', $invoiceModel->errors());
        }

        return redirect()->to('/incoming-invoices/show/' . $id)
            ->with('success', 'Eingangsrechnung gespeichert.');
    }

    public function delete($id)
    {
        $invoiceModel = new IncomingInvoiceModel();
        $row          = $invoiceModel->find((int) $id);
        if (! $row) {
            return redirect()->to('/incoming-invoices')->with('error', 'Eingangsrechnung nicht gefunden.');
        }
        $invoiceModel->delete((int) $id);

        return redirect()->to('/incoming-invoices')->with('success', 'Eingangsrechnung gelöscht.');
    }

    public function parseUpload()
    {
        if (! $this->request->is('post')) {
            return $this->response->setStatusCode(405)->setJSON(['ok' => false, 'warnings' => ['Methode nicht erlaubt.']]);
        }

        $file = $this->request->getFile('invoice_file');
        if ($file === null) {
            return $this->response->setJSON([
                'ok'             => false,
                'invoice_number' => null,
                'invoice_date'   => null,
                'amount_euro'    => null,
                'warnings'       => ['Keine Datei übermittelt.'],
                'source'         => 'none',
            ]);
        }

        return $this->response->setJSON((new IncomingInvoiceImportService())->parse($file));
    }

    /**
     * @param list<string>|array<string, string> $errors
     */
    private function bookFormWithErrors(int $year, ?array $creditor, bool $isNew, array $errors)
    {
        $isNewForm = $isNew || $creditor === null;

        return view('IncomingInvoices/book_form', $this->viewData([
            'moduleTitle' => 'Erfassung Eingangsrechnungen',
            'bookingYear' => $year,
            'creditor'    => $creditor,
            'isNew'           => $isNewForm,
            'creditorNumber'  => trim((string) $this->request->getPost('creditor_number')),
            'invoice'         => [
                'invoice_number' => (string) $this->request->getPost('invoice_number'),
                'invoice_date'   => (string) $this->request->getPost('invoice_date'),
                'amount_cents'   => $this->parseEuroToCents($this->request->getPost('amount_euro')),
            ],
            'action' => site_url('incoming-invoices/book'),
            'errors' => $errors,
        ]));
    }

    /**
     * @return list<int>
     */
    private function allowedBookingYears(): array
    {
        $y = (int) date('Y');

        return [$y - 2, $y - 1, $y];
    }

    private function bookingYearFromSession(): ?int
    {
        $year = (int) (session()->get(self::SESSION_BOOKING_YEAR) ?? 0);
        if ($year === 0 || ! in_array($year, $this->allowedBookingYears(), true)) {
            return null;
        }

        return $year;
    }

    /**
     * @return int|\CodeIgniter\HTTP\RedirectResponse
     */
    private function requireBookingYear()
    {
        $year = $this->bookingYearFromSession();
        if ($year === null) {
            return redirect()->to('/incoming-invoices/capture')
                ->with('error', 'Bitte zuerst das Buchungsjahr festlegen.');
        }

        return $year;
    }

    private function parseEuroToCents(mixed $value): int
    {
        $value = str_replace(['.', ' '], '', trim((string) $value));
        $value = str_replace(',', '.', $value);
        if ($value === '' || ! is_numeric($value)) {
            return 0;
        }

        return (int) round((float) $value * 100);
    }

    private function defaultInvoiceDate(int $bookingYear): string
    {
        $month = (int) date('n');
        $day   = (int) date('j');
        if (! checkdate($month, $day, $bookingYear)) {
            $day = 28;
        }

        return sprintf('%04d-%02d-%02d', $bookingYear, $month, $day);
    }

    private function invoiceDateMatchesYear(string $date, int $year): bool
    {
        if (! preg_match('/^(\d{4})-\d{2}-\d{2}$/', $date, $m)) {
            return false;
        }

        return (int) $m[1] === $year;
    }
}
