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

