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
 * Ostatnia modyfikacja: 2022-11-28 21:24:32                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

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
     * Metoda uruchamiająca się w przypadku przejścia na adres index.php?action=auth/registration. 
     */
    public function register()
    {
        $registraion_form_data = $this->_service->register();
        $this->renderer->render('auth/registration-view', array(
            'page_title' => 'Rejestracja', 
            'form' => $registraion_form_data,
            'is_error' => !empty($registraion_form_data['error']),
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres index.php?action=auth/login. 
     */
    public function login()
    {
        $login_form_data = $this->_service->login_user();
        $this->renderer->render('auth/login-view', array(
            'page_title' => 'Logowanie',
            'form' => $login_form_data,
            'is_error' => !empty($login_form_data['error']),
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiana, kiedy użytkownik w ścieżce zapytania poda jedynie nazwę kontrolera, czyli jak ścieżka jest mniej więcej taka:
     *      index.php?action=home
     * Metoda przekierowuje użytkownika na adres:
     *      index.php?action=home/welcome
     * renderując widok z metody welcode() powyższej klasy.
     */
    public function index()
    {
        header('Location:index.php?action=auth/login');
    }
}
