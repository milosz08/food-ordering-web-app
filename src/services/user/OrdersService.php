<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: OrdersService.php                              *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 21:03:17                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-15 13:11:16                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\User\Services;

use PDO;
use Exception;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\ShowUserOrdersListModel;
use App\Models\ShowUserSingleOrderModel;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_model('ShowUserOrdersListModel', 'user');
ResourceLoader::load_model('ShowUserSingleOrderModel', 'user');
ResourceLoader::load_service_helper('ValidationHelper');
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

    public function get_all_user_orders()
    {
        $all_orders = array();
        try
        {
            $this->dbh->beginTransaction();
            $query = "
                SELECT o.id, r.name, o.price, IF(o.status_id = 3, true, false) AS order_statement
                FROM orders AS o INNER JOIN restaurants AS r ON o.restaurant_id = r.id WHERE o.user_id = ?
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));

            // Pętla wypełniająca tablicę zamówieniami
            while ($row = $statement->fetchObject(ShowUserOrdersListModel::class)) array_push($all_orders, $row);
            $statement->closeCursor();
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::USER_ORDERS_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'orders' => $all_orders,
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function get_user_order_details()
    {
        $one_order = new ShowUserSingleOrderModel;
        if (!isset($_GET['id'])) header('Location:' . __URL_INIT_DIR__ . 'user/orders');
        try
        {
            $this->dbh->beginTransaction();
            $query = "
                SELECT IF((TIMESTAMPDIFF(SECOND, date_order, NOW())) > 300, true, false) AS time_statement,
                o.id, o.status_id, o.discount_id AS discount_id, dt.name AS order_type, os.name AS status_name, 
                u.first_name AS first_name, u.last_name AS last_name, u.email AS email, o.date_order AS date_order, 
                ua.street AS street, ua.building_nr AS building_nr, ua.locale_nr AS locale_nr, 
                ua.post_code AS post_code, ua.city AS city
                FROM ((((orders AS o
                INNER JOIN order_status AS os ON o.status_id = os.id)
                INNER JOIN delivery_type AS dt ON o.delivery_type = dt.id)
                INNER JOIN users AS u ON o.user_id = u.id)
                INNER JOIN user_address AS ua ON u.id = ua.user_id)
                WHERE o.user_id = :userid AND o.id = :id;
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('userid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
            $statement->bindValue('id', $_GET['id'], PDO::PARAM_INT);
            $statement->execute();
            $one_order = $statement->fetchObject(ShowUserSingleOrderModel::class);
            if (!$one_order) header('Location:' . __URL_INIT_DIR__ . 'user/orders');

            $validation = !($one_order->status_id == 3 || $one_order->time_statement);
            $statement->closeCursor();
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::USER_ORDER_DETAILS_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'one_order' => $one_order,
            'validation' => $validation,
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function cancel_order()
    {
        $redirect_url = 'user/orders';
        if (!isset($_GET['id'])) return $redirect_url;
        try
        {
            $this->dbh->beginTransaction();
            $query = "
                SELECT IF((TIMESTAMPDIFF(SECOND, date_order, NOW())) > 300, false, true) AS dif FROM orders
                WHERE user_id = :userid AND id = :id
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('userid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
            $statement->bindValue('id', $_GET['id'], PDO::PARAM_INT);
            $statement->execute();
            if (!$statement->fetchColumn()) throw new Exception('Czas na anulowanie zamówienia o numerze ' . $_GET['id'] . ' minał!');

            $query = "UPDATE orders SET status_id = 3 WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));

            $this->_banner_message = 'Pomyślnie anulowano zamówienie o nr: ' . $_GET['id'];
            $statement->closeCursor();
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $this->_banner_error = true;
            $this->_banner_message = $e->getMessage();
        }
        SessionHelper::create_session_banner(SessionHelper::USER_ORDERS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
        return $redirect_url;
    }
}
