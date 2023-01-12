<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: DiscountsController.php                        *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-12, 01:25:45                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-12 12:23:17                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcService;
use App\Core\MvcController;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;
use App\Owner\Services\DiscountsService;

ResourceLoader::load_service('DiscountsService', 'owner');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class DiscountsController extends MvcController
{
    private $_service; // instancja serwisu

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
    public function __construct()
    {
        parent::__construct();
		$this->_service = MvcService::get_instance(DiscountsService::class); // stworzenie instancji serwisu
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /owner/discounts/discounts-with-restaurants
     */
    public function discounts_with_restaurants()
    {
        $this->protector->protect_only_owner();
        $discount_data = $this->_service->get_restaurants_with_discounts_codes();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::DISCOUNTS_RES_PAGE_BANNER);
        $this->renderer->render_embed('owner-wrapper-view', 'owner/discounts/discounts-with-restaurants-view', array(
            'page_title' => 'Zarządzaj kodami rabatowymi',
            'data' => $discount_data,
            'banner' => $banner_data,
        ));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /owner/discounts/add-discount
     */
	public function add_discount()
    {
        $this->protector->protect_only_owner();
        $add_discount_data = $this->_service->add_discount_code();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::DISCOUNT_ADD_EDIT_PAGE_BANNER);
        $this->renderer->render_embed('owner-wrapper-view', 'owner/discounts/add-edit-discount-view', array(
            'page_title' => 'Dodaj kod rabatowy',
            'add_edit_text' => 'Dodaj kod rabatowy do',
            'add_edit_button' => 'Dodaj',
            'disable_edit_code' => array('bts_class' => '', 'trigger_class' => 'disable-checkbox'),
            'data' => $add_discount_data,
            'banner' => $banner_data,
        ));
	}

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /owner/discounts/edit-discount
     */
	public function edit_discount()
    {
        $this->protector->protect_only_owner();
        $edit_discount_data = $this->_service->edit_discount_code();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::DISCOUNT_ADD_EDIT_PAGE_BANNER);
        $this->renderer->render_embed('owner-wrapper-view', 'owner/discounts/add-edit-discount-view', array(
            'page_title' => 'Edytuj kod rabatowy',
            'add_edit_text' => 'Edytuj kod rabatowy',
            'add_edit_button' => 'Edytuj',
            'disable_edit_code' => array('bts_class' => 'disabled', 'trigger_class' => ''),
            'data' => $edit_discount_data,
            'banner' => $banner_data,
        ));
	}

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /owner/discounts/delete-discount
     */
	public function delete_discount()
    {
        $this->protector->protect_only_owner();
        $redir_path = $this->_service->delete_discount_code();
        header('Location:' . __URL_INIT_DIR__ . $redir_path, true, 301);
	}

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /owner/discounts/increase-usages
     */
    public function increase_usages()
    {
        $this->protector->protect_only_owner();
        $redir_path = $this->_service->increase_code_usages_count();
        header('Location:' . __URL_INIT_DIR__ . $redir_path, true, 301);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /owner/discounts/increase-time
     */
    public function increase_time()
    {
        $this->protector->protect_only_owner();
        $redir_path = $this->_service->increase_code_expiration_time();
        header('Location:' . __URL_INIT_DIR__ . $redir_path, true, 301);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Przejście pod adres: /owner/discounts
     */
	public function index()
    {
        $this->protector->protect_only_owner();
        $discount_data = $this->_service->get_all_discounts_codes();
        $banner_data = SessionHelper::check_session_and_unset(SessionHelper::DISCOUNTS_PAGE_BANNER);
        $this->renderer->render_embed('owner-wrapper-view', 'owner/discounts/discounts-view', array(
            'page_title' => 'Moje kody rabatowe',
            'data' => $discount_data,
            'banner' => $banner_data,
        ));
	}
}
