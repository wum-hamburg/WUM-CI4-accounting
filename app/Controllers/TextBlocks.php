<?php

namespace App\Controllers;

use App\Models\TextBlockModel;
use Config\Wum;

class TextBlocks extends BaseController
{
    public function index()
    {
        $model = new TextBlockModel();
        $split = $this->splitByStatus($model->orderBy('text_key', 'ASC')->findAll());

        return view('TextBlocks/index', $this->viewData([
            'moduleTitle'    => 'Texte',
            'activeBlocks'   => $split['active'],
            'inactiveBlocks' => $split['inactive'],
        ]));
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return array{active: list<array<string, mixed>>, inactive: list<array<string, mixed>>}
     */
    private function splitByStatus(array $items): array
    {
        $active   = [];
        $inactive = [];

        foreach ($items as $item) {
            if ((int) ($item['status'] ?? Wum::STATUS_ACTIVE) === Wum::STATUS_DELETED) {
                $inactive[] = $item;
            } else {
                $active[] = $item;
            }
        }

        return ['active' => $active, 'inactive' => $inactive];
    }

    public function edit($id)
    {
        if ((int) $id === 0) {
            return view('TextBlocks/form', $this->viewData([
                'moduleTitle' => 'Neuer Text',
                'textBlock'   => [],
                'action'      => site_url('text-blocks/store'),
            ]));
        }
        $model = new TextBlockModel();
        $block = $model->find($id);
        if (!$block) {
            return redirect()->to('/text-blocks')->with('error', 'Text nicht gefunden.');
        }
        return view('TextBlocks/form', $this->viewData([
            'moduleTitle' => 'Text bearbeiten',
            'textBlock'   => $block,
            'action'      => site_url('text-blocks/update/' . $id),
        ]));
    }

    public function store()
    {
        $model = new TextBlockModel();
        $data  = $this->textDataFromPost();
        $data['status'] = Wum::STATUS_ACTIVE;
        if (!$model->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }
        return redirect()->to('/text-blocks')->with('success', 'Text angelegt.');
    }

    public function update($id)
    {
        $model = new TextBlockModel();
        $block = $model->find($id);
        if (!$block) {
            return redirect()->to('/text-blocks')->with('error', 'Text nicht gefunden.');
        }
        $data   = $this->textDataFromPost();
        $status = (int) $this->request->getPost('status');
        if ($status === Wum::STATUS_DELETED && (int) $block['status'] === Wum::STATUS_DELETED) {
            if ($model->countAllResults() < 2) {
                return redirect()->back()->with('error', 'Der letzte Datensatz kann nicht gelöscht werden.');
            }
            $model->delete($id);
            return redirect()->to('/text-blocks')->with('success', 'Text gelöscht.');
        }
        $data['status'] = $status;
        if (!$model->update($id, $data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }
        return redirect()->to('/text-blocks')->with('success', 'Text gespeichert.');
    }

    private function textDataFromPost(): array
    {
        return [
            'text_key' => $this->request->getPost('text_key'),
            'content'  => $this->request->getPost('content'),
            'note'     => $this->request->getPost('note'),
        ];
    }
}
