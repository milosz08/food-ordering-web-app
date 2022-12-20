<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: DishesController.php                           *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-12-20, 19:11:35                       *
 * Autor: Lukasz Krawczyk                                      *
 *                                                             *
 * Ostatnia modyfikacja: 2022-12-20 19:37:10                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcController;
use App\Services\DishService;

class DishController extends MvcController
{
    private $_service;
    
    //--------------------------------------------------------------------------------------------------------------------------------------

    public function __construct()
    {
        // Wywołanie konstruktora z klasy MvcController. Każda klasa kontrolera musi wywoływać konstruktor klasy nadrzędniej!
        parent::__construct();
        $this->_service = DishService::get_instance(DishService::class);
    }
        
    //--------------------------------------------------------------------------------------------------------------------------------------
    
    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres restaurant/panel/add. 
     */
    public function panel_myrestaurant_add()
    {
        $this->protector->protect_only_restaurator();
        $add_restaurant_form_data = $this->_service->add_dish();
        $this->renderer->render_embed('dish/panel-wrapper-view', 'dish/panel-add-edit-restaurant-view', array(
            'page_title' => 'Dodaj restaurację',
            'add_edit_text' => 'Dodaj',
            'is_error' => !empty($add_restaurant_form_data['error']),
            'form' => $add_restaurant_form_data,
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiana, kiedy użytkownik w ścieżce zapytania poda jedynie nazwę kontrolera, czyli jak ścieżka jest mniej więcej taka:
     *      dish
     * Metoda przekierowuje użytkownika na adres:
     *      dish/panel/dashbaord
     * renderując widok z metody panel_dashboard() powyższej klasy.
     */
    public function index()
    {
        header('Location:' . __URL_INIT_DIR__ . 'dish/panel/dashboard', true, 301);
    }


}
