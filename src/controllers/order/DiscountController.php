<?php

namespace App\Controllers\Order;

use App\Core\MvcController;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Order\DiscountService;

ResourceLoader::load_service('DiscountService', 'Order');

class DiscountController extends MvcController
{
  private $_service; // instancja serwisu

  public function __construct()
  {
    parent::__construct();
    $this->_service = MvcService::get_instance(DiscountService::class); // stworzenie instancji serwisu
  }

  /**
   * Przejście pod adres: /order/discount/add-discount
   */
  public function add_discount()
  {
    $this->protector->protect_only_user();
    $res_id = $this->_service->add_discount();
    header('Location:' . __URL_INIT_DIR__ . 'order/summary?resid=' . $res_id, true, 301);
  }

  /**
   * Przejście pod adres: /order/discount/delete-discount
   */
  public function delete_discount()
  {
    $this->protector->protect_only_user();
    $res_id = $this->_service->delete_discount();
    header('Location:' . __URL_INIT_DIR__ . 'order/summary?resid=' . $res_id, true, 301);
  }

  /**
   * Proxy do adresu: /restaurants
   */
  public function index()
  {
    header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
  }
}
