<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: RegisterController.php                         *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 19:20:35                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:40:36                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Auth\Services\RegisterService;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service('RegisterService', 'auth'); // ładowanie serwisu przy użyciu require_once

class RegisterController extends MvcController
{
    private $_service; // instancja serwisu

    public function __construct()
    {
        parent::__construct();
		$this->_service = MvcService::get_instance(RegisterService::class); // stworzenie instancji serwisu
    }

    /**
     * Przejście pod adres: /auth/register
     */
	public function index()
    {
        $this->protector->redirect_when_logged();
        $register_user_data = $this->_service->register_user();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::REGISTER_PAGE_BANNER);
        $this->renderer->render('auth/registration-view', array(
            'page_title' => 'Rejestracja', 
            'data' => $register_user_data,
            'banner' => $banner_data,
        ));
	}
}
