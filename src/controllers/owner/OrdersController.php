<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: OrdersController.php                           *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-03, 02:13:39                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:42:41                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Owner\Services\OrdersService;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service('OrdersService', 'owner'); // ładowanie serwisu przy użyciu require_once

class OrdersController extends MvcController
{
    private $_service; // instancja serwisu

    public function __construct()
    {
        parent::__construct();
		$this->_service = MvcService::get_instance(OrdersService::class); // stworzenie instancji serwisu
    }

    /**
     * Przejście pod adres: /owner/orders/status
     */
    public function status()
    {
        $this->protector->protect_only_owner();
        $this->_service->order_change();
        header('Location:' . __URL_INIT_DIR__ . 'owner/orders', true, 301);
    }

    /**
     * Przejście pod adres: /owner/orders/order-details
     */
    public function order_details()
    {
        $this->protector->protect_only_owner();
        $order_details_data = $this->_service->get_order_details();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::OWNER_ORDER_DETAILS_PAGE_BANNER);
        $this->renderer->render_embed('owner-wrapper-view', 'owner/orders/order-details-view', array(
            'page_title' => 'Szczegóły zamówienia #' . $order_details_data['order_id'],
            'data' => $order_details_data,
            'banner' => $banner_data,
        ));
    }

    /**
     * Przejście pod adres: /owner/orders
     */
	public function index()
    {
        $this->protector->protect_only_owner();
        $orders_details = $this->_service->get_orders();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::OWNER_ORDERS_PAGE_BANNER);
        $this->renderer->render_embed('owner-wrapper-view', 'owner/orders/orders-view', array(
            'page_title' => 'Zamówienia',
            'data' => $orders_details,
            'banner' => $banner_data,
        ));
	}
}
