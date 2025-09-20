<?php
//============================================================+
// File name   : BaseController.php
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
* @method MODEL : UserModel (database actions)
* @author Marko Jorissen
* @version 1.0.0
*/
namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
	protected $table = 'user'; // name of the db-table
	protected $primaryKey = 'id'; // Primary key

	protected $allowedFields = ['username', 'first_name','last_name', 'language','password','rights']; // Fields you can write

	// Optional: Validation rules for inputs
	protected $validationRules = 
	[
	//  lang('Labels.passord') is not allowed!
	// TODO view or/and controller adjust
	// TODO Weitere deutsche Texte durch Sprachdatei-Kürzel ersetzen
		'id' => 
		[
			'label' => 'ID',
			'rules' => 'permit_empty|integer'
		],
		'username' =>
		[
			'label' => 'username',
			'rules' => 'required|alpha_numeric|min_length[3]|max_length[50]|is_unique[user.username,id,{id}]',
			'errors' => 
			[
			'required'   => 'no_username',
			'min_length' => 'Benutzername muss mindestens 3 Zeichen lang sein.',
			'is_unique'  => 'Benutzername muss eindeutig sein.'
			]
		],
		'password' => 
		[
			'label' => 'password',
			'rules' =>'required|min_length[5]'
		],
			//
		'first_name' => 
		[
			'label' => 'first_name',
			'rules' =>'required|min_length[3]|max_length[100]'
		],
		'last_name' => 
		[
			'label' => 'last_name',
			'rules' =>'required|min_length[3]|max_length[100]'
		],
	];

	// Optional: Automatisches Hashen des Passworts vor Insert/Update
	protected $beforeInsert = ['hashPassword'];
	protected $beforeUpdate = ['hashPassword'];

	protected function hashPassword(array $data)
	{
		if (isset($data['data']['password']))
		{
			$data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
		}
		return $data;
	}
	
}

?>