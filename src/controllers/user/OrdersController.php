<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: OrdersController.php                           *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 21:01:58                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-07 00:51:17                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\User\Services\OrdersService;

ResourceLoader::load_service('OrdersService', 'user'); // ładowanie serwisu przy użyciu require_once

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class OrdersController extends MvcController
{
    private $_service; // instancja serwisu

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
    public function __construct()
    {
        parent::__construct();
		$this->_service = MvcService::get_instance(OrdersService::class); // stworzenie instancji serwisu
    }

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    /**
     * Przejście pod adres: /user/orders
     */
	public function index()
    {
        $this->protector->protect_only_user();
        $this->renderer->render('user/orders-view', array(
            'page_title' => 'Zamówienia',
        ));
	}
}
