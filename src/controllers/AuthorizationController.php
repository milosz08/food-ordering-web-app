<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: AuthorizationController.php                    *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-11-22, 18:48:27                       *
 * Autor: Patryk Górniak                                       *
 *                                                             *
 * Ostatnia modyfikacja: 2022-11-22 20:18:28                   *
 * Modyfikowany przez: Desi                                    *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcController;

    // Kontroler odpowiadający za podstawowe wyrenderowywanie widoku panelu rejestracji.
class AuthorizationController extends MvcController
{
    //--------------------------------------------------------------------------------------------------------------------------------------

    public function __construct()
    {
        // Wywołanie konstruktora z klasy MvcController. Każda klasa kontrolera musi wywoływać konstruktor klasy nadrzędniej!
        parent::__construct();
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres index.php?action=authorization/registration. Metoda na końcu renderuje dany widok.
     */
    public function registration()
    {
        $this->renderer->render('authorization/registration-view', array());
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    public function login()
    {
        $this->renderer->render('authorization/login-view', array());
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
        header('Location:index.php?action=authorization/registration');
    }
}
