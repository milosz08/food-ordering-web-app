<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: RestaurantController.php                       *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-11-27, 19:49:47                       *
 * Autor: cptn3m012                                            *
 *                                                             *
 * Ostatnia modyfikacja: 2022-11-27 20:47:32                   *
 * Modyfikowany przez: cptn3m012                               *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcController;
use App\Services\RestaurantService;

/**
 * Kontroler odpowiadający za obsługę logowania, rejestracji oraz innych usług autentykacji i autoryzacji użytkowników.
 */

class RestaurantController extends MvcController
{
    private $_service; // instancja serwisu
    
    //--------------------------------------------------------------------------------------------------------------------------------------

    public function __construct()
    {
        // Wywołanie konstruktora z klasy MvcController. Każda klasa kontrolera musi wywoływać konstruktor klasy nadrzędniej!
        parent::__construct();
        $this->_service = RestaurantService::get_instance(RestaurantService::class); // pobranie instancji klasy RegistrationService
    }
    
    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres index.php?action=restaurant/add-restaurant. 
     */
    public function add_restaurant()
    {
        $add_restaurant_form_data = $this->_service->add_restaurant();
        $this->renderer->render('restaurant/add-restaurant-view', array(
            'page_title' => 'Dodaj restauracje', 
            'form' => $add_restaurant_form_data,
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres index.php?action=restaurant/edit-restaurant. 
     */
    public function edit_restaurant()
    {
        $edit_restaurant_form_data = $this->_service->edit_restaurant();
        $this->renderer->render('restaurant/edit-restaurant-view', array(
            'page_title' => 'Edytuj restauracje', 
            'form' => $edit_restaurant_form_data,
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
        header('Location:index.php?action=home/welcome');
    }
}

 
