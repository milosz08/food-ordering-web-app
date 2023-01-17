<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: IndexController.php                            *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 20:48:23                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-06 23:50:19                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service_helper('SessionHelper');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class IndexController extends MvcController
{
    public function __construct()
    {
        parent::__construct(); // przekazanie nazwy klasy serwisu, w celu zaimportowania jej dyrektywą require_once
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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
