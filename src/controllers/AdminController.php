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
 * Ostatnia modyfikacja: 2022-12-16 02:38:09                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcController;
use App\Services\AdminService;
use App\Models\AcceptationModel;

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
     * Metoda uruchamiająca się w przypadku przejścia na adres admin/panel/settings. 
     */
    public function panel_restaurant_accept()
    {
        ///////////////////////////////////////////////////////// tylko do testów
        $test_res = new AcceptationModel();
        $test_res->id = 1;
        $test_res->full_name = "Jan Kowalski";
        $test_res->name = "TESTIFICATE REST";
        $test_res->address = "ul. Długa 93, 43-100 Gliwice";
        ///////////////////////////////////////////////////////// tylko do testów
        $this->renderer->render_embed('admin/panel-wrapper-view', 'admin/panel-accept-restaurant-view', array(
            'page_title' => 'Akceptowanie restauracji',
            'waiting_restaurants' => array(
                array('res' => $test_res, 'iterator' => 1),
            ),
        ));
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
