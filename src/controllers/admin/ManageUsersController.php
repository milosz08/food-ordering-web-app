<?php

namespace App\Controllers\Admin;

use App\Core\MvcController;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Admin\ManageUsersService;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service('ManageUsersService', 'Admin'); // ładowanie serwisu przy użyciu require_once

class ManageUsersController extends MvcController
{
  private $_service; // instancja serwisu

  public function __construct()
  {
    parent::__construct();
    $this->_service = MvcService::get_instance(ManageUsersService::class); // stworzenie instancji serwisu
  }

  /**
   * Przejście pod adres: /admin/manage-users/user-details
   */
  public function user_details()
  {
    $this->protector->protect_only_admin();
    $user_details_data = $this->_service->get_users_details();
    $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADMIN_USER_DETAILS_PAGE_BANNER);
    $this->renderer->render_embed('admin-wrapper-view', 'admin/manage-users/user-details-view', array(
      'page_title' => 'Szczegóły użytkownika #' . $user_details_data['user_details']->id,
      'banner' => $banner_data,
      'data' => $user_details_data,
    ));
  }

  /**
   * Przejście pod adres: /admin/manage-users/delete-user
   */
  public function delete_user()
  {
    $this->protector->protect_only_admin();
    $this->_service->delete_user();
    header('Location:' . __URL_INIT_DIR__ . '/admin/manage-users', true, 301);
  }

  /**
   * Przejście pod adres: /admin/manage-users/delete-user-image
   */
  public function delete_user_image()
  {
    $this->protector->protect_only_admin();
    $redirect_path = $this->_service->delete_user_profile_image();
    header('Location:' . __URL_INIT_DIR__ . $redirect_path, true, 301);
  }

  /**
   * Przejście pod adres: /admin/manage-users
   */
  public function index()
  {
    $this->protector->protect_only_admin();
    $users_list = $this->_service->get_users();
    $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADMIN_MANAGED_USERS_PAGE_BANNER);
    $this->renderer->render_embed('admin-wrapper-view', 'admin/manage-users/manage-users-view', array(
      'page_title' => 'Zarządzaj użytkownikami',
      'banner' => $banner_data,
      'data' => $users_list,
    ));
  }
}
