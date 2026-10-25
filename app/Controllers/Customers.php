<?php

namespace App\Controllers;

use App\Models\CustomerModel;
use App\Services\DocumentNumberService;
use Config\Wum;

class Customers extends BaseController
{
    public function index()
    {
        $model = new CustomerModel();
        $split = $this->splitCustomerGroups($model->getGroupedByStatus());

        return view('Customers/index', $this->viewData([
            'moduleTitle'       => 'Debitoren',
            'activeCustomers'   => $split['active'],
            'inactiveCustomers' => $split['inactive'],
        ]));
    }

    /**
     * @param array<int|string, list<array<string, mixed>>> $groups
     * @return array{active: list<array<string, mixed>>, inactive: list<array<string, mixed>>}
     */
    private function splitCustomerGroups(array $groups): array
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
            return view('Customers/form', $this->viewData([
                'moduleTitle' => 'Neuer Kunde',
                'customer'    => [],
                'action'      => site_url('customers/store'),
            ]));
        }
        $model    = new CustomerModel();
        $customer = $model->find($id);
        if (!$customer) {
            return redirect()->to('/customers')->with('error', 'Kunde nicht gefunden.');
        }
        return view('Customers/form', $this->viewData([
            'moduleTitle' => 'Kunde bearbeiten',
            'customer'    => $customer,
            'action'      => site_url('customers/update/' . $id),
        ]));
    }

    public function store()
    {
        $model = new CustomerModel();
        $data  = $this->customerDataFromPost();
        $data['customer_number'] = $model->nextCustomerNumber();
        $data['status']          = Wum::STATUS_ACTIVE;
        $this->autofillInvoiceFolder($data);
        if (!$model->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }
        $this->ensureInvoiceFolder($data['invoice_folder'] ?? null);
        return redirect()->to('/customers')->with('success', 'Kunde angelegt.');
    }

    public function update($id)
    {
        $model    = new CustomerModel();
        $customer = $model->find($id);
        if (!$customer) {
            return redirect()->to('/customers')->with('error', 'Kunde nicht gefunden.');
        }
        $data   = $this->customerDataFromPost();
        $status = (int) $this->request->getPost('status');
        if ($status === Wum::STATUS_DELETED && (int) $customer['status'] === Wum::STATUS_DELETED) {
            if ($model->where('status !=', Wum::STATUS_DELETED)->countAllResults() < 1) {
                return redirect()->back()->with('error', 'Der letzte aktive Datensatz kann nicht gelöscht werden.');
            }
            $model->delete($id);
            return redirect()->to('/customers')->with('success', 'Kunde gelöscht.');
        }
        $data['status'] = $status;
        $this->autofillInvoiceFolder($data, $customer);
        if (!$model->update($id, $data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }
        $this->ensureInvoiceFolder($data['invoice_folder'] ?? null);
        return redirect()->to('/customers')->with('success', 'Kunde gespeichert.');
    }

    private function customerDataFromPost(): array
    {
        $data = [
            'company'     => $this->request->getPost('company'),
            'first_name'  => $this->request->getPost('first_name'),
            'last_name'   => $this->request->getPost('last_name'),
            'street'      => $this->request->getPost('street'),
            'postal_code' => (int) $this->request->getPost('postal_code'),
            'city'        => $this->request->getPost('city') ?: 'Hamburg',
            'phone'       => $this->request->getPost('phone'),
            'email'       => $this->request->getPost('email'),
            'note'        => $this->request->getPost('note'),
        ];

        if (! wum_is_demo()) {
            $folder = trim((string) $this->request->getPost('invoice_folder'));
            $data['invoice_folder'] = $folder !== '' ? $folder : null;
        }

        return $data;
    }

    /**
     * When invoice_folder is empty (non-demo), derive from customer data / disk.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed>|null $existing
     */
    private function autofillInvoiceFolder(array &$data, ?array $existing = null): void
    {
        if (wum_is_demo()) {
            return;
        }
        if (trim((string) ($data['invoice_folder'] ?? '')) !== '') {
            return;
        }

        $merged = array_merge($existing ?? [], $data);
        $folder = (new DocumentNumberService())->resolveInvoiceFolder($merged);
        if ($folder !== null && $folder !== '') {
            $data['invoice_folder'] = $folder;
        }
    }

    private function ensureInvoiceFolder(?string $folder): void
    {
        if (wum_is_demo() || $folder === null || $folder === '') {
            return;
        }
        wum_ensure_invoice_folder($folder);
    }
}
