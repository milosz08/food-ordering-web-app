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
 * Ostatnia modyfikacja: 2023-01-08 01:10:19                   *
 * Modyfikowany przez: Lukasz Krawczyk                         *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\User\Services\OrdersService;
use App\Services\Helpers\SessionHelper;

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
     * Przejście pod adres: /user/orders/dashboard/orders
     */
    public function dashboard_orders()
    {
        $this->renderer->render('user/orders-list-view', array(
            'page_title' => 'Twoje zamówienia',
        ));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /user/orders/dashboard/single/order
     */
    public function dashboard_single_order()
    {
        $this->renderer->render('user/single-order-view', array(
            'page_title' => 'Szczegóły zamówienia',
        ));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /user/orders/order-finish
     */
    public function order_finish()
    {
        $this->protector->protect_only_user();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ORDER_FINISH_PAGE);
        if (!$banner_data) $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ORDER_FINISH_PAGE);
        $adress = $this->_service->fillAdress();
        $delete_discount_code = $this->_service->deleteDiscountCode();
        $this->renderer->render('user/orders-view', array(
            'page_title' => 'Składanie zamówienia',
            'form' => $adress,
            'banner' => $banner_data,
            'discount' => $delete_discount_code
        ));
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
