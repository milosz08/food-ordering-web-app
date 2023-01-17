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
 * Ostatnia modyfikacja: 2023-01-15 22:19:51                   *
 * Modyfikowany przez: BubbleWaffle                            *
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
     * Przejście pod adres: /user/orders/order-details
     */
    public function order_details()
    {
        $this->protector->protect_only_user();
        $service_data = $this->_service->get_user_order_details();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::USER_ORDER_DETAILS_PAGE_BANNER);
        $this->renderer->render('user/order-details-view', array(
            'page_title' => 'Szczegóły zamówienia',
            'data' => $service_data,
            'banner' => $banner_data,
        ));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /user/orders/cancel-order
     */
    public function cancel_order()
    {
        $this->protector->protect_only_user();
        $this->_service->cancel_order();
        header('Location:' . __URL_INIT_DIR__ . 'user/orders', true, 301);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /user/orders
     */
	public function index()
    {
        $this->protector->protect_only_user();
        $orders = $this->_service->get_all_user_orders();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::USER_ORDERS_PAGE_BANNER);
        $this->renderer->render('user/orders-list-view', array(
            'page_title' => 'Moje zamówienia',
            'data' => $orders,
            'banner' => $banner_data,
        ));
	}
}
