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
 * Ostatnia modyfikacja: 2022-12-06 17:42:32                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

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
     * Metoda uruchamiająca się w przypadku przejścia na adres index.php?action=admin/panel/dashboard. 
     */
    public function panel_dashboard()
    {
        $this->renderer->render_embed('admin/panel-wrapper-view', 'admin/panel-dashboard-view', array(
            'page_title' => 'Panel administratora',
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiana, kiedy użytkownik w ścieżce zapytania poda jedynie nazwę kontrolera, czyli jak ścieżka jest mniej więcej taka:
     *      index.php?action=admin
     * Metoda przekierowuje użytkownika na adres:
     *      index.php?action=admin/panel/dashbaord
     * renderując widok z metody panel_dashboard() powyższej klasy.
     */
	public function index()
    {
        header('Location:index.php?action=admin/panel/dashboard');
	}
}
