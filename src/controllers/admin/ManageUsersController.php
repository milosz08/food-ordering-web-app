<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: ManageUsersController.php                      *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-14, 22:59:50                       *
 * Autor: patrick012016                                        *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-16 03:24:03                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;
use App\Admin\Services\ManageUsersService;

ResourceLoader::load_service('ManageUsersService', 'admin'); // ładowanie serwisu przy użyciu require_once

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class ManageUsersController extends MvcController
{
    private $_service; // instancja serwisu

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
    public function __construct()
    {
        parent::__construct();
		$this->_service = MvcService::get_instance(ManageUsersService::class); // stworzenie instancji serwisu
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /admin/manage-users/user-details
     */
    public function user_details()
    {
        $this->protector->protect_only_admin();
        $user_details_data = $this->_service->get_users_details();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADMIN_USER_DETAILS_PAGE_BANNER);
        $this->renderer->render_embed('admin-wrapper-view', 'admin/manage-users/user-details-view', array(
            'page_title' => 'Szczegóły użytkownika #' . $user_details_data['user_details']->id,
            'banner' => $banner_data,
            'data' => $user_details_data,
        ));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /admin/manage-users/delete-user
     */
    public function delete_user()
    {
        $this->protector->protect_only_admin();
        $this->_service->delete_user();
        header('Location:' . __URL_INIT_DIR__ . '/admin/manage-users', true, 301);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /admin/manage-users/delete-user-image
     */
    public function delete_user_image()
    {
        $this->protector->protect_only_admin();
        $redir_path = $this->_service->delete_user_profile_image();
        header('Location:' . __URL_INIT_DIR__ . $redir_path, true, 301);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /admin/manage-users
     */
	public function index()
    {
        $this->protector->protect_only_admin();
        $users_list = $this->_service->get_users();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::ADMIN_MANAGED_USERS_PAGE_BANNER);
        $this->renderer->render_embed('admin-wrapper-view', 'admin/manage-users/manage-users-view', array(
            'page_title' => 'Zarządzaj użytkownikami',
            'banner' => $banner_data,
            'data' => $users_list,
        ));
	}
}
