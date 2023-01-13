<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: ProfileController.php                          *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 21:22:13                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-13 00:18:49                   *
 * Modyfikowany przez: patrick012016                           *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\User\Services\ProfileService;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service('ProfileService', 'user'); // ładowanie serwisu przy użyciu require_once

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
     * Przejście pod adres: /user/profile/edit-profile
     */
    public function edit_profile()
    {
        $this->protector->protect_only_user();
        $edit_login_profile = $this->_service->edit_user_profile();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::EDIT_USER_PROFILE_PAGE_BANNER);
        $this->renderer->render('user/edit-profile-view', array(
            'page_title' => 'Edytuj profil',
            'data' => $edit_login_profile,
            'banner' => $banner_data,
        ));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    /**
     * Przejście pod adres: /user/profile/add-new-address
     */
    public function add_new_address()
    {
        $this->protector->protect_only_user();
        $add_address = $this->_service->add_new_addres();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADD_USER_NEW_ADDRESS_PAGE_BANNER);
        $this->renderer->render('user/add-new-address-view', array(
            'page_title' => 'Dodaj nowy adres',
            'data' => $add_address,
            'banner' => $banner_data,
        ));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    

    /**
     * Przejście pod adres: /user/profile
     */
	public function index()
    {
        $this->protector->protect_only_user();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::USER_PROFILE_PAGE_BANNER);
        $this->renderer->render('user/profile-view', array(
            'page_title' => 'Profil',
            'banner' => $banner_data,
        ));
	}
}
