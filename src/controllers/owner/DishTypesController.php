<?php

namespace App\Controllers\Owner;

use App\Core\MvcController;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;
use App\Services\Owner\DishTypesService;

ResourceLoader::load_service('DishTypesService', 'Owner');

class DishTypesController extends MvcController
{
  private $_service; // instancja serwisu

  public function __construct()
  {
    parent::__construct();
    $this->_service = MvcService::get_instance(DishTypesService::class); // stworzenie instancji serwisu
  }

  /**
   * Przejście pod adres: /owner/dish-types/add-dish-type. Proxy dla adresu /admin/dish-types
   */
  public function add_dish_type()
  {
    $this->protector->protect_only_owner();
    $this->_service->add_dish_type();
    header('Location:' . __URL_INIT_DIR__ . 'owner/dish-types', true, 301);
  }

  /**
   * Przejście pod adres: /owner/dish-types/edit-dish-type. Proxy dla adresu /owner/dish-types
   */
  public function edit_dish_type()
  {
    $this->protector->protect_only_owner();
    $this->_service->edit_dish_type();
    header('Location:' . __URL_INIT_DIR__ . 'owner/dish-types', true, 301);
  }

  /**
   * Przejście pod adres: /owner/dish-types/delete-dish-type. Proxy dla adresu /owner/dish-types
   */
  public function delete_dish_type()
  {
    $this->protector->protect_only_owner();
    $this->_service->delete_dish_type();
    header('Location:' . __URL_INIT_DIR__ . 'owner/dish-types', true, 301);
  }

  /**
   * Przejście pod adres: /owner/dish-types
   */
  public function index()
  {
    $this->protector->protect_only_owner();
    $dish_types_data = $this->_service->get_all_default_dish_types();
    $banner_data = SessionHelper::check_session_and_unset(SessionHelper::OWNER_DISH_TYPES_PAGE_BANNER);
    $this->renderer->render_embed('owner-wrapper-view', 'owner/dish-types-view', array(
      'page_title' => 'Moje typy potraw',
      'data' => $dish_types_data,
      'banner' => $banner_data,
    ));
  }
}
