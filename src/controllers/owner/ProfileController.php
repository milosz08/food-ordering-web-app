<?php

namespace App\Controllers\Owner;

use App\Core\MvcController;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;
use App\Services\ProfileService;

ResourceLoader::load_service('ProfileService', 'Owner'); // ładowanie serwisu przy użyciu require_once

class ProfileController extends MvcController
{
  private $_service; // instancja serwisu

  public function __construct()
  {
    parent::__construct();
    $this->_service = MvcService::get_instance(ProfileService::class); // stworzenie instancji serwisu
  }

  /**
   * Przejście pod adres: /owner/profile
   */
  public function index()
  {
    $this->protector->protect_only_owner();
    $profile_info = $this->_service->profile();
    $banner_data = SessionHelper::check_session_and_unset(SessionHelper::OWNER_PROFILE_PAGE_BANNER);
    $this->renderer->render_embed('owner-wrapper-view', 'owner/profile-view', array(
      'page_title' => 'Profil właściciela',
      'data' => $profile_info,
      'banner' => $banner_data,
    ));
  }
}
