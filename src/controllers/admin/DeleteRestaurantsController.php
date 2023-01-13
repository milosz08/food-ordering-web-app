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
 * Ostatnia modyfikacja: 2023-01-13 08:39:14                   *
 * Modyfikowany przez: Miłosz Gilga                            *
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

    public function remove()
    {
        $this->protector->protect_only_admin();
        $this->_service->delete_restaurant();
        header('Location:' . __URL_INIT_DIR__ . 'admin/delete-restaurants', true, 301);
    }

    /**
     * Przejście pod adres: /admin/delete-restaurants
     */
    public function index()
    {
        $this->protector->protect_only_admin();
        $restaurant_table = $this->_service->get_restaurants();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::RESTAURANTS_PAGE_BANNER);
        $this->renderer->render_embed('admin-wrapper-view', 'admin/delete-restaurants-view', array(
            'page_title' => 'Usuwanie restauracji',
            'banner' => $banner_data,
            'data' => $restaurant_table,
        ));
    }
}
