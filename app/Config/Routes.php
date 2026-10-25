<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('login', 'Login::index');
$routes->post('login', 'Login::auth');
$routes->get('logout', 'Login::logout');
$routes->get('welcome', 'Home::index');
$routes->get('/', 'Home::index');
$routes->get('language/switch/(:segment)', 'Language::switch/$1');
// Dashbord not used today - you see only home
$routes->get('dashboard', 'Home::index');
// usermanagement
$routes->get('users/manage', 'UserManagement::index');
$routes->post('users/store', 'UserManagement::store');
$routes->post('users/update/(:num)', 'UserManagement::update/$1');
$routes->get('users/delete/(:num)', 'UserManagement::delete/$1');
$routes->get('users/choose', 'UserManagement::choose');
$routes->get('users/edit/(:num)', 'UserManagement::edit/$1');
$routes->get('users/create', 'UserManagement::create');
$routes->post('users/update/(:num)', 'UserManagement::update/$1');
$routes->get('users/delete/(:num)', 'UserManagement::delete/$1');

// Incoming invoices (Eingangsrechnungen)
$routes->get('incoming-invoices', 'IncomingInvoices::index');
$routes->get('incoming-invoices/capture', 'IncomingInvoices::capture');
$routes->post('incoming-invoices/set-year', 'IncomingInvoices::setYear');
$routes->post('incoming-invoices/select-creditor', 'IncomingInvoices::selectCreditor');
$routes->post('incoming-invoices/book', 'IncomingInvoices::book');
$routes->post('incoming-invoices/parse-upload', 'IncomingInvoices::parseUpload');
$routes->get('incoming-invoices/show/(:num)', 'IncomingInvoices::show/$1');
$routes->get('incoming-invoices/edit/(:num)', 'IncomingInvoices::edit/$1');
$routes->post('incoming-invoices/update/(:num)', 'IncomingInvoices::update/$1');
$routes->post('incoming-invoices/delete/(:num)', 'IncomingInvoices::delete/$1');

// Outgoing invoices (Ausgangsrechnungen) — X-Rechnung embedded on book
$routes->match(['get', 'post'], 'invoices/preview', 'Invoices::preview');
$routes->match(['get', 'post'], 'invoices/preview-from-convert', 'Invoices::previewFromConvert');
$routes->post('invoices/book', 'Invoices::book');
$routes->post('invoices/(:num)/status', 'Invoices::setStatus/$1');
$routes->post('invoices/(:num)/email', 'Invoices::sendEmail/$1');
$routes->get('invoices/(:num)/correction', 'Invoices::correction/$1');
