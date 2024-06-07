<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: LogoutController.php                           *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 20:28:23                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:40:25                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service_helper('SessionHelper');

class LogoutController extends MvcController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Przejście pod adres: /auth/logout
     */
	public function index()
    {
        if (!isset($_SESSION['logged_user'])) header('Location:' . __URL_INIT_DIR__ . 'auth/login', true, 301);
        else
        {
            unset($_SESSION['logged_user']);
            $_SESSION[SessionHelper::LOGOUT_PAGE_BANNER] = array('is_open' => true);
            header('Location:' . __URL_INIT_DIR__, true, 301);
        }
	}
}
