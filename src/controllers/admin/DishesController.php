<?php

namespace App\Controllers\Admin;

use App\Core\MvcController;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Admin\DishesService;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service('DishesService', 'Admin');
ResourceLoader::load_service_helper('SessionHelper');

class DishesController extends MvcController
{
  private $_service; // instancja serwisu

  public function __construct()
  {
    parent::__construct();
    $this->_service = MvcService::get_instance(DishesService::class); // stworzenie instancji serwisu
  }

  /**
   * Przejście pod adres: /admin/dishes/delete-dish
   */
  public function delete_dish()
  {
    $this->protector->protect_only_admin();
    $res_id = $this->_service->delete_dish();
    header('Location:' . __URL_INIT_DIR__ . 'admin/restaurants/restaurant-details?id=' . $res_id, true, 301);
  }

  /**
   * Przejście pod adres: /admin/dishes/delete-dish-image
   */
  public function delete_dish_image()
  {
    $this->protector->protect_only_admin();
    $redirect_path = $this->_service->delete_dish_image();
    header('Location:' . __URL_INIT_DIR__ . $redirect_path, true, 301);
  }

  /**
   * Przejście pod adres: /admin/dishes/dish-details
   */
  public function dish_details()
  {
    $this->protector->protect_only_admin();
    $dish_details = $this->_service->get_dish_details();
    $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADMIN_DISH_DETAILS_PAGE_BANNER);
    $this->renderer->render_embed('admin-wrapper-view', 'admin/dish-details-view', array(
      'page_title' => 'Szczegóły potrawy',
      'data' => $dish_details,
      'banner' => $banner_data,
    ));
  }

  /**
   * Przejście pod adres: /admin/dishes. Proxy pod adres /admin/dishes/dish-details
   */
  public function index()
  {
    $this->protector->protect_only_admin();
    header('Location:' . __URL_INIT_DIR__ . 'admin/dish-details', true, 301);
  }
}
