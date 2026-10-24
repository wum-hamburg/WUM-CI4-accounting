<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Require a specific user role / Rollenprüfung
 */
class RoleFilter implements FilterInterface
{
    private const ALLOWED_ROLES = ['registered', 'admin', 'superadmin', 'demo'];

    public function before(RequestInterface $request, $arguments = null)
    {
        $required = $arguments[0] ?? 'superadmin';
        if (!in_array($required, self::ALLOWED_ROLES, true)) {
            $required = 'superadmin';
        }

        $userRight = session()->get('rights');
        if ($userRight !== $required && $userRight !== 'superadmin') {
            return redirect()->to('/')->with('error', 'Keine Berechtigung.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
