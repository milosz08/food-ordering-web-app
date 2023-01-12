<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: PendingRestaurantsController.php               *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 22:49:58                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-12 03:12:05                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;
use App\Admin\Services\PendingRestaurantsService;

ResourceLoader::load_service('PendingRestaurantsService', 'admin'); // ładowanie serwisu przy użyciu require_once

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class PendingRestaurantsController extends MvcController
{
    private $_service; // instancja serwisu

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
    public function __construct()
    {
        parent::__construct();
		$this->_service = MvcService::get_instance(PendingRestaurantsService::class); // stworzenie instancji serwisu
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /admin/pending-restaurants/accept
     */
    public function accept()
    {
        $this->protector->protect_only_admin();
        $this->_service->accept_restaurant();
        header('Location:' . __URL_INIT_DIR__ . 'admin/pending-restaurants', true, 301);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /admin/pending-restaurants/reject
     */
    public function reject()
    {
        $this->protector->protect_only_admin();
        $this->_service->reject_restaurant();
        header('Location:' . __URL_INIT_DIR__ . 'admin/pending-restaurants', true, 301);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /admin/pending-restaurants
     */
	public function index()
    {
        $this->protector->protect_only_admin();
        $details_restaurant_data = $this->_service->get_pending_restaurants();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::PENDING_RESTAURANT_PAGE_BANNER);
        $this->renderer->render_embed('admin-wrapper-view', 'admin/pending-restaurants-view', array(
            'page_title' => 'Oczekujące restauracje',
            'banner' => $banner_data,
            'data' => $details_restaurant_data,
        ));
	}
}
