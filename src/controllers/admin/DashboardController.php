<?php

namespace App\Controllers\Admin;

use App\Core\MvcController;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Admin\DashboardService;

ResourceLoader::load_service('DashboardService', 'Admin'); // ładowanie serwisu przy użyciu require_once

class DashboardController extends MvcController
{
  private $_service; // instancja serwisu

  public function __construct()
  {
    parent::__construct();
    $this->_service = MvcService::get_instance(DashboardService::class); // stworzenie instancji serwisu
  }

  /**
   * Przejście pod adres: /admin/dashboard/graph
   */
  public function graph()
  {
    $this->protector->protect_only_admin();
    header('Content-Type: application/json; charset=UTF-8');
    echo $this->_service->graph();
  }

  /**
   * Przejście pod adres: /admin/dashboard
   */
  public function index()
  {
    $this->protector->protect_only_admin();
    $this->renderer->render_embed('admin-wrapper-view', 'admin/dashboard-view', array(
      'page_title' => 'Panel główny',
      'charts_admin_loadable_content' => true,
    ));
  }
}
