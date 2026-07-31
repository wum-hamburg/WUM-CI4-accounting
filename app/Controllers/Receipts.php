<?php

namespace App\Controllers;

use App\Models\ReceiptModel;

class Receipts extends BaseController
{
    private function guardHansen()
    {
        if (! session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        if (session()->get('username') !== 'hansen') {
            return redirect()->to('/dashboard')->with('error', 'Keine Berechtigung.');
        }

        return null;
    }

    private function viewPayload(array $extra = []): array
    {
        return array_merge([
            'menu'            => $this->getMenu(session()->get('rights') ?? null),
            'moduleTitleKey'  => $extra['moduleTitleKey'] ?? null,
        ], $extra);
    }

    public function index()
    {
        if ($denied = $this->guardHansen()) {
            return $denied;
        }

        $model = new ReceiptModel();

        return view('Receipts/index', $this->viewPayload([
            'moduleTitleKey' => 'quittungsblock',
            'receipts'       => $model->listRecent(),
            'nextNumber'     => $model->nextReceiptNumber(),
        ]));
    }

    public function create()
    {
        if ($denied = $this->guardHansen()) {
            return $denied;
        }

        $model = new ReceiptModel();
        $limit = $model->demoLimit();
        if ($model->countAllResults() >= $limit) {
            return redirect()->to('/quittungsblock')
                ->with('error', 'Demo: maximal ' . $limit . ' Quittungen erlaubt.');
        }

        return view('Receipts/form', $this->viewPayload([
            'moduleTitleKey' => 'quittungsblock',
            'receipt'        => [],
            'receiptNumber'  => $model->nextReceiptNumber(),
            'action'         => site_url('quittungsblock/store'),
            'readonly'       => false,
        ]));
    }

    public function store()
    {
        if ($denied = $this->guardHansen()) {
            return $denied;
        }

        $model = new ReceiptModel();
        $limit = $model->demoLimit();
        if ($model->countAllResults() >= $limit) {
            return redirect()->to('/quittungsblock')
                ->with('error', 'Demo: maximal ' . $limit . ' Quittungen erlaubt.');
        }

        $data = $this->receiptDataFromPost();
        if ($data['receipt_date'] === null && $this->request->getPost('receipt_date')) {
            return redirect()->back()->withInput()->with('error', 'Ungültiges Datum. Bitte dd.mm.yyyy wählen.');
        }
        if ($data['time_text'] === null && $this->request->getPost('time_text')) {
            return redirect()->back()->withInput()->with('error', 'Ungültige Uhrzeit. Bitte hh:mm eingeben.');
        }

        $data['receipt_number'] = $model->nextReceiptNumber();

        if (! $model->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        $id = (int) $model->getInsertID();

        return redirect()->to('/quittungsblock/' . $id)
            ->with('success', 'Quittung Nr. ' . $data['receipt_number'] . ' gespeichert.');
    }

    public function show($id)
    {
        if ($denied = $this->guardHansen()) {
            return $denied;
        }

        $model   = new ReceiptModel();
        $receipt = $model->find((int) $id);
        if (! $receipt) {
            return redirect()->to('/quittungsblock')->with('error', 'Quittung nicht gefunden.');
        }

        return view('Receipts/form', $this->viewPayload([
            'moduleTitleKey' => 'quittungsblock',
            'receipt'        => $receipt,
            'receiptNumber'  => $receipt['receipt_number'],
            'action'         => null,
            'readonly'       => true,
        ]));
    }

    private function receiptDataFromPost(): array
    {
        $signature = (string) $this->request->getPost('signature_data');
        if ($signature !== '' && ! str_starts_with($signature, 'data:image/')) {
            $signature = '';
        }

        return [
            'from_text'      => $this->request->getPost('from_text'),
            'via_text'       => $this->request->getPost('via_text'),
            'via2_text'      => $this->request->getPost('via2_text'),
            'to_text'        => $this->request->getPost('to_text'),
            'time_text'      => ReceiptModel::normalizeTime($this->request->getPost('time_text')),
            'waiting_time'   => $this->request->getPost('waiting_time'),
            'agent'          => $this->request->getPost('agent'),
            'persons'        => $this->request->getPost('persons'),
            'price'          => $this->request->getPost('price'),
            'receipt_date'   => ReceiptModel::normalizeDate($this->request->getPost('receipt_date')),
            'vessel'         => $this->request->getPost('vessel'),
            'signature_data' => $signature !== '' ? $signature : null,
        ];
    }
}
