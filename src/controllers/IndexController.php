<?php

namespace App\Controllers;

use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service_helper('SessionHelper');

class IndexController extends MvcController
{
  public function __construct()
  {
    parent::__construct(); // przekazanie nazwy klasy serwisu, w celu zaimportowania jej dyrektywą require_once
  }

  /**
   * Przejście pod adres: /
   */
  public function index()
  {
    $logout_modal = SessionHelper::check_session_and_unset(SessionHelper::LOGOUT_PAGE_BANNER);
    $this->renderer->render('index-view', array(
      'page_title' => 'Strona główna',
      'logout_modal_visible' => $logout_modal['is_open'] ?? false,
    ));
  }
}
