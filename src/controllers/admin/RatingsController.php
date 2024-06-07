<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: RatingsController.php                          *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-15, 01:58:31                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:37:51                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Admin\Services\RatingsService;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service('RatingsService', 'admin');

class RatingsController extends MvcController
{
    private $_service; // instancja serwisu

    public function __construct()
    {
        parent::__construct();
        $this->_service = MvcService::get_instance(RatingsService::class); // stworzenie instancji serwisu
    }

    /**
     * Przejście pod adres: admin/ratings/accept-delete-rating. Proxy pod adres: /admin/ratings/pending-to-delete
     */
    public function accept_delete_rating()
    {
        $this->protector->protect_only_admin();
        $this->_service->accept_pending_delete_rating();
        header('Location:' . __URL_INIT_DIR__ . 'admin/ratings/pending-to-delete', true, 301);
    }

    /**
     * Przejście pod adres: admin/ratings/accept-delete-rating. Proxy pod adres: /admin/ratings/pending-to-delete
     */
    public function reject_delete_rating()
    {
        $this->protector->protect_only_admin();
        $this->_service->reject_pending_delete_rating();
        header('Location:' . __URL_INIT_DIR__ . 'admin/ratings/pending-to-delete', true, 301);
    }

    /**
     * Przejście pod adres: admin/ratings/delete-rating. Proxy pod adres: /admin/ratings
     */
    public function delete_rating()
    {
        $this->protector->protect_only_admin();
        $this->_service->delete_rating();
        header('Location:' . __URL_INIT_DIR__ . 'admin/ratings', true, 301);
    }

    /**
     * Przejście pod adres: /admin/ratings/pending-to-delete
     */
    public function pending_to_delete()
    {
        $this->protector->protect_only_admin();
        $pending_ratings_data = $this->_service->get_all_rating_from_pending_to_delete();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADMIN_RATINGS_PENDING_TO_DELETE);
        $this->renderer->render_embed('admin-wrapper-view', 'admin/ratings/pending-to-delete-ratings-view', array(
            'page_title' => 'Oceny oczekujące na usunięcie',
            'data' => $pending_ratings_data,
            'banner' => $banner_data,
        ));
    }

    /**
     * Przejście pod adres: /admin/ratings
     */
    public function index()
    {
        $this->protector->protect_only_admin();
        $ratings_data = $this->_service->get_all_ratings_from_all_restaurants();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADMIN_RATINGS_PAGE_BANNER);
        $this->renderer->render_embed('admin-wrapper-view', 'admin/ratings/ratings-view', array(
            'page_title' => 'Oceny restauracji',
            'data' => $ratings_data,
            'banner' => $banner_data,
        ));
    }
}
