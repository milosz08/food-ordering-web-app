<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: RatingsController.php                          *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-14, 07:45:57                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:43:01                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Owner\Services\RatingsService;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service('RatingsService', 'owner');

class RatingsController extends MvcController
{
    private $_service; // instancja serwisu

    public function __construct()
    {
        parent::__construct();
		$this->_service = MvcService::get_instance(RatingsService::class); // stworzenie instancji serwisu
    }

    /**
     * Przejście pod adres: /owner/ratings/request-for-delete
     * Proxy pod adres: /owner/ratings/pending-to-delete
     */
    public function request_for_delete()
    {
        $this->protector->protect_only_owner();
        $this->_service->send_request_to_delete_rating();
        header('Location:' . __URL_INIT_DIR__ . 'owner/ratings', true, 301);
    }

    /**
     * Przejście pod adres: /owner/ratings/pending-to-delete
     */
    public function pending_to_delete()
    {
        $this->protector->protect_only_owner();
        $pending_delete_ratings_data = $this->_service->get_pending_to_delete_ratings();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::OWNER_RATINGS_PENDING_TO_DELETE);
        $this->renderer->render_embed('owner-wrapper-view', 'owner/ratings/pending-to-delete-ratings-view', array(
            'page_title' => 'Oceny oczekujące na usunięcie',
            'data' => $pending_delete_ratings_data,
            'banner' => $banner_data,
        ));
    }

    /**
     * Przejście pod adres: /owner/ratings/delete-pending
     * Proxy pod adres: /owner/ratings/pending-to-delete
     */
    public function delete_pending()
    {
        $this->protector->protect_only_owner();
        $this->_service->delete_pending();
        header('Location:' . __URL_INIT_DIR__ . 'owner/ratings/pending-to-delete', true, 301);
    }

    /**
     * Przejście pod adres: /owner/ratings
     */
	public function index()
    {
        $this->protector->protect_only_owner();
        $ratings_data = $this->_service->get_restaurants_ratings();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::OWNER_RATINGS_PAGE_BANNER);
        $this->renderer->render_embed('owner-wrapper-view', 'owner/ratings/ratings-view', array(
            'page_title' => 'Oceny restauracji',
            'data' => $ratings_data,
            'banner' => $banner_data,
        ));
	}
}
