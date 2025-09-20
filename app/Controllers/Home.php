<?php

//============================================================+
// File name   : Home.php
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
* @method Home (starting site)
* @author Marko Jorissen
* @version 1.0.0
*/
namespace App\Controllers;

class Home extends BaseController
{

	public function index()
{
		if (!session()->get('logged_in'))
		{
			return redirect()->to('/login');
		}

		$menu = $this->getMenu(session()->get('rights') ?? null);


	return view('welcome_message', ['menu' => $menu]);
}
}