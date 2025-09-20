<?php
//============================================================+
// File name   : UserManagement.php
// Version     : 1.0.0
// Begin       : 2025-07-07
// Last Update : 2025-07-23
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
* @method UserManagement (new, edit or delete users, with 3 rights to give)
* @author Marko Jorissen
* @version 1.0.0
*/
namespace App\Controllers;

class UserManagement extends BaseController
{
	// starting
	public function index()
	{
		//$menu definieren
		$menu = $this->getMenu(session()->get('rights') ?? null);
		// is login TRUE
		if (!session()->get('logged_in'))
		{
			return redirect()->to('/login');
		}
		// no rights as superadmin	
		elseif (!$this->hasRight('superadmin'))
		{
			return redirect()->to('/')->with('error', lang('UserManagement.no_authorization'));
		}
		// is superadmin
		elseif ($this->hasRight('superadmin'))
		{
			$userModel = new \App\Models\UserModel();
			// Alphabetisch sortieren
			$users = $userModel->orderBy('username', 'ASC')->findAll();
			$superadminUsername = 'wumy'; // TODO. dynamic
			return view('UserManagement/user_chose', [
				'users' => $users,
				'superadminUsername' => $superadminUsername,
				'menu' => $this->getMenu(session()->get('rights') ?? null)
			]);
		}
		// only safe action, if something get wrong 
		return view('welcome_message', ['menu' => $menu]);
	}
	/**
	* 
	* Functions to mange
	* 
	* @return result of the operation
	*/ 
	public function choose()
	{
		// code is in index
	}

	
	public function delete($id)
	{
		$userModel = new \App\Models\UserModel();
		$session = session();
		$session->remove('success_message');
		// Get current user from the session
		$currentUserId = $session->get('id');
		$currentUserRights = $session->get('rights');

		// only superadmin has the right to delete
		if ($currentUserRights !== 'superadmin')
		{
			return redirect()->to('/users/manage')->with('error', lang('UserManagement.no_permission'));
		}

		// Get user to be deleted
		$user = $userModel->find($id);
		if (!$user)
		{
			return redirect()->to('/users/manage')->with('error', lang('UserManagement.user_not_found'));
		}

		// Username for feedback
		$username = $user['username'] ?? ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');

		// Prevent self-deletion
		if ($currentUserId == $id)
		{
			return redirect()->to('/users/manage')->with('error', lang('UserManagement.cannot_delete_self'));
		}

		// Prevent deleting the last superadmin
		if ($user['rights'] === 'superadmin')
		{
			$superadminCount = $userModel->where('rights', 'superadmin')->countAllResults();
			if ($superadminCount <= 1)
			{
				return redirect()->to('/users/manage')->with('error', lang('UserManagement.last_superadmin'));
			}
		}

		// User deleting
		$userModel->delete($id);

		// Success message with username
		return redirect()->to('/users/manage')->with(
		'success',
		lang('UserManagement.user') . ' : ' . $username . ' ' . lang('UserManagement.user_deleted')
		);
	}
	public function create()
	{
		// Only superadmin can access this page
		if (!$this->hasRight('superadmin'))
		{
			return redirect()->to('/')->with('error', lang('UserManagement.no_authorization'));
		}

		$menu = $this->getMenu(session()->get('rights') ?? null);

		// Show the user creation form
		// return view('/UserManagement/user_create', ['menu' => $menu]);
		return view('/UserManagement/user_create', [
			'menu' => $menu,
			'moduleTitleKey' => 'UserManagement.title', // 
		]);

	}

	public function store()
	{
		// Only superadmin can create users
		if (!$this->hasRight('superadmin'))
		{
			return redirect()->to('/')->with('error', lang('UserManagement.no_authorization'));
		}

		//
		$userModel = new \App\Models\UserModel();
		//

		// Get POST data
		$data = [
			'username'   => $this->request->getPost('username'),
			'first_name' => $this->request->getPost('first_name'),
			'last_name'  => $this->request->getPost('last_name'),
			'language'   => $this->request->getPost('language'),
			'password'   => $this->request->getPost('password'),
			'rights'     => $this->request->getPost('rights'),
		];

		// Validate input using model rules
		if (!$userModel->validate($data))
		{
			// Validation failed, show errors
			return redirect()->back()->withInput()->with('errors', $userModel->errors());
		}

		// Insert new user
		$userModel->insert($data);

		// Success message and redirect
		return redirect()->to('/users/manage')->with('success', lang('UserManagement.user_created'));
	}
	public function edit($id)
	{
		$session = session();
		$currentUserId = $session->get('id');
		$currentUserRights = $session->get('rights');
		//
		$userModel = new \App\Models\UserModel();
		//
		$data = $this->request->getPost();
		$data['id'] = $id; // für is_unique-Regel
		// Superadmin can do everything, others only themselves
		if ($currentUserRights !== 'superadmin' && $currentUserId != $id)
		{
			return redirect()->to('/')->with('error', lang('UserManagement.no_authorization'));
		}

		
		$user = $userModel->find($id);

		if (!$user)
		{
			return redirect()->to('/users/manage')->with('error', lang('UserManagement.user_not_found'));
		}

		$menu = $this->getMenu(session()->get('rights') ?? null);

		return view('UserManagement/user_edit', [
			'user' => $user,
			'menu' => $menu
		]);
	}
	public function update($id)
	{
		$session = session();
		$currentUserId = $session->get('id');
		$currentUserRights = $session->get('rights');
		//
		$userModel = new \App\Models\UserModel();
		//
		// $id is the user who has a change, self or other(superadmin)
		$data = $this->request->getPost();
		$data['id'] = $id; // für is_unique-Regel
		//
		if (!$userModel->validate($data))
		{
			return redirect()->back()->withInput()->with('errors', $userModel->errors());
		}
		// Superadmin can do everything, others only themselves
		if ($currentUserRights !== 'superadmin' && $currentUserId != $id)
		{
			return redirect()->to('/')->with('error', lang('UserManagement.no_authorization'));
		}
		// 1. Remove previous success message
		$session->remove('success_message');

		// 2. Load current user
		$user = $userModel->find($id);
		if (!$user)
		{
			return redirect()->to('/users/manage')->with('error', lang('UserManagement.user_not_found'));
		}

		// 3. Get input data
		$data = $this->request->getPost();

		// 4. Collect only changed fields
		$updateData = [];
		if ($data['username'] !== $user['username'])
		{
			$updateData['username'] = $data['username'];
		}
		if ($data['first_name'] !== $user['first_name'])
		{
			$updateData['first_name'] = $data['first_name'];
		}
		if ($data['last_name'] !== $user['last_name'])
		{
			$updateData['last_name'] = $data['last_name'];
		}
		if ($data['language'] !== $user['language'])
		{
			$updateData['language'] = $data['language'];
		}
		// rights only for superadmins
		if (isset($data['rights']) && $data['rights'] !== $user['rights'])
		{
			$updateData['rights'] = $data['rights'];
		}
		// Update password only if entered
		if (!empty($data['password']))
		{
			$updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
		}

		// 5. Only update if something has changed
		if (!empty($updateData))
		{
			$userModel->update($id, $updateData);
			$session->setFlashdata('success_message', lang('UserManagement.update_success'));
		}
		else
		{
			$session->setFlashdata('success_message', lang('UserManagement.nothing_changed'));
		}
		// Update session when current user edits himself
		if ($currentUserId == $id)
		{
			// Get the current user data from the database
			$updatedUser = $userModel->find($id);
			// Update relevant session variables
			$session->set([
				'username'   => $updatedUser['username'],
				'first_name' => $updatedUser['first_name'],
				'last_name'  => $updatedUser['last_name'],
				'language'   => $updatedUser['language'],
				'lang'   => $updatedUser['language'],
			]);
		}
		return redirect()->to('/users/edit/' . $id);
	}

	} ?>