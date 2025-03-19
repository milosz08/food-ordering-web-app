<?php

namespace App\Controllers\Owner;

use App\Core\MvcController;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;
use App\Services\Owner\DishesService;

ResourceLoader::load_service('DishesService', 'Owner'); // ładowanie serwisu przy użyciu require_once

class DishesController extends MvcController
{
  private $_service; // instancja serwisu

  public function __construct()
  {
    parent::__construct();
    $this->_service = MvcService::get_instance(DishesService::class); // stworzenie instancji serwisu
  }

  /**
   * Przejście pod adres: /owner/dishes/dishes-with-restaurants
   */
  public function dishes_with_restaurants()
  {
    $this->protector->protect_only_owner();
    $restaurants_with_dishes = $this->_service->get_all_restaurants_with_dishes();
    $banner_data = SessionHelper::check_session_and_unset(SessionHelper::DISHES_WITH_RES_PAGE_BANNER);
    $this->renderer->render_embed('owner-wrapper-view', 'owner/dishes/dishes-restaurants-view', array(
      'page_title' => 'Dodaj potrawę do restauracji',
      'data' => $restaurants_with_dishes,
      'banner' => $banner_data,
    ));
  }

  /**
   * Przejście pod adres: /owner/dishes/add-dish
   */
  public function add_dish()
  {
    $this->protector->protect_only_owner();
    $add_dish_data = $this->_service->add_dish_to_restaurant();
    $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADD_EDIT_DISH_PAGE_BANNER);
    $this->renderer->render_embed('owner-wrapper-view', 'owner/dishes/add-edit-dish-view', array(
      'page_title' => 'Dodaj potrawę',
      'add_edit_text' => 'Dodaj',
      'data' => $add_dish_data,
      'banner' => $banner_data,
    ));
  }

  /**
   * Przejście pod adres: /owner/dishes/edit-dish
   */
  public function edit_dish()
  {
    $this->protector->protect_only_owner();
    $edit_dish_data = $this->_service->edit_dish_from_restaurant();
    $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADD_EDIT_DISH_PAGE_BANNER);
    $this->renderer->render_embed('owner-wrapper-view', 'owner/dishes/add-edit-dish-view', array(
      'page_title' => 'Edytuj potrawę',
      'add_edit_text' => 'Edytuj',
      'data' => $edit_dish_data,
      'banner' => $banner_data,
    ));
  }

  /**
   * Przejście pod adres: /owner/dishes/dish-details
   */
  public function dish_details()
  {
    $this->protector->protect_only_owner();
    $dish_details = $this->_service->get_dish_details();
    $banner_data = SessionHelper::check_session_and_unset(SessionHelper::DISH_DETAILS_PAGE_BANNER);
    $this->renderer->render_embed('owner-wrapper-view', 'owner/dishes/dish-details-view', array(
      'page_title' => 'Szczegóły potrawy',
      'data' => $dish_details,
      'banner' => $banner_data,
    ));
  }

  /**
   * Przejście pod adres: /owner/dishes/delete-dish-image
   */
  public function delete_dish_image()
  {
    $this->protector->protect_only_owner();
    $redirect_path = $this->_service->delete_dish_image();
    header('Location:' . __URL_INIT_DIR__ . $redirect_path, true, 301);
  }

  /**
   * Przejście pod adres: /owner/dishes/delete-dish
   */
  public function delete_dish()
  {
    $this->protector->protect_only_owner();
    $delete_dish_details = $this->_service->delete_dish_from_restaurant();
    header(
      'Location:' . __URL_INIT_DIR__ . 'owner/restaurants/restaurant-details?id=' . $delete_dish_details['restaurant_id'],
      true,
      301
    );
  }

  /**
   * Przejście pod adres: /owner/dishes
   */
  public function index()
  {
    $this->protector->protect_only_owner();
    $all_dishes = $this->_service->get_all_dishes();
    $banner_data = SessionHelper::check_session_and_unset(SessionHelper::DISHES_PAGE_BANNER);
    $this->renderer->render_embed('owner-wrapper-view', 'owner/dishes/dishes-view', array(
      'page_title' => 'Moje potrawy',
      'data' => $all_dishes,
      'banner' => $banner_data,
    ));
  }
}
