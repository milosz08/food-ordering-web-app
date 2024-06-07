<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: DiscountController.php                         *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-12, 23:32:28                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:40:48                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Order\Services\DiscountService;

ResourceLoader::load_service('DiscountService', 'order');

class DiscountController extends MvcController
{
    private $_service; // instancja serwisu

    public function __construct()
    {
        parent::__construct();
		$this->_service = MvcService::get_instance(DiscountService::class); // stworzenie instancji serwisu
    }

    /**
     * Przejście pod adres: /order/discount/add-discount
     */
    public function add_discount()
    {
        $this->protector->protect_only_user();
        $res_id = $this->_service->add_discount();
        header('Location:' . __URL_INIT_DIR__ . 'order/summary?resid=' . $res_id, true, 301);
    }

    /**
     * Przejście pod adres: /order/discount/delete-discount
     */
    public function delete_discount()
    {
        $this->protector->protect_only_user();
        $res_id = $this->_service->delete_discount();
        header('Location:' . __URL_INIT_DIR__ . 'order/summary?resid=' . $res_id, true, 301);
    }

    /**
     * Proxy do adresu: /restaurants
     */
	public function index()
    {
        header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
	}
}
