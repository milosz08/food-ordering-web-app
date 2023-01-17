<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: ForgotPasswordController.php                   *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 19:42:14                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-12 02:58:49                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;
use App\Auth\Services\ForgotPasswordService;

ResourceLoader::load_service('ForgotPasswordService', 'auth'); // ładowanie serwisu przy użyciu require_once

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class ForgotPasswordController extends MvcController
{
    private $_service; // instancja serwisu

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
    public function __construct()
    {
        parent::__construct();
		$this->_service = MvcService::get_instance(ForgotPasswordService::class); // stworzenie instancji serwisu
    }

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /auth/forgot-password/change
     */
    public function change()
    {
        $this->protector->redirect_when_logged();
        $form_data = $this->_service->forgot_password_change();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::FORGOT_PASSWORD_CHANGE_PAGE_BANNER);
        $this->renderer->render('auth/renew-password-change-view', array(
            'page_title' => 'Zmień hasło',
            'form' => $form_data,
            'banner' => $banner_data,
        ));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /auth/forgot-password
     */
    public function index()
    {
        $this->protector->redirect_when_logged();
        $form_data = $this->_service->forgot_password_request();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::FORGOT_PASSWORD_PAGE_BANNER);
        $this->renderer->render('auth/renew-password-email-view', array(
            'page_title' => 'Resetuj hasło',
            'form' => $form_data,
            'banner' => $banner_data,
        ));
    }
}
