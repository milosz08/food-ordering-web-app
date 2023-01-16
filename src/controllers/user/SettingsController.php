<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: SettingsController.php                         *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-07, 01:01:03                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-16 07:56:13                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\User\Services\SettingsService;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service('SettingsService', 'user');

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
     * Przejście pod adres: /user/settings/add-new-address
     */
    public function add_new_address()
    {
        $this->protector->protect_only_user();
        $add_address = $this->_service->add_new_addres();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADD_USER_NEW_ADDRESS_PAGE_BANNER);
        $this->renderer->render('user/add-edit-new-address-view', array(
            'page_title' => 'Dodaj nowy adres',
            'data' => $add_address,
            'banner' => $banner_data,
        ));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /user/settings/alternative-address
     */
    public function alternative_address()
    {
        $this->protector->protect_only_user();
        $base_path = __URL_INIT_DIR__ . 'user/settings';
        if (!isset($_POST['id']) || !isset($_POST['action'])) header('Location:' . $base_path, true, 301);
        switch ($_POST['action'])
        {
            case 'edit': header('Location:' . $base_path . '/edit-alternative-address?id=' . $_POST['id'], true, 301); break;
            case 'delete': header('Location:' . $base_path . '/delete-address?id=' . $_POST['id'], true, 301); break;
            default: header('Location:' . $base_path, true, 301); break;
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /user/settings/delete-address
     */
    public function delete_address()
    {
        $this->protector->protect_only_user();
        $this->_service->delete_address();
        header('Location:' . __URL_INIT_DIR__ . 'user/settings', true, 301);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /user/settings/delete-profile-image
     */
    public function delete_profile_image()
    {
        $this->protector->protect_only_user();
        $this->_service->delete_profile_image();
        header('Location:' . __URL_INIT_DIR__ . 'user/settings', true, 301);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    /**
     * Przejście pod adres: /user/settings/edit-alternativ-address
     */
    public function edit_alternative_address()
    {
        $this->protector->protect_only_user();
        $edit_alternativ_address = $this->_service->edit_alternative_address();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADD_USER_NEW_ADDRESS_PAGE_BANNER);
        $this->renderer->render('user/add-edit-new-address-view', array(
            'page_title' => 'Edytuj adres',
            'banner' => $banner_data,
            'data' => $edit_alternativ_address
        ));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /user/settings
     */
    public function index()
    {
        $this->protector->protect_only_user();
        $edit_user_profile = $this->_service->edit_user_profile();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::USER_PROFILE_PAGE_BANNER);
        $this->renderer->render('user/settings-view', array(
            'page_title' => 'Ustawienia',
            'data' => $edit_user_profile,
            'banner' => $banner_data,
        ));
    }
}
