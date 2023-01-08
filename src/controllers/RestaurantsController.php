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
 * Ostatnia modyfikacja: 2023-01-07 19:20:53                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Services\RestaurantsService;
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
     * Przejście pod adres: /restaurants/restaurant-details
     */
	public function restaurant_details()
    {
        $this->renderer->render('restaurants/restaurant-details-view', array(
            'page_title' => '[[nazwa restauracji]]',
        ));
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
