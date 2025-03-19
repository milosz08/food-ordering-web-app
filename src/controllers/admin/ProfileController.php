<?php

namespace App\Controllers\Admin;

use App\Core\MvcController;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;
use App\Services\ProfileService;

ResourceLoader::load_service('ProfileService'); // ładowanie serwisu przy użyciu require_once

class ProfileController extends MvcController
{
  private $_service; // instancja serwisu

  public function __construct()
  {
    parent::__construct();
    $this->_service = MvcService::get_instance(ProfileService::class); // stworzenie instancji serwisu
  }

  /**
   * Przejście pod adres: /admin/profile
   */
  public function index()
  {
    $this->protector->protect_only_admin();
    $profile_info = $this->_service->profile();
    $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADMIN_PROFILE_PAGE_BANNER);
    $this->renderer->render_embed('admin-wrapper-view', 'admin/profile-view', array(
      'page_title' => 'Profil administratora',
      'data' => $profile_info,
      'banner' => $banner_data,
    ));
  }
}
