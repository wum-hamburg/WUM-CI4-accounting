<?php
//============================================================+
// File name   : Login.php
// Version     : 1.0.0
// Begin       : 2025-07-07
// Last Update : 2025-07-22
// Author      : Marko Jorissen - www.wum.hamburg - info@wum.hamburg
// License     : GNU-LGPL v3 (http://www.gnu.org/copyleft/lesser.html)
// -------------------------------------------------------------------
// Copyright (C) 2025 Marko Jorissen - Webseiten und mehr - Hamburg
//
// This file is part of Wum-CI4-accounting software library.
//
// Wum-CI4-accounting is free software: you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// Wum-CI4-accounting is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the License
// along with it. If not, see
// https://github.com/wum-hamburg/WUM-CI4-accounting/blob/main/LICENSE
//
// See LICENSE.TXT file for more information.
// -------------------------------------------------------------------
//
// Description :
// This is a PHP code on base from CodeIgniter-framwork (4.6) and bootstrap5.
//


/**
* @method Login.php (without nothing to see)
* @author Marko Jorissen
* @version 1.0.0
*/
namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Controller;

class Login extends BaseController
{
public function index()
{
helper(['form']);
return view('login');
}

public function auth()
{
$session = session();
$model = new UserModel();

$username = $this->request->getVar('username');
$password = $this->request->getVar('password');
$user = $model->where('username', $username)->first();

if ($user && password_verify($password, $user['password'])) 
{
	$session->set([
	'id' => $user['id'],
	'first_name' => $user['first_name'],
	'last_name' => $user['last_name'],
	'username' => $user['username'],
	'language'=> $user['language'],
	'rights'=>$user ['rights'],
	'logged_in' => true
	]);
	$session->set('lang', $user['language']);
	$menu = $this->getMenu(session()->get('rights') ?? null);
	return redirect()->to('/welcome');
} else {
	$validation = \Config\Services::validation();
//
	$validation->setRules([
		'username' => 'required|min_length[3]|max_length[50]',
		'password' => 'required|min_length[5]',
		'first_name' => 'required|min_length[3]|max_length[100]',
		'last_name' => 'required|min_length[3]|max_length[100]',
	], [
		'username' => [
			'required' => lang('Validation.username_required'),
			'min_length' => lang('Validation.username_min_length'),
			'max_length' => lang('Validation.username_max_length'),
		],
		'password' => [
			'required' => lang('Validation.password_required'),
			'min_length' => lang('Validation.password_min_length'),
		],
		'first_name' => [
			'required' => lang('Validation.first_name_required'),
			'min_length' => lang('Validation.first_name_min_length'),
			'max_length' => lang('Validation.first_name_max_length'),
		],
		'last_name' => [
			'required' => lang('Validation.last_name_required'),
			'min_length' => lang('Validation.last_name_min_length'),
			'max_length' => lang('Validation.last_name_max_length'),
		],
	]);

	if (!$validation->withRequest($this->request)->run())
	{
		$errors = $validation->getErrors();
		// $errors kannst du an die View übergeben und anzeigen
	}

$session->setFlashdata('login_error', lang('Login.error'));
return redirect()->to('/login');
}
}

public function logout()
{
session()->destroy();
return redirect()->to('/login');
}
}
?>