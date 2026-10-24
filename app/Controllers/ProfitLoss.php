<?php

namespace App\Controllers;

use App\Libraries\TcpdfDocument;
use App\Services\ProfitLossService;

class ProfitLoss extends BaseController
{
    public function index()
    {
        $service = new ProfitLossService();
        $years   = $service->taxYears();
        $year    = (int) ($this->request->getGet('year') ?? date('Y'));
        if (! $service->isAllowedYear($year)) {
            $year = (int) end($years);
        }

        $report = $service->build($year);

        return view('ProfitLoss/index', $this->viewData([
            'moduleTitle' => 'Einnahmenüberschussrechnung',
            'years'       => $years,
            'year'        => $year,
            'report'      => $report,
        ]));
    }

    public function pdf()
    {
        helper('wum');
        if (wum_is_demo()) {
            return redirect()->to('/guv')->with('error', 'In der Demo-Version nicht verfügbar.');
        }

        $service = new ProfitLossService();
        $years   = $service->taxYears();
        $year    = (int) ($this->request->getGet('year') ?? date('Y'));
        if (! $service->isAllowedYear($year)) {
            $year = (int) end($years);
        }

        $report = $service->build($year);
        $html   = view('ProfitLoss/pdf', [
            'year'   => $year,
            'report' => $report,
        ]);

        $dir = WRITEPATH . 'archives' . DIRECTORY_SEPARATOR . 'guv' . DIRECTORY_SEPARATOR;
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = 'EUR-' . $year . '.pdf';
        $path     = $dir . $filename;

        // Page 1 beside logo; page 2+ below Kontakt; Firmenbogen + Seitenfuß on every page
        if (! (new TcpdfDocument())->generate($html, 'EÜR ' . $year, $path, false, 48, true)) {
            return redirect()->to('/guv?year=' . $year)->with('error', 'PDF konnte nicht erzeugt werden.');
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setBody((string) file_get_contents($path));
    }
}
