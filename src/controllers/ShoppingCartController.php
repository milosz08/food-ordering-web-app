<?php


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: ShoppingCartController.php                     *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-11, 22:15:17                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-12 23:13:13                   *
 * Modyfikowany przez: Lukasz Krawczyk                         *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Services\ShoppingCartService;

ResourceLoader::load_service('ShoppingCartService');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class ShoppingCartController extends MvcController
{
 
    private $_service; // instancja serwisu

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
    public function __construct()
    {
        parent::__construct();
		$this->_service = MvcService::get_instance(ShoppingCartService::class); // stworzenie instancji serwisu
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // /shoppingcart/add-dish
    public function add_dish()
    {
        $res_id = $this->_service->add_dish_to_shopping_cart();
        header('Location:' . __URL_INIT_DIR__ . 'restaurants/restaurant-dishes?id=' . $res_id, true, 301);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // /shoppingcart/remove-dish
    public function remove_dish()
    {
        $res_id = $this->_service->remove_dish_from_shopping_cart();
        header('Location:' . __URL_INIT_DIR__ . 'restaurants/restaurant-dishes?id=' . $res_id, true, 301);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    /**
     * Przekierowanie na adres: /restaurants
     */
	public function index()
    {
        header('Location:' . __URL_INIT_DIR__ . 'restaurants');
	}
}
