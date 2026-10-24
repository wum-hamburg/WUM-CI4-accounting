<?php

namespace App\Controllers;

use App\Models\ArticleModel;
use Config\Wum;

class Articles extends BaseController
{
    public function index()
    {
        $model = new ArticleModel();
        $split = $this->splitByStatus($model->getAllSorted());

        return view('Articles/index', $this->viewData([
            'moduleTitle'      => 'Artikel',
            'activeArticles'   => $split['active'],
            'inactiveArticles' => $split['inactive'],
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
            return view('Articles/form', $this->viewData([
                'moduleTitle' => 'Neuer Artikel',
                'article'     => [],
                'action'      => site_url('articles/store'),
            ]));
        }
        $model   = new ArticleModel();
        $article = $model->find($id);
        if (!$article) {
            return redirect()->to('/articles')->with('error', 'Artikel nicht gefunden.');
        }
        return view('Articles/form', $this->viewData([
            'moduleTitle' => 'Artikel bearbeiten',
            'article'     => $article,
            'action'      => site_url('articles/update/' . $id),
        ]));
    }

    public function store()
    {
        $model = new ArticleModel();
        $data  = $this->articleDataFromPost();
        $data['status'] = Wum::STATUS_ACTIVE;
        if (!$model->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }
        return redirect()->to('/articles')->with('success', 'Artikel angelegt.');
    }

    public function update($id)
    {
        $model   = new ArticleModel();
        $article = $model->find($id);
        if (!$article) {
            return redirect()->to('/articles')->with('error', 'Artikel nicht gefunden.');
        }
        $data   = $this->articleDataFromPost();
        $status = (int) $this->request->getPost('status');
        if ($status === Wum::STATUS_DELETED && (int) $article['status'] === Wum::STATUS_DELETED) {
            if ($model->countAllResults() < 2) {
                return redirect()->back()->with('error', 'Der letzte Datensatz kann nicht gelöscht werden.');
            }
            $model->delete($id);
            return redirect()->to('/articles')->with('success', 'Artikel gelöscht.');
        }
        $data['status'] = $status;
        if (!$model->update($id, $data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }
        return redirect()->to('/articles')->with('success', 'Artikel gespeichert.');
    }

    private function articleDataFromPost(): array
    {
        return [
            'article_number' => $this->request->getPost('article_number'),
            'title'          => $this->request->getPost('title'),
            'price_cents'    => (int) round((float) str_replace(',', '.', $this->request->getPost('price_euro')) * 100),
            'note'           => $this->request->getPost('note'),
        ];
    }
}
