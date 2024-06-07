<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: RestaurantsController.php                      *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-12, 17:38:57                       *
 * Autor: BubbleWaffle                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:38:05                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;
use App\Admin\Services\RestaurantsService;

ResourceLoader::load_service('RestaurantsService', 'admin'); // ładowanie serwisu przy użyciu require_once

class RestaurantsController extends MvcController
{
    private $_service; // instancja serwisu

    public function __construct()
    {
        parent::__construct();
        $this->_service = MvcService::get_instance(RestaurantsService::class); // stworzenie instancji serwisu
    }

    /**
     * Przejście pod adres: /admin/restaurants/delete-restaurant. Proxy pod adres: /admin/restaurants
     */
    public function delete_restaurant()
    {
        $this->protector->protect_only_admin();
        $this->_service->delete_restaurant();
        header('Location:' . __URL_INIT_DIR__ . 'admin/restaurants', true, 301);
    }

    /**
     * Przejście pod adres: /admin/restaurants/delete-banner-image
     */
    public function delete_banner_image()
    {
        $this->protector->protect_only_admin();
        $redirect_url = $this->_service->delete_restaurant_image('banner_url', 'zdjęcie w tle', 'banner');
        header('Location:' . __URL_INIT_DIR__ . $redirect_url,  true, 301);
    }

    /**
     * Przejście pod adres: /admin/restaurants/delete-profile-image
     */
    public function delete_profile_image()
    {
        $this->protector->protect_only_admin();
        $redirect_url = $this->_service->delete_restaurant_image('profile_url', 'zdjęcie profilowe', 'profile');
        header('Location:' . __URL_INIT_DIR__ . $redirect_url, true, 301);
    }

    /**
     * Przejście pod adres: /admin/restaurants/restaurant-details
     */
    public function restaurant_details()
    {
        $this->protector->protect_only_admin();
        $restaurant_details = $this->_service->get_restaurant_details();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADMIN_RESTAURANT_DETAILS_PAGE_BANNER);
        $this->renderer->render_embed('admin-wrapper-view', 'admin/restaurants/restaurant-details-view', array(
            'page_title' => 'Szczegóły restauracji #' . $restaurant_details['res_id'],
            'data' => $restaurant_details,
            'banner' => $banner_data,
            'is_details_subpage' => true,
        ));
    }

    /**
     * Przejście pod adres: /admin/restaurants
     */
    public function index()
    {
        $this->protector->protect_only_admin();
        $restaurants_data = $this->_service->get_restaurants();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADMIN_RESTAURANTS_PAGE_BANNER);
        $this->renderer->render_embed('admin-wrapper-view', 'admin/restaurants/restaurants-view', array(
            'page_title' => 'Wszystkie restauracje',
            'banner' => $banner_data,
            'data' => $restaurants_data,
        ));
    }
}
