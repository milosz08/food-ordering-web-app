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
 * Ostatnia modyfikacja: 2022-11-26 22:11:29                   *
 * Modyfikowany przez: patrick012016                           *
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
        $registraion_name = $this->_service->registration();

        if (isset($registraion_name) == NULL) {
            for ($i=0; $i < 17; $i++) { 
                $registraion_name[$i] = "";
            }
        }

        $this->renderer->render('auth/registration-view', array(
            'page_title' => 'Rejestracja', 'name'=>$registraion_name[0], 'surname'=>$registraion_name[1], 'login'=>$registraion_name[2],
            'email'=>$registraion_name[3], 'local-number'=>$registraion_name[4], 'post-code'=>$registraion_name[5], 'city'=>$registraion_name[6],
            'street'=>$registraion_name[7], 'validationName'=>$registraion_name[8], 'validationSurname'=>$registraion_name[9],
            'validationLogin'=>$registraion_name[10], 'validationPassword'=>$registraion_name[11], 'validationEmail'=>$registraion_name[12],
            'validationLN'=>$registraion_name[13], 'validationPC'=>$registraion_name[14], 'validationCity'=>$registraion_name[15],
            'validationStreet'=>$registraion_name[16]
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres index.php?action=auth/login. 
     */
    public function login()
    {
        $login_variables = $this->_service->login_user();

        if (isset($login_variables) == NULL) {
            for ($i = 0; $i < 2; $i++) { 
                $login_variables[$i] = "";
            }
        }

        $this->renderer->render('auth/login-view', array(
            'page_title' => 'Logowanie',
            'loginError' => $login_variables[0],
            'passError' => $login_variables[1]
        )
        );
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
