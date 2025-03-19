<?php

namespace App\Controllers\Owner;

use App\Core\MvcController;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;
use App\Services\Owner\SettingsService;

ResourceLoader::load_service('SettingsService', 'Owner'); // ładowanie serwisu przy użyciu require_once

class SettingsController extends MvcController
{
  private $_service; // instancja serwisu

  public function __construct()
  {
    parent::__construct();
    $this->_service = MvcService::get_instance(SettingsService::class); // stworzenie instancji serwisu
  }

  /**
   * Przejście pod adres: /owner/settings/delete-account
   */
  public function delete_account()
  {
    $this->_service->delete_account();
    header('Location:' . __URL_INIT_DIR__ . 'owner/settings', true, 301);
  }

  /**
   * Przejście pod adres: /owner/settings/delete-profile-image
   */
  public function delete_profile_image()
  {
    $this->protector->protect_only_owner();
    $this->_service->delete_profile_image();
    header('Location:' . __URL_INIT_DIR__ . 'owner/settings', true, 301);
  }

  /**
   * Przejście pod adres: /owner/settings
   */
  public function index()
  {
    $this->protector->protect_only_owner();
    $personal_data = $this->_service->get_and_modify_user_personal_data();
    $banner_data = SessionHelper::check_session_and_unset(SessionHelper::OWNER_SETTINGS_PAGE_BANNER);
    $this->renderer->render_embed('owner-wrapper-view', 'owner/settings-view', array(
      'page_title' => 'Ustawienia właściciela',
      'data' => $personal_data,
      'banner' => $banner_data,
    ));
  }
}
