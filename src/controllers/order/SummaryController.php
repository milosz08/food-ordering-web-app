<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: SummaryController.php                          *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-13, 04:17:31                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-13 06:51:51                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Order\Services\SummaryService;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service('SummaryService', 'order');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class SummaryController extends MvcController
{
    private $_service; // instancja serwisu

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
    public function __construct()
    {
        parent::__construct();
		$this->_service = MvcService::get_instance(SummaryService::class); // stworzenie instancji serwisu
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /order/summary/cancel-place-order
     */
    public function cancel_place_order()
    {
        $this->protector->protect_only_user();
        $res_id = $this->_service->cancel_place_order();
        header('Location:' . __URL_INIT_DIR__ . 'restaurants/restaurant-dishes?id=' . $res_id, true, 301);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    /**
     * Przejście pod adres: /order/summary/place_order. Adres proxy nie zwraca widoku.
     */
    public function place_order()
    {
        $this->protector->protect_only_user();
        $order_id = $this->_service->place_new_order();
        header('Location:' . __URL_INIT_DIR__ . 'order/summary/new-order-details?id=' . $order_id, true, 301);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    /**
     * Przejście pod adres: /order/summary/new-order-details
     */
    public function new_order_details()
    {
        $this->protector->protect_only_user();
        $data = $this->_service->get_new_order_details();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::NEW_ORDER_DETAILS_PAGE_BANNER);
        $this->renderer->render('order/new-order-details-view', array(
            'page_title' => 'Złożone zamówienie #ID',
            'data' => $data,
            'banner' => $banner_data,
        ));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /order/summary
     */
	public function index()
    {
        $this->protector->protect_only_user();
        $order_summary_data = $this->_service->get_order_summary_and_user_addresses();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ORDER_SUMMARY_PAGE_BANNER);
        $this->renderer->render('order/order-summary-view', array(
            'page_title' => 'Składanie zamówienia',
            'data' => $order_summary_data,
            'banner' => $banner_data,
        ));
	}
}
