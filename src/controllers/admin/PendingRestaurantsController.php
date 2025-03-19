<?php

namespace App\Controllers\Admin;

use App\Core\MvcController;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Admin\PendingRestaurantsService;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service('PendingRestaurantsService', 'Admin'); // ładowanie serwisu przy użyciu require_once

class PendingRestaurantsController extends MvcController
{
  private $_service; // instancja serwisu

  public function __construct()
  {
    parent::__construct();
    $this->_service = MvcService::get_instance(PendingRestaurantsService::class); // stworzenie instancji serwisu
  }

  /**
   * Przejście pod adres: /admin/pending-restaurants/accept
   */
  public function accept()
  {
    $this->protector->protect_only_admin();
    $this->_service->accept_restaurant();
    header('Location:' . __URL_INIT_DIR__ . 'admin/pending-restaurants', true, 301);
  }

  /**
   * Przejście pod adres: /admin/pending-restaurants/reject
   */
  public function reject()
  {
    $this->protector->protect_only_admin();
    $this->_service->reject_restaurant();
    header('Location:' . __URL_INIT_DIR__ . 'admin/pending-restaurants', true, 301);
  }

  /**
   * Przejście pod adres: /admin/pending-restaurants
   */
  public function index()
  {
    $this->protector->protect_only_admin();
    $details_restaurant_data = $this->_service->get_pending_restaurants();
    $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADMIN_PENDING_RESTAURANTS_PAGE_BANNER);
    $this->renderer->render_embed('admin-wrapper-view', 'admin/restaurants/pending-restaurants-view', array(
      'page_title' => 'Oczekujące restauracje',
      'banner' => $banner_data,
      'data' => $details_restaurant_data,
    ));
  }
}
