<?php
namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class LanguageFilter implements FilterInterface
{
public function before(RequestInterface $request, $arguments = null)
{
$session = session();
$locale = $session->get('lang') ?? config('App')->defaultLocale;
service('request')->setLocale($locale);
}

public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
{
// No action needed after
}
}
?>