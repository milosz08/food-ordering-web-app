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
 * Ostatnia modyfikacja: 2022-12-11 20:22:19                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Utils\Utils;
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
     * Metoda uruchamiająca się w przypadku przejścia na adres restaurant/panel/dashboard. 
     */
    public function panel_dashboard()
    {
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
        $restaurant_table = $this->_service->get_user_restaurants();
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
        $add_restaurant_form_data = $this->_service->add_restaurant();
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
        $edit_restaurant_form_data = $this->_service->edit_restaurant();
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
        $details_restaurant_data = $this->_service->get_restaurant_details();
        $this->renderer->render_embed('restaurant/panel-wrapper-view', 'restaurant/panel-restaurant-details-view', array(
            'page_title' => 'Szczegóły restauracji',
            'data' => $details_restaurant_data,
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------
    
    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres restaurant/panel/delete.
     */
    public function panel_myrestaurant_delete()
    {
        $this->_service->delete_restaurant();
        header('Location:' . __URL_INIT_DIR__ . 'restaurant/panel/myrestaurants', true, 301);
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres restaurant/panel/profile.
     */
    public function panel_profile()
    {
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
        $this->renderer->render_embed('restaurant/panel-wrapper-view', 'restaurant/panel-restaurant-orders-view', array(
            'page_title' => 'Zamówienia',
        ));
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
