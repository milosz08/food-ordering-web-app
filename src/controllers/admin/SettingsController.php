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
 * Ostatnia modyfikacja: 2024-06-08 00:38:14                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Admin\Services\SettingsService;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service('SettingsService', 'admin'); // ładowanie serwisu przy użyciu require_once

class SettingsController extends MvcController
{
    private $_service; // instancja serwisu

    public function __construct()
    {
        parent::__construct();
		$this->_service = MvcService::get_instance(SettingsService::class); // stworzenie instancji serwisu
    }

    /**
     * Przejście pod adres: /owner/settings/delete-profile-image
     */
    public function delete_profile_image()
    {
        $this->protector->protect_only_admin();
        $this->_service->delete_profile_image();
        header('Location:' . __URL_INIT_DIR__ . 'admin/settings', true, 301);
    }

    /**
     * Przejście pod adres: /admin/settings
     */
	public function index()
    {
        $this->protector->protect_only_admin();
        $personal_data = $this->_service->get_and_modify_user_personal_data();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADMIN_SETTINGS_PAGE_BANNER);
        $this->renderer->render_embed('admin-wrapper-view', 'admin/settings-view', array(
            'page_title' => 'Ustawienia administratora',
            'data' => $personal_data,
            'banner' => $banner_data,
        ));
	}
}
