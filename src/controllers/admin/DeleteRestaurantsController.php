<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: DeleteRestaurantsController.php                *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-12, 17:38:57                       *
 * Autor: BubbleWaffle                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-15 15:43:08                   *
 * Modyfikowany przez: patrick012016                           *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;
use App\Admin\Services\DeleteRestaurantsService;

ResourceLoader::load_service('DeleteRestaurantsService', 'admin'); // ładowanie serwisu przy użyciu require_once

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class DeleteRestaurantsController extends MvcController
{
    private $_service; // instancja serwisu

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct()
    {
        parent::__construct();
        $this->_service = MvcService::get_instance(DeleteRestaurantsService::class); // stworzenie instancji serwisu
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /admin/delete-restaurants/details
     */
    public function details()
    {
        $this->protector->protect_only_admin();
        $restaurant_details = $this->_service->get_details();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::RESTAURANT_DETAILS_PAGE_BANNER);
        $this->renderer->render_embed('admin-wrapper-view', 'admin/restaurant-admin-details-view', array(
            'page_title' => 'Szczegóły restauracji',
            'is_details_subpage' => true,
            'banner' => $banner_data,
            'data' => $restaurant_details,
        ));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /admin/delete-restaurants/remove
     */
    public function remove()
    {
        $this->protector->protect_only_admin();
        $this->_service->delete_restaurant();
        header('Location:' . __URL_INIT_DIR__ . 'admin/delete-restaurants', true, 301);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /admin/delete-restaurants
     */
    public function index()
    {
        $this->protector->protect_only_admin();
        $restaurant_table = $this->_service->get_restaurants();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADMIN_DELETE_RESTAURANT_BANNER);
        $this->renderer->render_embed('admin-wrapper-view', 'admin/delete-restaurants-view', array(
            'page_title' => 'Usuwanie restauracji',
            'banner' => $banner_data,
            'data' => $restaurant_table,
        ));
    }
}
