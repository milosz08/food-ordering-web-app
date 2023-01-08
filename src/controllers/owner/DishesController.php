<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: DishesController.php                           *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-03, 16:20:48                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-07 00:48:08                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Owner\Services\DishesService;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service('DishesService', 'owner'); // ładowanie serwisu przy użyciu require_once

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class DishesController extends MvcController
{
    private $_service; // instancja serwisu

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
    public function __construct()
    {
        parent::__construct();
		$this->_service = MvcService::get_instance(DishesService::class); // stworzenie instancji serwisu
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /owner/dishes/add-dish-to-restaurant
     */
	public function add_dish_to_restaurant()
    {
        $this->protector->protect_only_owner();
        $restaurants_with_dishes = $this->_service->get_all_restaurants_with_dishes();
        $this->renderer->render_embed('owner-wrapper-view', 'owner/dish/dishes-restaurants-view', array(
            'page_title' => 'Dodaj potrawę do restauracji',
            'data' => $restaurants_with_dishes,
        ));
	}

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /owner/dishes/add-dish
     */
	public function add_dish()
    {
        $this->protector->protect_only_owner();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADD_EDIT_DISH_PAGE_BANNER);
        $add_dish_data = $this->_service->add_dish_to_restaurant();
        if (!$banner_data) $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADD_EDIT_DISH_PAGE_BANNER);
        $this->renderer->render_embed('owner-wrapper-view', 'owner/dish/add-edit-dish-view', array(
            'page_title' => 'Dodaj potrawę',
            'add_edit_text' => 'Dodaj',
            'data' => $add_dish_data,
            'banner' => $banner_data,
        ));
	}

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /owner/dishes/edit-dish
     */
	public function edit_dish()
    {
        $this->protector->protect_only_owner();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADD_EDIT_DISH_PAGE_BANNER);
        $edit_dish_data = $this->_service->edit_dish_from_restaurant();
        if (!$banner_data) $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADD_EDIT_DISH_PAGE_BANNER);
        $this->renderer->render_embed('owner-wrapper-view', 'owner/dish/add-edit-dish-view', array(
            'page_title' => 'Edytuj potrawę',
            'add_edit_text' => 'Edytuj',
            'data' => $edit_dish_data,
            'banner' => $banner_data,
        ));
	}

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /owner/dishes/dish-details
     */
	public function dish_details()
    {
        $this->protector->protect_only_owner();
        $dish_details = $this->_service->get_dish_details();
        $this->renderer->render_embed('owner-wrapper-view', 'owner/dish/dish-details-view', array(
            'page_title' => 'Szczegóły potrawy',
            'data' => $dish_details,
        ));
	}

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /owner/dishes/delete_dish_image
     */
    public function delete_dish_image()
    {
        $this->protector->protect_only_owner();
        $deleted_data = $this->_service->delete_dish_image();
        header('Location:' . __URL_INIT_DIR__ . $deleted_data['redirect_path'], true, 301);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /owner/dishes/delete-dish
     */
	public function delete_dish()
    {
        $this->protector->protect_only_owner();
        $delete_dish_details = $this->_service->delete_dish_from_restaurant();
        header('Location:' . __URL_INIT_DIR__ . 'owner/restaurants/restaurant-details?id=' . $delete_dish_details['restaurant_id'], true, 301);
	}

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /owner/dishes
     */
	public function index()
    {
        $this->protector->protect_only_owner();
        $all_dishes = $this->_service->get_all_dishes();
        $this->renderer->render_embed('owner-wrapper-view', 'owner/dish/dishes-view', array(
            'page_title' => 'Moje potrawy',
            'data' => $all_dishes,
        ));
	}
}