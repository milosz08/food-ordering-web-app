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
 * Ostatnia modyfikacja: 2022-12-06 16:48:41                   *
 * Modyfikowany przez: Desi                                    *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcController;
use App\Services\RestaurantService;

/**
 * Kontroler odpowiadający za obsługę dodawania oraz edytowania restauracji.
 */
class RestaurantController extends MvcController
{
    private $_service; // instancja serwisu
    
    //--------------------------------------------------------------------------------------------------------------------------------------

    public function __construct()
    {
        // Wywołanie konstruktora z klasy MvcController. Każda klasa kontrolera musi wywoływać konstruktor klasy nadrzędniej!
        parent::__construct();
        $this->_service = RestaurantService::get_instance(RestaurantService::class); // pobranie instancji klasy RestaurantService
    }
    
    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres index.php?action=restaurant/add. 
     */
    public function add()
    {
        $add_restaurant_form_data = $this->_service->add_restaurant();
        $this->renderer->render('restaurant/add-edit-restaurant-view', array(
            'page_title' => 'Dodaj restaurację',
            'add_edit_text' => 'Dodaj',
            'is_error' => !empty($add_restaurant_form_data['error']),
            'form' => $add_restaurant_form_data,
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres index.php?action=restaurant/edit.
     */
    public function edit()
    {
        $edit_restaurant_form_data = $this->_service->edit_restaurant();
        $this->renderer->render('restaurant/add-edit-restaurant-view', array(
            'page_title' => 'Edytuj restaurację',
            'add_edit_text' => 'Edytuj',
            'add_delete_text' => 'Usuń',
            'is_error' => !empty($edit_restaurant_form_data['error']),
            'form' => $edit_restaurant_form_data,
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres index.php?action=restaurant/delete.
     */
    public function delete()
    {
        $edit_restaurant_form_data = $this->_service->delete_restaurant();
        $this->renderer->render('restaurant/add-edit-restaurant-view', array(
            'page_title' => 'Edytuj restaurację',
            'add_edit_text' => 'Edytuj',
            'add_delete_text' => 'Usuń',
            'is_error' => !empty($edit_restaurant_form_data['error']),
            'form' => $edit_restaurant_form_data,
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiana, kiedy użytkownik w ścieżce zapytania poda jedynie nazwę kontrolera, czyli jak ścieżka jest mniej więcej taka:
     *      index.php?action=restaurant
     * Metoda przekierowuje użytkownika na adres:
     *      index.php?action=restaurant/add
     * renderując widok z metody welcode() powyższej klasy.
     */
    public function index()
    {
        header('Location:index.php?action=restaurant/add');
    }
}
