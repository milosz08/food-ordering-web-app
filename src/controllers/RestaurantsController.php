<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: RestaurantsController.php                      *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 21:40:28                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-13 01:07:22                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Services\RestaurantsService;
use App\Services\Helpers\CookieHelper;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service('RestaurantsService'); // ładowanie serwisu przy użyciu require_once

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
     * Przejście pod adres: /restaurants/restaurant-dishes
     */
	public function restaurant_dishes()
    {
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ORDER_SUMMARY_PAGE);
        $res_details = $this->_service->get_restaurant_dishes_with_cart();
        $this->renderer->render('restaurants/restaurant-dishes-view', array(
            'page_title' => $res_details['res_details']['name'] ?? 'Potrawy restauracji',
            'data' => $res_details,
            'banner' => $banner_data,
        ));
	}

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function add_dish()
    {
        $res_id = $this->_service->addDishToShoppingCard();
        header('Location:' . __URL_INIT_DIR__ . 'restaurants/restaurant-dishes?id=' . $res_id, true, 301);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    /**
     * Przejście pod adres: /restaurants/clear-filters
     */
    public function clear_filters()
    {
        CookieHelper::delete_cookie(CookieHelper::RESTAURANT_FILTERS);
        SessionHelper::create_session_banner(SessionHelper::HOME_RESTAURANTS_LIST_PAGE_BANNER, 
            'Filtry zostały pomyślnie wyczyszczone.', false);
        header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /restaurants
     */
	public function index()
    {
        $res_list = $this->_service->get_all_accepted_restaurants();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::HOME_RESTAURANTS_LIST_PAGE_BANNER);
        $this->renderer->render('restaurants/all-restaurants-view', array(
            'page_title' => 'Restauracje',
            'banner' => $banner_data,
            'data' => $res_list,
        ));
	}
}
