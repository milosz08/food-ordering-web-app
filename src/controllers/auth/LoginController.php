<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: LoginController.php                            *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 18:31:08                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:38:48                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Auth\Services\LoginService;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service('LoginService', 'auth'); // ładowanie serwisu przy użyciu require_once

class LoginController extends MvcController
{
	private $_service; // instancja serwisu

    public function __construct()
    {
        parent::__construct();
		$this->_service = MvcService::get_instance(LoginService::class); // stworzenie instancji serwisu
    }

	/**
     * Przejście pod adres: /auth/login
     */
	public function index()
    {
		$this->protector->redirect_when_logged();
        $form_data = $this->_service->login_user();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::LOGIN_PAGE_BANNER); 
        $this->renderer->render('auth/login-view', array(
            'page_title' => 'Logowanie',
            'data' => $form_data,
            'banner' => $banner_data,
        ));
	}
}
