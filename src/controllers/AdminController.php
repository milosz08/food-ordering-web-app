<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: AdminController.php                            *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-12-06, 15:19:53                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2022-12-17 00:15:55                   *
 * Modyfikowany przez: patrick012016                           *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Utils\Utils;
use App\Core\MvcController;
use App\Services\AdminService;

class AdminController extends MvcController
{
    private $_service;

    //--------------------------------------------------------------------------------------------------------------------------------------

    public function __construct()
    {
        // Wywołanie konstruktora z klasy MvcController. Każda klasa kontrolera musi wywoływać konstruktor klasy nadrzędniej!
        parent::__construct();
        $this->_service = AdminService::get_instance(AdminService::class); // pobranie instancji klasy AdminService
    }
    
    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres admin/panel/dashboard. 
     */
    public function panel_dashboard()
    {
        $this->renderer->render_embed('admin/panel-wrapper-view', 'admin/panel-dashboard-view', array(
            'page_title' => 'Panel administratora',
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres admin/panel/profile. 
     */
    public function panel_profile()
    {
        $this->renderer->render_embed('admin/panel-wrapper-view', 'admin/panel-profile-view', array(
            'page_title' => 'Profil administratora',
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres admin/panel/settings. 
     */
    public function panel_settings()
    {
        $this->renderer->render_embed('admin/panel-wrapper-view', 'admin/panel-settings-view', array(
            'page_title' => 'Ustawienia',
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres admin/panel/restaurant/accept. 
     */
    public function panel_restaurant_accept()
    {
        $details_restaurant_data = $this->_service->show_accept_restaurants();
        $mainpulate_restaurant_banner = Utils::check_session_and_unset('manipulate_restaurant_banner');
        $this->renderer->render_embed('admin/panel-wrapper-view', 'admin/panel-accept-restaurant-view', array(
            'page_title' => 'Akceptowanie restauracji',
            'banner' => $mainpulate_restaurant_banner,
            'data' => $details_restaurant_data,
        ));
    }
        
    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres admin/panel/restaurant/accept/restaurant. 
     */
    public function panel_restaurant_accept_restaurant()
    {
        $this->_service->accept_restaurant();
        header('Location:' . __URL_INIT_DIR__ . 'admin/panel/restaurant/accept', true, 301);
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres admin/panel/restaurant/accept/restaurant/reject. 
     */
    public function panel_restaurant_accept_restaurant_reject()
    {
        $this->_service->reject_restaurant();
        header('Location:' . __URL_INIT_DIR__ . 'admin/panel/restaurant/accept', true, 301);
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiana, kiedy użytkownik w ścieżce zapytania poda jedynie nazwę kontrolera, czyli jak ścieżka jest mniej więcej taka:
     *      admin
     * Metoda przekierowuje użytkownika na adres:
     *      admin/panel/dashbaord
     * renderując widok z metody panel_dashboard() powyższej klasy.
     */
	public function index()
    {
        header('Location:' . __URL_INIT_DIR__ . 'admin/panel/dashboard');
	}
}
