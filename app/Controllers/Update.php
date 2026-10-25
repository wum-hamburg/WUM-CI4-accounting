<?php

namespace App\Controllers;

use App\Services\SchemaUpdateService;

class Update extends BaseController
{
    public function index()
    {
        $service = new SchemaUpdateService();
        if (! $service->hasPending()) {
            return redirect()->to('/dashboard')->with('info', 'Kein Datenbank-Update erforderlich.');
        }

        return view('Update/index', $this->viewData([
            'moduleTitle' => 'Datenbank-Update',
            'pending'     => $service->pending(),
        ]));
    }

    public function run()
    {
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);

        $service = new SchemaUpdateService();
        if (! $service->hasPending()) {
            return redirect()->to('/dashboard')->with('info', 'Kein Datenbank-Update erforderlich.');
        }

        $result = $service->apply();

        return redirect()->to('/dashboard')->with(
            $result['ok'] ? 'success' : 'error',
            $result['message']
        );
    }
}
