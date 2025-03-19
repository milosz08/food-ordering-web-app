<?php

namespace App\Controllers\Owner;

use App\Core\MvcController;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Owner\DashboardService;

ResourceLoader::load_service('DashboardService', 'Owner'); // ładowanie serwisu przy użyciu require_once

class DashboardController extends MvcController
{
  private $_service; // instancja serwisu

  public function __construct()
  {
    parent::__construct();
    $this->_service = MvcService::get_instance(DashboardService::class); // stworzenie instancji serwisu
  }

  /**
   * Przejście pod adres: /owner/dashboard/graph
   */
  public function graph()
  {
    $this->protector->protect_only_owner();
    header('Content-Type: application/json; charset=UTF-8');
    echo $this->_service->graph();
  }

  /**
   * Przejście pod adres: /owner/dashboard
   */
  public function index()
  {
    $this->protector->protect_only_owner();
    $this->renderer->render_embed('owner-wrapper-view', 'owner/dashboard-view', array(
      'page_title' => 'Panel główny',
      'charts_owner_loadable_content' => true,
    ));
  }
}
