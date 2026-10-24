<?php

namespace App\Controllers;

use App\Models\CreditorModel;
use Config\Wum;

class Creditors extends BaseController
{
    public function index()
    {
        $model = new CreditorModel();
        $split = $this->splitCreditorGroups($model->getGroupedByStatus());

        return view('Creditors/index', $this->viewData([
            'moduleTitle'       => 'Kreditoren',
            'activeCreditors'   => $split['active'],
            'inactiveCreditors' => $split['inactive'],
        ]));
    }

    /**
     * @param array<int|string, list<array<string, mixed>>> $groups
     * @return array{active: list<array<string, mixed>>, inactive: list<array<string, mixed>>}
     */
    private function splitCreditorGroups(array $groups): array
    {
        $active   = [];
        $inactive = [];

        foreach ($groups as $status => $rows) {
            foreach ($rows as $row) {
                if ((int) $status === Wum::STATUS_DELETED) {
                    $inactive[] = $row;
                } else {
                    $active[] = $row + ['_status' => (int) $status];
                }
            }
        }

        return ['active' => $active, 'inactive' => $inactive];
    }

    public function edit($id)
    {
        if ((int) $id === 0) {
            return view('Creditors/form', $this->viewData([
                'moduleTitle' => 'Neuer Kreditor',
                'creditor'    => [],
                'action'      => site_url('creditors/store'),
            ]));
        }

        $model    = new CreditorModel();
        $creditor = $model->find($id);
        if (! $creditor) {
            return redirect()->to('/creditors')->with('error', 'Kreditor nicht gefunden.');
        }

        return view('Creditors/form', $this->viewData([
            'moduleTitle' => 'Kreditor bearbeiten',
            'creditor'    => $creditor,
            'action'      => site_url('creditors/update/' . $id),
        ]));
    }

    public function store()
    {
        $model = new CreditorModel();
        $data  = $this->creditorDataFromPost();
        $data['status'] = Wum::STATUS_ACTIVE;
        if ($data['creditor_number'] === '') {
            return redirect()->back()->withInput()->with('error', 'Kundennummer ist erforderlich.');
        }
        $existingSameName = $model->where('company_name', $data['company_name'])->findAll();
        if ($existingSameName !== []) {
            return redirect()->back()->withInput()->with('error', 'Dieser Firmenname ist bereits vergeben.');
        }
        if ($model->findByCreditorNumber($data['creditor_number'])) {
            return redirect()->back()->withInput()->with('error', 'Diese Kundennummer ist bereits vergeben.');
        }

        if (! $model->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        return redirect()->to('/creditors')->with('success', 'Kreditor angelegt.');
    }

    public function update($id)
    {
        $model    = new CreditorModel();
        $creditor = $model->find($id);
        if (! $creditor) {
            return redirect()->to('/creditors')->with('error', 'Kreditor nicht gefunden.');
        }

        $data   = $this->creditorDataFromPost();
        $status = (int) $this->request->getPost('status');
        if ($data['creditor_number'] === '') {
            return redirect()->back()->withInput()->with('error', 'Kundennummer ist erforderlich.');
        }
        $nameTaken = $model->where('company_name', $data['company_name'])->where('id !=', (int) $id)->first();
        if ($nameTaken) {
            return redirect()->back()->withInput()->with('error', 'Dieser Firmenname ist bereits vergeben.');
        }
        $numberTaken = $model->where('creditor_number', $data['creditor_number'])->where('id !=', (int) $id)->first();
        if ($numberTaken) {
            return redirect()->back()->withInput()->with('error', 'Diese Kundennummer ist bereits vergeben.');
        }

        if ($status === Wum::STATUS_DELETED && (int) $creditor['status'] === Wum::STATUS_DELETED) {
            if ($model->where('status !=', Wum::STATUS_DELETED)->countAllResults() < 1) {
                return redirect()->back()->with('error', 'Der letzte aktive Datensatz kann nicht gelöscht werden.');
            }
            $model->delete($id);
            return redirect()->to('/creditors')->with('success', 'Kreditor gelöscht.');
        }

        $data['status'] = $status;
        if (! $model->update($id, $data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        return redirect()->to('/creditors')->with('success', 'Kreditor gespeichert.');
    }

    private function creditorDataFromPost(): array
    {
        return [
            'company_name'    => trim((string) $this->request->getPost('company_name')),
            'creditor_number' => trim((string) $this->request->getPost('creditor_number')),
            'note'            => $this->request->getPost('note'),
        ];
    }
}
