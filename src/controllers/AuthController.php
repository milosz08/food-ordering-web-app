<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: AuthController.php                             *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-11-22, 18:48:27                       *
 * Autor: Patryk Górniak                                       *
 *                                                             *
 * Ostatnia modyfikacja: 2022-12-11 20:24:32                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Utils\Utils;
use App\Core\MvcController;
use App\Services\AuthService;

/**
 * Kontroler odpowiadający za obsługę logowania, rejestracji oraz innych usług autentykacji i autoryzacji użytkowników.
 */
class AuthController extends MvcController
{
    private $_service;

    //--------------------------------------------------------------------------------------------------------------------------------------

    public function __construct()
    {
        // Wywołanie konstruktora z klasy MvcController. Każda klasa kontrolera musi wywoływać konstruktor klasy nadrzędniej!
        parent::__construct();
        $this->_service = AuthService::get_instance(AuthService::class); // pobranie instancji klasy RegistrationService
    }
    
    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres auth/registration. 
     */
    public function register()
    {
        $banner_data = Utils::check_session_and_unset('successful_register_user');
        $form_data = $this->_service->register();
        $banner_data = Utils::fill_banner_with_form_data($form_data, $banner_data);
        $this->renderer->render('auth/registration-view', array(
            'page_title' => 'Rejestracja', 
            'form' => $form_data,
            'banner' => $banner_data,
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres auth/login. 
     */
    public function login()
    {
        $banner_data = Utils::check_session_and_unset('attempt_activate_account');
        $form_data = $this->_service->login_user();
        $banner_data = Utils::fill_banner_with_form_data($form_data, $banner_data);
        $this->renderer->render('auth/login-view', array(
            'page_title' => 'Logowanie',
            'form' => $form_data,
            'banner' => $banner_data,
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres auth/password/renew/request. 
     */
    public function password_renew_request()
    {
        $banner_data = Utils::check_session_and_unset('attempt_change_password');
        $form_data = $this->_service->attempt_renew_password();
        $banner_data = Utils::fill_banner_with_form_data($form_data, $banner_data);
        $this->renderer->render('auth/renew-password-email-view', array(
            'page_title' => 'Resetuj hasło',
            'form' => $form_data,
            'banner' => $banner_data,
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres auth/password/renew/change. 
     */
    public function password_renew_change()
    {
        $form_data = $this->_service->renew_change_password();
        $this->renderer->render('auth/renew-password-change-view', array(
            'page_title' => 'Zmień hasło',
            'form' => $form_data,
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres auth/account/activate.
     */
    public function account_activate()
    {
        $this->_service->attempt_activate_account();
        header('Location:' . __URL_INIT_DIR__ . 'auth/login', true, 301);
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres auth/account/resend/code&userid=?.
     */
    public function account_activate_resend_code()
    {
        $this->_service->resend_account_activation_link();
        header('Location:' . __URL_INIT_DIR__ . 'auth/login', true, 301);
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres auth/logout.
     */
    public function logout()
    {
        unset($_SESSION['logged_user']);
        header('Location:' . __URL_INIT_DIR__, true, 301);
        $_SESSION['logout_modal_data'] = array(
            'is_open' => true,
        );
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiana, kiedy użytkownik w ścieżce zapytania poda jedynie nazwę kontrolera, czyli jak ścieżka jest mniej więcej taka:
     *      index.php?action=auth
     * Metoda przekierowuje użytkownika na adres:
     *      index.php?action=auth/login
     * renderując widok z metody login() powyższej klasy.
     */
    public function index()
    {
        header('Location:' . __URL_INIT_DIR__ . 'auth/login', true, 301);
    }
}
