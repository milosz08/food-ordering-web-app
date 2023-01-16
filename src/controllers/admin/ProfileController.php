<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: ProfileController.php                          *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 22:29:35                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-16 19:39:00                   *
 * Modyfikowany przez: patrick012016                           *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Owner\Services\ProfileService;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service('ProfileService', 'owner'); // ładowanie serwisu przy użyciu require_once

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class ProfileController extends MvcController
{
    private $_service; // instancja serwisu

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct()
    {
        parent::__construct();
        $this->_service = MvcService::get_instance(ProfileService::class); // stworzenie instancji serwisu
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /admin/profile
     */
    public function index()
    {
        $this->protector->protect_only_admin();
        $profile_info = $this->_service->profile();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADMIN_PROFILE_PAGE_BANNER);
        $this->renderer->render_embed('admin-wrapper-view', 'admin/profile-view', array(
            'page_title' => 'Profil administratora',
            'data' => $profile_info,
            'banner' => $banner_data,
        ));
    }
}
