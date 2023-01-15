<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: OrdersService.php                              *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-03, 02:13:51                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-14 12:00:27                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Owner\Services;

use Exception;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service_helper('SessionHelper');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class OrdersService extends MvcService
{
    private $_banner_message = '';
    private $_banner_error = false;

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    protected function __construct()
    {
        parent::__construct();
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda zwracająca wszystkie zamówienia dokonane przez użytkowników restauracji. Dodatkowo restauracje można filtrować, wyświetlając
     * jedynie zamówienia z wybranej restauracji oraz ze wszystkich restauracji.
     */
    public function get_orders()
    {
        try
        {
            $this->dbh->beginTransaction();

            // tutaj kod

            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::OWNER_ORDER_DETAILS_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(

        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda zwracająca szczegóły zamówienia na podstawie jego ID przekazywanego w parametrach GET zapytania.
     */
    public function get_order_details()
    {
        if (!isset($_GET['id'])) header('Location:' . __URL_INIT_DIR__ . 'owner/orders', true, 301);
        try
        {
            $this->dbh->beginTransaction();

            // tutaj kod

            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::OWNER_ORDER_DETAILS_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'order_id' => $_GET['id'],
            'order_details' => '',
        );
    }
}
