<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: SettingsController.php                         *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 22:29:43                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-06 17:44:17                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Admin\Services\SettingsService;

ResourceLoader::load_service('SettingsService', 'admin'); // ładowanie serwisu przy użyciu require_once

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class SettingsController extends MvcController
{
    private $_service; // instancja serwisu

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
    public function __construct()
    {
        parent::__construct();
		$this->_service = MvcService::get_instance(SettingsService::class); // stworzenie instancji serwisu
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /admin/settings
     */
	public function index()
    {
        $this->protector->protect_only_admin();
        $this->renderer->render_embed('admin-wrapper-view', 'admin/settings-view', array(
            'page_title' => 'Ustawienia administratora',
        ));
	}
}
