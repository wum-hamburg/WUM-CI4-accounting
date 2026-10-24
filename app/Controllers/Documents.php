<?php

namespace App\Controllers;

use App\Models\ArticleModel;
use App\Models\CustomerModel;
use App\Models\TextBlockModel;
use App\Services\DocumentSessionService;
use Config\Wum;

class Documents extends BaseController
{
    private DocumentSessionService $sessionService;

    public function __construct()
    {
        $this->sessionService = new DocumentSessionService();
    }

    /**
     * Start wizard for Angebot or Rechnung (skips former type-selection step).
     */
    public function start(string $type)
    {
        if (! in_array($type, ['quote', 'invoice'], true)) {
            return redirect()->to('/dashboard');
        }

        if (session()->get('document_type') !== $type) {
            session()->remove(['customer_number', 'line_items', 'text_keys', 'total_cents', 'correction_of', 'source_quote_id', 'convert_post_data']);
        }

        session()->set('document_type', $type);
        $this->sessionService->setStep(2);

        return redirect()->to('/documents');
    }

    public function letter()
    {
        return view('Documents/letter', $this->viewData([
            'moduleTitle' => 'Brief',
            'moduleIcon'  => config('Wum')->menuIcons['document_letter'] ?? null,
        ]));
    }

    public function index()
    {
        if ($this->request->getPost('abort')) {
            $this->sessionService->reset();
            return redirect()->to('/dashboard');
        }

        if ($this->request->getGet('step')) {
            $step = (int) $this->request->getGet('step');
            if ($this->sessionService->isStepAllowed($step)) {
                $this->sessionService->setStep($step);
            }
            return redirect()->to('/documents');
        }

        if ($this->request->getPost('save_customer')) {
            $number = $this->request->getPost('customer_number');
            session()->set('customer_number', $number);
            session()->remove(['line_items', 'text_keys', 'total_cents']);
            $this->sessionService->setStep(3);
            return redirect()->to('/documents');
        }

        if ($this->request->getPost('save_selection')) {
            $documentType = session()->get('document_type');
            $textKeys     = $this->request->getPost('text_keys') ?? [];

            if ($documentType === 'quote') {
                $positions = $this->sessionService->parsePositionsFromPost($this->request->getPost());
                if ($positions === [] || ! $this->sessionService->hasBillablePositions($positions)) {
                    return view('Documents/wizard', $this->wizardData(3, 'Bitte mindestens eine Position mit Menge größer 0 angeben.'));
                }
                $this->sessionService->setLineItems($positions);
            } elseif (session()->get('correction_of')) {
                $positions = $this->sessionService->parsePositionsFromPost($this->request->getPost());
                if ($positions === [] || ! $this->sessionService->hasBillablePositions($positions)) {
                    return view('Documents/wizard', $this->wizardData(3, 'Bitte mindestens eine Position mit Menge größer 0 angeben.'));
                }
                $this->sessionService->setLineItems($positions);
                if (!in_array('schluss1', $textKeys, true)) {
                    $textKeys[] = 'schluss1';
                }
            } else {
                $quantities = $this->request->getPost('quantity') ?? [];
                $items      = [];
                foreach ($quantities as $nr => $qty) {
                    if ((int) $qty > 0) {
                        $items[] = [
                            'type'           => 'catalog',
                            'article_number' => (string) $nr,
                            'qty'            => (int) $qty,
                        ];
                    }
                }
                if ($items === []) {
                    return view('Documents/wizard', $this->wizardData(3, 'Bitte mindestens einen Artikel mit Menge größer 0 wählen.'));
                }
                $this->sessionService->setLineItems($items);
                if (!in_array('schluss1', $textKeys, true)) {
                    $textKeys[] = 'schluss1';
                }
            }

            session()->set('text_keys', array_map('strval', $textKeys));
            $this->sessionService->recalculateTotal();
            $this->sessionService->setStep(4);
            return redirect()->to('/documents');
        }

        if ($this->request->getPost('save_step4')) {
            $positions = $this->sessionService->parseLineItemsFromPost($this->request->getPost());
            if ($positions === [] || ! $this->sessionService->hasBillablePositions($positions)) {
                return view('Documents/wizard', $this->wizardData(4, 'Bitte mindestens eine Position mit Menge größer 0 angeben.'));
            }
            $this->sessionService->setLineItems($positions, true);
            $this->sessionService->recalculateTotal();
            return redirect()->to('/documents');
        }

        if (! session()->get('document_type')) {
            return redirect()->to('/dashboard');
        }

        $step = $this->sessionService->getStep();
        if ($step < 2 || ! $this->sessionService->isStepAllowed($step)) {
            $step = 2;
            $this->sessionService->setStep(2);
        }

        return view('Documents/wizard', $this->wizardData($step));
    }

    private function wizardData(int $step, ?string $error = null): array
    {
        $customerModel = new CustomerModel();
        $articleModel  = new ArticleModel();
        $textModel     = new TextBlockModel();
        $documentType  = session()->get('document_type');
        $typeLabel     = $this->sessionService->typeLabel($documentType);

        $customer = null;
        if (session()->get('customer_number')) {
            $customer = $customerModel->findByCustomerNumber(session()->get('customer_number'));
        }

        if ($step === 4) {
            $this->sessionService->recalculateTotal();
        }

        $lineItems = $this->sessionService->normalizeLineItems(session()->get('line_items') ?? []);
        $customLines = array_values(array_filter($lineItems, static fn ($p) => $p['type'] === 'custom'));
        $catalogLines = [];
        foreach ($lineItems as $p) {
            if ($p['type'] === 'catalog') {
                $catalogLines[$p['article_number']] = $p['qty'];
            }
        }

        $textCategories = $documentType === 'quote'
            ? [
                ['vor', 'Vorbemerkungen'],
                ['hin', 'Text im Angebot'],
            ]
            : [
                ['vor', 'Vorbemerkungen'],
                ['hin', 'Text in der Rechnung'],
                ['std', 'Stundenlohntexte'],
                ['schluss', 'Schlussbemerkungen'],
            ];

        return $this->viewData([
            'moduleTitle'    => $typeLabel !== '' ? $typeLabel . ' erstellen' : 'Dokument erstellen',
            'step'           => $step,
            'error'          => $error,
            'documentType'   => $documentType,
            'typeLabel'      => $typeLabel,
            'stepTitle'      => $this->sessionService->stepTitle($step),
            'customerGroups' => $customerModel->getGroupedByStatus(),
            'articles'       => $articleModel->getAllSorted(),
            'textCategories' => $textCategories,
            'textModel'      => $textModel,
            'customer'       => $customer,
            'lineItems'      => $catalogLines,
            'customLines'    => $customLines,
            'previewLines'   => $lineItems,
            'textKeys'       => session()->get('text_keys') ?? [],
            'totalCents'     => session()->get('total_cents') ?? 0,
            'fromConvert'    => session()->get('source_quote_id') !== null,
            'isCorrection'   => session()->get('correction_of') !== null,
            'statusLabels'   => [
                Wum::STATUS_DELETED  => 'Inaktiv',
                Wum::STATUS_ACTIVE   => 'Aktiv',
                Wum::STATUS_ONE_TIME => 'Einmalig',
            ],
        ]);
    }
}
