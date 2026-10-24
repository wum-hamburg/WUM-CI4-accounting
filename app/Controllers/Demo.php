<?php

namespace App\Controllers;

use App\Services\DemoResetService;

class Demo extends BaseController
{
    public function reset()
    {
        helper('wum');

        if (! wum_is_offline() || ! wum_is_demo()) {
            return redirect()->to('/dashboard')->with('error', 'Demo-Reset nur offline in der Demo verfügbar.');
        }

        @ini_set('max_execution_time', '0');
        @set_time_limit(0);

        $result = (new DemoResetService())->reset();

        return redirect()->to('/dashboard')->with(
            $result['ok'] ? 'success' : 'error',
            $result['message']
        );
    }
}
