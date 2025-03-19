<?php

namespace App\Controllers\Auth;

use App\Core\MvcController;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Auth\ForgotPasswordService;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service('ForgotPasswordService', 'Auth'); // ładowanie serwisu przy użyciu require_once

class ForgotPasswordController extends MvcController
{
  private $_service; // instancja serwisu

  public function __construct()
  {
    parent::__construct();
    $this->_service = MvcService::get_instance(ForgotPasswordService::class); // stworzenie instancji serwisu
  }

  /**
   * Przejście pod adres: /auth/forgot-password/change
   */
  public function change()
  {
    $this->protector->redirect_when_logged();
    $form_data = $this->_service->forgot_password_change();
    $banner_data = SessionHelper::check_session_and_unset(SessionHelper::FORGOT_PASSWORD_CHANGE_PAGE_BANNER);
    $this->renderer->render('auth/renew-password-change-view', array(
      'page_title' => 'Zmień hasło',
      'form' => $form_data,
      'banner' => $banner_data,
    ));
  }

  /**
   * Przejście pod adres: /auth/forgot-password
   */
  public function index()
  {
    $this->protector->redirect_when_logged();
    $form_data = $this->_service->forgot_password_request();
    $banner_data = SessionHelper::check_session_and_unset(SessionHelper::FORGOT_PASSWORD_PAGE_BANNER);
    $this->renderer->render('auth/renew-password-email-view', array(
      'page_title' => 'Resetuj hasło',
      'form' => $form_data,
      'banner' => $banner_data,
    ));
  }
}
