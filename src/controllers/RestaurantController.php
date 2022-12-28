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
 * Ostatnia modyfikacja: 2022-12-28 14:24:30                   *
 * Modyfikowany przez: Desi                                    *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Utils\Utils;
use App\Core\MvcController;
use App\Services\RestaurantService;
use App\Services\DishService;

/**
 * Kontroler odpowiadający za obsługę dodawania oraz edytowania restauracji.
 */
class RestaurantController extends MvcController
{
    private $_resService; // instancja serwisu
    private $_dishService;

    
    //--------------------------------------------------------------------------------------------------------------------------------------

    public function __construct()
    {
        // Wywołanie konstruktora z klasy MvcController. Każda klasa kontrolera musi wywoływać konstruktor klasy nadrzędniej!
        parent::__construct();
        $this->_dishService = new DishService();
        $this->_resService = RestaurantService::get_instance(RestaurantService::class); // pobranie instancji klasy RestaurantService
        
    }
    
    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres restaurant/panel/dashboard. 
     */
    public function panel_dashboard()
    {
        $this->protector->protect_only_restaurator();
        $this->renderer->render_embed('restaurant/panel-wrapper-view', 'restaurant/panel-dashboard-view', array(
            'page_title' => 'Panel restauratora',
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres restaurant/panel/myrestaurants. 
     */
    public function panel_myrestaurants()
    {
        $this->protector->protect_only_restaurator();
        $restaurant_table = $this->_resService->get_user_restaurants();
        $mainpulate_restaurant_banner = Utils::check_session_and_unset('manipulate_restaurant_banner');
        $this->renderer->render_embed('restaurant/panel-wrapper-view', 'restaurant/panel-my-restaurants-view', array(
            'page_title' => 'Moje restauracje',
            'banner' => $mainpulate_restaurant_banner,
            'data' => $restaurant_table,
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres restaurant/panel/add. 
     */
    public function panel_myrestaurant_add()
    {
        $this->protector->protect_only_restaurator();
        $add_restaurant_form_data = $this->_resService->add_restaurant();
        $this->renderer->render_embed('restaurant/panel-wrapper-view', 'restaurant/panel-add-edit-restaurant-view', array(
            'page_title' => 'Dodaj restaurację',
            'add_edit_text' => 'Dodaj',
            'is_error' => !empty($add_restaurant_form_data['error']),
            'form' => $add_restaurant_form_data,
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres restaurant/panel/edit.
     */
    public function panel_myrestaurant_edit()
    {
        $this->protector->protect_only_restaurator();
        $edit_restaurant_form_data = $this->_resService->edit_restaurant();
        $this->renderer->render_embed('restaurant/panel-wrapper-view', 'restaurant/panel-add-edit-restaurant-view', array(
            'page_title' => 'Edytuj restaurację',
            'add_edit_text' => 'Edytuj',
            'is_error' => !empty($edit_restaurant_form_data['error']),
            'form' => $edit_restaurant_form_data,
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres restaurant/panel/myrestaurant/details&id=?.
     */
    public function panel_myrestaurant_details()
    {
        $this->protector->protect_only_restaurator();
        $details_restaurant_dish = $this->_resService->get_restaurant_details();
        $mainpulate_restaurant_banner = Utils::check_session_and_unset('manipulate_restaurant_banner');
        $this->renderer->render_embed('restaurant/panel-wrapper-view', 'restaurant/panel-restaurant-details-view', array(
            'page_title' => 'Szczegóły restauracji',
            'banner' => $mainpulate_restaurant_banner,
            'data' => $details_restaurant_dish,
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------
    
    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres restaurant/panel/delete.
     */
    public function panel_myrestaurant_delete()
    {
        $this->protector->protect_only_restaurator();
        $this->_resService->delete_restaurant();
        header('Location:' . __URL_INIT_DIR__ . 'restaurant/panel/myrestaurants', true, 301);
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres restaurant/panel/profile.
     */
    public function panel_profile()
    {
        $this->protector->protect_only_restaurator();
        $this->renderer->render_embed('restaurant/panel-wrapper-view', 'restaurant/panel-profile-view', array(
            'page_title' => 'Profil restauratora',
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres restaurant/panel/settings.
     */
    public function panel_settings()
    {
        $this->protector->protect_only_restaurator();
        $this->renderer->render_embed('restaurant/panel-wrapper-view', 'restaurant/panel-settings-view', array(
            'page_title' => 'Ustawienia',
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres restaurant/panel/orders.
     */
    public function panel_orders()
    {
        $this->protector->protect_only_restaurator();
        $this->renderer->render_embed('restaurant/panel-wrapper-view', 'restaurant/panel-restaurant-orders-view', array(
            'page_title' => 'Zamówienia',
        ));
    }


    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres restaurant/panel/add. 
     */
    public function panel_dish_add()
    {
        $this->protector->protect_only_restaurator();
        $add_dish_form_data = $this->_dishService->add_dish();
        $show_res = $this->_dishService->show_restaurants();
        $this->renderer->render_embed('restaurant/panel-wrapper-view', 'restaurant/panel-add-edit-dish-view', array(
            'page_title' => 'Dodaj danie',
            'add_edit_text' => 'Dodaj',
            'is_error' => !empty($add_dish_form_data['error']),
            'form' => $add_dish_form_data,
            'res' => $show_res
        ));
    }

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres restaurant/panel/edit. 
     */
    public function panel_dish_edit()
    {
        $this->protector->protect_only_restaurator();
        $edit_dish_form_data = $this->_dishService->edit_dish();
        $show_res = $this->_dishService->show_restaurants();
        $this->renderer->render_embed('restaurant/panel-wrapper-view', 'restaurant/panel-add-edit-dish-view', array(
            'page_title' => 'Edytuj danie',
            'add_edit_text' => 'Edytuj',
            'is_error' => !empty($edit_dish_form_data['error']),
            'form' => $edit_dish_form_data,
            'res' => $show_res
        ));
    }

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres restaurant/panel/delete.
     */
    public function panel_dish_delete()
    {
        $this->_dishService->remove_dish();
        //header('Location:' . __URL_INIT_DIR__ . 'restaurant/panel/myrestaurants', true, 301);
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres restaurant/panel/restaurant/single. 
     */
    public function panel_restaurant_single()
    {
        $this->renderer->render_embed('restaurant/panel-wrapper-view', 'restaurant/panel-restaurant-single-view');
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiana, kiedy użytkownik w ścieżce zapytania poda jedynie nazwę kontrolera, czyli jak ścieżka jest mniej więcej taka:
     *      restaurant
     * Metoda przekierowuje użytkownika na adres:
     *      restaurant/panel/dashbaord
     * renderując widok z metody panel_dashboard() powyższej klasy.
     */
    public function index()
    {
        header('Location:' . __URL_INIT_DIR__ . 'restaurant/panel/dashboard', true, 301);
    }
}
