<?php

namespace App\Controllers\Auth;

use App\Core\MvcController;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Auth\RegisterService;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service('RegisterService', 'Auth'); // ładowanie serwisu przy użyciu require_once

class RegisterController extends MvcController
{
  private $_service; // instancja serwisu

  public function __construct()
  {
    parent::__construct();
    $this->_service = MvcService::get_instance(RegisterService::class); // stworzenie instancji serwisu
  }

  /**
   * Przejście pod adres: /auth/register
   */
  public function index()
  {
    $this->protector->redirect_when_logged();
    $register_user_data = $this->_service->register_user();
    $banner_data = SessionHelper::check_session_and_unset(SessionHelper::REGISTER_PAGE_BANNER);
    $this->renderer->render('auth/registration-view', array(
      'page_title' => 'Rejestracja',
      'data' => $register_user_data,
      'banner' => $banner_data,
    ));
  }
}
