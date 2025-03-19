<?php

namespace App\Controllers\Auth;

use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service_helper('SessionHelper');

class LogoutController extends MvcController
{
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Przejście pod adres: /auth/logout
   */
  public function index()
  {
    if (!isset($_SESSION['logged_user'])) {
      header('Location:' . __URL_INIT_DIR__ . 'auth/login', true, 301);
    } else {
      unset($_SESSION['logged_user']);
      $_SESSION[SessionHelper::LOGOUT_PAGE_BANNER] = array('is_open' => true);
      header('Location:' . __URL_INIT_DIR__, true, 301);
    }
  }
}
