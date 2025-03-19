<?php

namespace App\Controllers\Auth;

use App\Core\MvcController;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Auth\ActivateAccountService;

ResourceLoader::load_service('ActivateAccountService', 'Auth'); // ładowanie serwisu przy użyciu require_once

class ActivateAccountController extends MvcController
{
  private $_service; // instancja serwisu

  public function __construct()
  {
    parent::__construct();
    $this->_service = MvcService::get_instance(ActivateAccountService::class); // stworzenie instancji serwisu
  }

  /**
   * Przejście pod adres: /auth/activate-account/resend
   */
  public function resend()
  {
    $this->protector->redirect_when_logged();
    $this->_service->resend_account_activation_link();
    header('Location:' . __URL_INIT_DIR__ . 'auth/login', true, 301);
  }

  /**
   * Przejście pod adres: /auth/activate-account
   */
  public function index()
  {
    $this->protector->redirect_when_logged();
    $this->_service->attempt_activate_account();
    header('Location:' . __URL_INIT_DIR__ . 'auth/login', true, 301);
  }
}
