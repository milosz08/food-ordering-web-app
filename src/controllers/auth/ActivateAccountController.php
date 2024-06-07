<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: ActivateAccountController.php                  *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 20:16:35                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:38:27                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Auth\Services\ActivateAccountService;

ResourceLoader::load_service('ActivateAccountService', 'auth'); // ładowanie serwisu przy użyciu require_once

class ActivateAccountController extends MvcController
{
    private $_service; // instancja serwisu

    public function __construct()
    {
        parent::__construct();
		$this->_service = MvcService::get_instance(ActivateAccountService::class); // stworzenie instancji serwisu
    }

    /**
     * Przejście pod adres: /auth/activate-account/resend
     */
    public function resend()
    {
        $this->protector->redirect_when_logged();
        $this->_service->resend_account_activation_link();
        header('Location:' . __URL_INIT_DIR__ . 'auth/login', true, 301);
    }

    /**
     * Przejście pod adres: /auth/activate-account
     */
	public function index()
    {
        $this->protector->redirect_when_logged();
        $this->_service->attempt_activate_account();
        header('Location:' . __URL_INIT_DIR__ . 'auth/login', true, 301);
	}
}
