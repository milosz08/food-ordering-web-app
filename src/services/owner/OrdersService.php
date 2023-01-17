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
 * Ostatnia modyfikacja: 2023-01-17 02:41:52                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Owner\Services;

use PDO;
use Exception;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\OwnerOrdersModel;
use App\Models\OwnerOrderDetailsModel;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\PaginationHelper;

ResourceLoader::load_model('OwnerOrdersModel', 'restaurant');
ResourceLoader::load_model('OwnerOrderDetailsModel', 'restaurant');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('PaginationHelper');

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
        $pagination = array(); // tablica przechowująca liczby przekazywane do dynamicznego tworzenia elementów paginacji
        $orders = array();
        $pages_nav = array();
        $not_empty = false;
        try
        {
            $this->dbh->beginTransaction();

            $curr_page = $_GET['page'] ?? 1; // pobranie indeksu paginacji
            $page = ($curr_page - 1) * 10;
            $total_per_page = $_GET['total'] ?? 10;
            $search_text = SessionHelper::persist_search_text('search-order-name', SessionHelper::OWNER_ORDER_SEARCH);
            
            $redirect_url = 'owner/orders';
            PaginationHelper::check_parameters('owner/orders');

            // zapytanie do bazy danych, które zwróci poszczególne wartości zamówień wszystkich klientów dla obecnie zalogowanego właściciela
            $query = "
                SELECT ROW_NUMBER() OVER(ORDER BY o.id) as it, o.id AS id, CONCAT(u.first_name, ' ', u.last_name) AS user,
                IF(o.discount_id IS NOT NULL, d.code, 'Brak') AS discount, os.name AS status,
                CONCAT('ul. ', ua.street, ' ', ua.building_nr, IF(ua.locale_nr IS NOT NULL, (CONCAT('/',ua.locale_nr)), ('')) , ', ', 
                ua.post_code, ' ', ua.city) AS order_adress, dt.name AS delivery_type, IF(o.status_id != 1, 'disabled', ' ') AS button_status,
                CONCAT(REPLACE(CAST(o.price AS DECIMAL(10,2)), '.', ','), ' zł') AS price, r.name AS restaurant
                FROM (((((orders AS o
                INNER JOIN order_status AS os ON o.status_id = os.id)
                INNER JOIN delivery_type AS dt ON o.delivery_type = dt.id)
                INNER JOIN users AS u ON o.user_id = u.id)
                INNER JOIN restaurants AS r ON o.restaurant_id = r.id)
                INNER JOIN user_address AS ua ON u.id = ua.user_id)
                LEFT JOIN discounts AS d ON o.discount_id = d.id
                WHERE r.user_id = :id AND r.name LIKE :search
                GROUP BY o.id
                LIMIT :total OFFSET :page
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('id', $_SESSION['logged_user']['user_id']);
            $statement->bindValue('search', '%' . $search_text . '%');
            $statement->bindValue('total', $total_per_page, PDO::PARAM_INT);
            $statement->bindValue('page', $page, PDO::PARAM_INT);
            $statement->execute();

            while ($row = $statement->fetchObject(OwnerOrdersModel::class)) array_push($orders, $row);
            $not_empty = count($orders);

            // zapytanie zliczające wszystkie zamówienia przypisane do właściciela
            $query = "SELECT count(*) FROM orders AS o
            INNER JOIN restaurants AS r ON o.restaurant_id = r.id
            WHERE r.user_id = :id AND r.name LIKE :search";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('id', $_SESSION['logged_user']['user_id']);
            $statement->bindValue('search', '%' . $search_text . '%');
            $statement->execute();
            $total_records = $statement->fetchColumn();

            $total_pages = ceil($total_records / $total_per_page);
            for ($i = 1; $i <= $total_pages; $i++) array_push($pagination, array(
                'it' => $i,
                'url' => $redirect_url . '?page=' . $i . '&total=' . $total_per_page, 
                'selected' => $curr_page ==  $i ? 'active' : '',
            ));

            $statement->closeCursor();
            PaginationHelper::check_if_page_is_greaten_than($redirect_url, $total_pages);
            $pages_nav = PaginationHelper::get_pagination_nav($curr_page, $total_per_page, $total_pages, $total_records, $redirect_url);
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::OWNER_ORDERS_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'total_per_page' => $total_per_page,
            'pagination_url' => 'owner/orders?',
            'pagination' => $pagination,
            'pages_nav' => $pages_nav,
            'orders' => $orders,
            'search_text' => $search_text,
            'not_empty' => $not_empty,
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda zwracająca szczegóły zamówienia na podstawie jego ID przekazywanego w parametrach GET zapytania.
     */
    public function get_order_details()
    {
        $single_order = new OwnerOrderDetailsModel;
        if (!isset($_GET['id'])) header('Location:' . __URL_INIT_DIR__ . 'owner/orders', true, 301);
        try
        {
            $this->dbh->beginTransaction();
            $query = "
                SELECT o.id, o.status_id, o.discount_id AS discount_id, dt.name AS order_type, os.name AS status_name, 
                u.first_name AS first_name, u.last_name AS last_name, u.email AS email, o.date_order AS date_order, 
                ua.street AS street, ua.building_nr AS building_nr, ua.locale_nr AS locale_nr,
                ua.post_code AS post_code, ua.city AS city
                FROM ((((orders AS o
                INNER JOIN order_status AS os ON o.status_id = os.id)
                INNER JOIN delivery_type AS dt ON o.delivery_type = dt.id)
                INNER JOIN users AS u ON o.user_id = u.id)
                INNER JOIN user_address AS ua ON u.id = ua.user_id)
                WHERE o.user_id = u.id AND o.id = :id;
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('id', $_GET['id'], PDO::PARAM_INT);
            $statement->execute();
            $single_order = $statement->fetchObject(OwnerOrderDetailsModel::class);
            if (!$single_order) header('Location:' . __URL_INIT_DIR__ . 'owner/orders');

            $query = "
                SELECT COUNT(owd.dish_id) AS dish_amount, d.name AS dish_name
                FROM (((orders_with_dishes AS owd
                INNER JOIN orders AS o ON owd.order_id = o.id)
                INNER JOIN dishes AS d ON owd.dish_id = d.id)
                INNER JOIN users AS u ON o.user_id = u.id)
                WHERE o.user_id = u.id AND owd.order_id = :id AND owd.dish_id = d.id
                GROUP BY owd.dish_id;
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('id', $_GET['id'], PDO::PARAM_INT);
            $statement->execute();
            $single_order->dishes_value = $statement->fetchAll(PDO::FETCH_ASSOC);

            $statement->closeCursor();
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::OWNER_ORDER_DETAILS_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'order_id' => $_GET['id'],
            'order_details' => $single_order,
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda zmieniająca staus danego zamówienia w panelu restauratora
     */
    public function order_change()
    {
        if (!isset($_GET['id'])) return;
        try
        {
            $this->dbh->beginTransaction();
            
            $query = "SELECT id FROM orders WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));
            $result = $statement->fetchColumn();
            if (empty($result)) throw new Exception('Podane zamówienie nie istnieje w systemie lub nie należy do twojej restauracji.');

            $query = "UPDATE orders SET status_id = 2, finish_order = NOW() WHERE id = ?;";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));

            $this->_banner_message = 'Pomyślnie zmienio status zamówienia o numerze ' . $_GET['id'] . '.';
            $statement->closeCursor();
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $this->_banner_error = true;
            $this->_banner_message = $e->getMessage();
        }
        SessionHelper::create_session_banner(SessionHelper::OWNER_ORDERS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
    }
}
