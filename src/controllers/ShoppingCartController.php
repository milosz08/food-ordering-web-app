<?php

namespace App\Controllers;

use App\Core\MvcController;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\ShoppingCartService;

ResourceLoader::load_service('ShoppingCartService');

class ShoppingCartController extends MvcController
{
  private $_service; // instancja serwisu

  public function __construct()
  {
    parent::__construct();
    $this->_service = MvcService::get_instance(ShoppingCartService::class); // stworzenie instancji serwisu
  }

  /**
   * Przekierowanie na adres: /shopping-cart/add-dish
   */
  public function add_dish()
  {
    $res_id = $this->_service->add_dish_to_shopping_cart();
    header('Location:' . __URL_INIT_DIR__ . 'restaurants/restaurant-dishes?id=' . $res_id, true, 301);
  }

  /**
   * Przekierowanie na adres: /shopping-cart/delete-dish
   */
  public function delete_dish()
  {
    $res_id = $this->_service->delete_dish_from_shopping_cart();
    header('Location:' . __URL_INIT_DIR__ . 'restaurants/restaurant-dishes?id=' . $res_id, true, 301);
  }

  /**
   * Przekierowanie na adres: /shopping-cart/delete-all
   */
  public function delete_all()
  {
    $res_id = $this->_service->delete_all_dishes_from_shopping_cart();
    header('Location:' . __URL_INIT_DIR__ . 'restaurants/restaurant-dishes?id=' . $res_id, true, 301);
  }

  /**
   * Przekierowanie na adres: /restaurants
   */
  public function index()
  {
    header('Location:' . __URL_INIT_DIR__ . 'restaurants');
  }
}
