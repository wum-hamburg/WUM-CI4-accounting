<?php
//============================================================+
// File name   : BaseController.php
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
* @method BaseController (with menu)
* @author Marko Jorissen
* @version 1.0.0
*/

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var list<string>
     */
    protected $helpers = [];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
		// Share menu
		$this->menu = $this->getMenu(session()->get('rights') ?? null);
		$session = session();
		$locale = $session->get('lang') ?? 'de';
		$request->setLocale($locale);
		       
        // Preload any models, libraries, etc, here.
		

        // E.g.: $this->session = service('session');
    }
    // control rights
	protected function hasRight($right)
	{
		$userRight = session()->get('rights');
		// superadmin darf alles
		return $userRight === $right || $userRight === 'superadmin';
	}
	// more
	protected function hasAnyRight(array $rights)
	{
		$userRight = session()->get('rights');
		return in_array($userRight, $rights) || $userRight === 'superadmin';
	}
	// Menu - Function
	
	protected function getMenu($rights = null)
	{
		$userID=session()->get('id');
		$menu = [
			['label' => '-' . lang('Menu.dashboard'), 'route' => 'dashboard', 'class' => 'nav-link'],
		];
		
		if ($rights == 'superadmin')
		{
			$menu[] = [
				'label' => '- <i class="fas fa-users-cog"></i> ' . lang('Menu.user_management'),
				'route' => 'users/manage',
				'class' => 'nav-link'
			];
		}else 
		{
			$menu[] = [
				'label' => '- <i class="fas fa-person"></i> ' . lang('Menu.user_edit'),
				'route' => 'users/edit/'.$userID,
				'class' => 'nav-link'
			];
		}

		$menu[] = ['label' => '-' . lang('Menu.logout'), 'route' => 'logout', 'class' => 'nav-link'];
		return $menu;
	}


}



