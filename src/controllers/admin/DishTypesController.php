<?php

namespace App\Controllers\Admin;

use App\Core\MvcController;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Admin\DishTypesService;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service('DishTypesService', 'Admin');

class DishTypesController extends MvcController
{
  private $_service; // instancja serwisu

  public function __construct()
  {
    parent::__construct();
    $this->_service = MvcService::get_instance(DishTypesService::class); // stworzenie instancji serwisu
  }

  /**
   * Przejście pod adres: /admin/dish-types/add-dish-type. Proxy dla adresu /admin/dish-types
   */
  public function add_dish_type()
  {
    $this->protector->protect_only_admin();
    $this->_service->add_dish_type();
    header('Location:' . __URL_INIT_DIR__ . 'admin/dish-types', true, 301);
  }

  /**
   * Przejście pod adres: /admin/dish-types/edit-dish-type. Proxy dla adresu /admin/dish-types
   */
  public function edit_dish_type()
  {
    $this->protector->protect_only_admin();
    $this->_service->edit_dish_type();
    header('Location:' . __URL_INIT_DIR__ . 'admin/dish-types', true, 301);
  }

  /**
   * Przejście pod adres: /admin/dish-types/delete-dish-type. Proxy dla adresu /admin/dish-types
   */
  public function delete_dish_type()
  {
    $this->protector->protect_only_admin();
    $this->_service->delete_dish_type();
    header('Location:' . __URL_INIT_DIR__ . 'admin/dish-types', true, 301);
  }

  /**
   * Przejście pod adres: /admin/dish-types
   */
  public function index()
  {
    $this->protector->protect_only_admin();
    $dish_types_data = $this->_service->get_all_default_dish_types();
    $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADMIN_DISH_TYPES_PAGE_BANNER);
    $this->renderer->render_embed('admin-wrapper-view', 'admin/dish-types-view', array(
      'page_title' => 'Domyślne typy potraw',
      'data' => $dish_types_data,
      'banner' => $banner_data,
    ));
  }
}
