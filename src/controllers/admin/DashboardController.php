<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: DashboardController.php                        *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 22:02:17                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:36:33                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Admin\Services\DashboardService;

ResourceLoader::load_service('DashboardService', 'admin'); // ładowanie serwisu przy użyciu require_once

class DashboardController extends MvcController
{
    private $_service; // instancja serwisu

    public function __construct()
    {
        parent::__construct();
        $this->_service = MvcService::get_instance(DashboardService::class); // stworzenie instancji serwisu
    }

    /**
     * Przejście pod adres: /admin/dashboard/graph
     */
    public function graph()
    {
        $this->protector->protect_only_admin();
        header('Content-Type: application/json; charset=UTF-8');
        echo $this->_service->graph();
    }

    /**
     * Przejście pod adres: /admin/dashboard
     */
    public function index()
    {
        $this->protector->protect_only_admin();
        $this->renderer->render_embed('admin-wrapper-view', 'admin/dashboard-view', array(
            'page_title' => 'Panel główny',
            'charts_admin_loadable_content' => true,
        ));
    }
}
