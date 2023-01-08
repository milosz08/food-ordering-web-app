<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: RestaurantsController.php                      *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-03, 00:04:05                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-07 00:48:31                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;
use App\Owner\Services\RestaurantsService;

ResourceLoader::load_service('RestaurantsService', 'owner'); // ładowanie serwisu przy użyciu require_once

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class RestaurantsController extends MvcController
{
    private $_service; // instancja serwisu

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
    public function __construct()
    {
        parent::__construct();
		$this->_service = MvcService::get_instance(RestaurantsService::class); // stworzenie instancji serwisu
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /owner/restaurants/edit-restaurant
     */
    public function add_restaurant()
    {
        $this->protector->protect_only_owner();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADD_EDIT_RESTAURANT_PAGE_BANNER);
        $add_restaurant_data = $this->_service->add_restaurant();
        if (!$banner_data) $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADD_EDIT_RESTAURANT_PAGE_BANNER);
        $this->renderer->render_embed('owner-wrapper-view', 'owner/restaurants/add-edit-restaurant-view', array(
            'page_title' => 'Dodaj restaurację',
            'add_edit_text' => 'Dodaj',
            'data' => $add_restaurant_data,
            'banner' => $banner_data,
        ));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /owner/restaurants/edit-restaurant
     */
    public function edit_restaurant()
    {
        $this->protector->protect_only_owner();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADD_EDIT_RESTAURANT_PAGE_BANNER);
        $edit_restaurant_data = $this->_service->edit_restaurant();
        if (!$banner_data) $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADD_EDIT_RESTAURANT_PAGE_BANNER);
        $this->renderer->render_embed('owner-wrapper-view', 'owner/restaurants/add-edit-restaurant-view', array(
            'page_title' => 'Edytuj restaurację',
            'add_edit_text' => 'Edytuj',
            'data' => $edit_restaurant_data,
            'banner' => $banner_data,
        ));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /owner/restaurants/delete-restaurant
     */
    public function delete_restaurant()
    {
        $this->protector->protect_only_owner();
        $this->_service->delete_restaurant();
        header('Location:' . __URL_INIT_DIR__ . 'owner/restaurants', true, 301);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /owner/restaurants/delete-restaurant-banner
     */
    public function delete_restaurant_banner()
    {
        $this->protector->protect_only_owner();
        $redirect_url = $this->_service->delete_restaurant_image('banner_url', 'zdjęcie w tle');
        var_dump($redirect_url);
        header('Location:' . __URL_INIT_DIR__ . $redirect_url,  true, 301);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /owner/restaurants/delete-restaurant-profile
     */
    public function delete_restaurant_profile()
    {
        $this->protector->protect_only_owner();
        $redirect_url = $this->_service->delete_restaurant_image('profile_url', 'zdjęcie profilowe');
        header('Location:' . __URL_INIT_DIR__ . $redirect_url, true, 301);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /owner/restaurants/restaurant-details
     */
    public function restaurant_details()
    {
        $this->protector->protect_only_owner();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::RESTAURANT_DETAILS_PAGE_BANNER);
        $restaurant_details = $this->_service->get_restaurant_details();
        if (!$banner_data) $banner_data = SessionHelper::check_session_and_unset(SessionHelper::RESTAURANT_DETAILS_PAGE_BANNER);
        $this->renderer->render_embed('owner-wrapper-view', 'owner/restaurants/restaurant-details-view', array(
            'page_title' => 'Szczegóły restauracji',
            'is_details_subpage' => true,
            'banner' => $banner_data,
            'data' => $restaurant_details,
        ));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /owner/restaurants
     */
	public function index()
    {
        $this->protector->protect_only_owner();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::RESTAURANTS_PAGE_BANNER);
        $restaurant_table = $this->_service->get_user_restaurants();
        if (!$banner_data) $banner_data = SessionHelper::check_session_and_unset(SessionHelper::RESTAURANTS_PAGE_BANNER);
        $this->renderer->render_embed('owner-wrapper-view', 'owner/restaurants/restaurants-view', array(
            'page_title' => 'Moje restauracje',
            'banner' => $banner_data,
            'data' => $restaurant_table,
        ));
	}
}
