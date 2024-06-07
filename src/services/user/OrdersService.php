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
 * Ostatnia modyfikacja: 2024-06-08 00:52:18                   *
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
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('ValidationHelper');

class OrdersService extends MvcService
{
    private $_banner_message = '';
    private $_banner_error = false;

    protected function __construct()
    {
        parent::__construct();
    }

    public function get_all_user_orders()
    {
        $all_orders = array();
        try
        {
            $this->dbh->beginTransaction();
            $query = "
                SELECT o.id, r.name, REPLACE(o.price, '.', ',') AS price, s.name AS order_status,
                IF(s.id <> 3, 'text-success', 'text-danger') AS order_status_color,
                CONCAT(IFNULL(NULLIF(CONCAT(HOUR(estimate_time), 'h '), 0), ''), IFNULL(NULLIF(CONCAT(MINUTE(estimate_time), 'min'), 0), '?'))
                AS estimate_time, IFNULL(r.profile_url, 'static/images/default-profile.jpg') AS profile_url
                FROM ((orders AS o
                INNER JOIN restaurants AS r ON o.restaurant_id = r.id)
                INNER JOIN order_status AS s ON o.status_id = s.id)
                WHERE o.user_id = ? ORDER BY date_order DESC LIMIT 10
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));

            // Pętla wypełniająca tablicę zamówieniami
            while ($row = $statement->fetchObject(ShowUserOrdersListModel::class)) array_push($all_orders, $row);
            $statement->closeCursor();
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::USER_ORDERS_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'orders' => $all_orders,
            'has_orders' => count($all_orders),
        );
    }

    public function get_user_order_details()
    {
        $one_order = new ShowUserSingleOrderModel;
        if (!isset($_GET['id'])) header('Location:' . __URL_INIT_DIR__ . 'user/orders');
        try
        {
            $this->dbh->beginTransaction();
            $query = "
                SELECT IF((TIMESTAMPDIFF(SECOND, date_order, NOW())) > 300, true, false) AS time_statement,
                o.id, o.status_id, REPLACE(o.discount_id, '.', ',') AS discount_id, dt.name AS order_type, os.name AS status_name, 
                u.first_name AS first_name, u.last_name AS last_name, u.email AS email, o.date_order AS date_order, 
                ua.street AS street, ua.building_nr AS building_nr, ua.locale_nr AS locale_nr,
                ua.post_code AS post_code, ua.city AS city,
                IF(status_id = 2, true, false) AS is_grade_active, rg.id AS grade_id,
                IF((SELECT COUNT(*) FROM restaurants_grades WHERE order_id = o.id) = 1, '123', '') AS is_grade_editable
                FROM (((((orders AS o
                INNER JOIN order_status AS os ON o.status_id = os.id)
                LEFT JOIN restaurants_grades AS rg ON rg.order_id = o.id)
                INNER JOIN delivery_type AS dt ON o.delivery_type = dt.id)
                INNER JOIN users AS u ON o.user_id = u.id)
                INNER JOIN user_address AS ua ON u.id = ua.user_id)
                WHERE o.user_id = :userid AND o.id = :id AND o.order_adress = ua.id;
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('userid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
            $statement->bindValue('id', $_GET['id'], PDO::PARAM_INT);
            $statement->execute();
            $one_order = $statement->fetchObject(ShowUserSingleOrderModel::class);
            if (!$one_order) header('Location:' . __URL_INIT_DIR__ . 'user/orders');
            $is_cancel_active = !($one_order->status_id == 3 || $one_order->time_statement);

            $query = "
                SELECT COUNT(owd.dish_id) AS dish_amount, d.name AS dish_name
                FROM (((orders_with_dishes AS owd
                INNER JOIN orders AS o ON owd.order_id = o.id)
                INNER JOIN dishes AS d ON owd.dish_id = d.id)
                INNER JOIN users AS u ON o.user_id = u.id)
                WHERE o.user_id = :userid AND owd.order_id = :id AND owd.dish_id = d.id
                GROUP BY owd.dish_id;
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('userid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
            $statement->bindValue('id', $_GET['id'], PDO::PARAM_INT);
            $statement->execute();
            $one_order->dishes_value = $statement->fetchAll(PDO::FETCH_ASSOC);

            $statement->closeCursor();
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::USER_ORDER_DETAILS_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'one_order' => $one_order,
            'is_cancel_active' => $is_cancel_active,
        );
    }

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
            if ($this->dbh->inTransaction()) $this->dbh->commit();
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
