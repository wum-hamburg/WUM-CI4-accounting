<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Block demo users from restricted routes (backup, restore, profile).
 */
class DenyDemoFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session()->get('is_demo') || session()->get('rights') === 'demo') {
            return redirect()->to('/dashboard')->with('error', 'In der Demo-Version nicht verfügbar.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
