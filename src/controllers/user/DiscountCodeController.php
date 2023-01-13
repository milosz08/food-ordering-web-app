<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: DiscountCodeController.php                     *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-12, 23:32:28                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-13 00:31:29                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\User\Services\DiscountCodeService;

ResourceLoader::load_service('DiscountCodeService', 'user');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class DiscountCodeController extends MvcController
{
    private $_service; // instancja serwisu

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
    public function __construct()
    {
        parent::__construct();
		$this->_service = MvcService::get_instance(DiscountCodeService::class); // stworzenie instancji serwisu
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /user/discount-code/add
     */
    public function add()
    {
        $res_id = $this->_service->add_discount();
        header('Location:' . __URL_INIT_DIR__ . 'user/orders/order-summary?resid=' . $res_id, true, 301);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /user/discount-code/delete
     */
    public function delete()
    {
        $res_id = $this->_service->delete_discount();
        header('Location:' . __URL_INIT_DIR__ . 'user/orders/order-summary?resid=' . $res_id, true, 301);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Proxy do adresu: /restaurants
     */
	public function index()
    {
        header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
	}
}
