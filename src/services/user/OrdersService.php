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
 * Ostatnia modyfikacja: 2023-01-13 00:30:17                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\User\Services;

use PDO;
use Exception;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\DishDetailsCartModel;
use App\Models\ShowUserOrdersListModel;
use App\Models\ShowUserSingleOrderModel;
use App\Services\Helpers\CookieHelper;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_model('DishDetailsCartModel', 'cart');
ResourceLoader::load_model('ShowUserOrdersListModel', 'user');
ResourceLoader::load_model('ShowUserSingleOrderModel', 'user');
ResourceLoader::load_service_helper('CookieHelper');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('ValidationHelper');

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

    public function fillShoppingCard()
    {
        try
        {
            $this->dbh->beginTransaction();
            // Tablice pomocnicze kolejno uzupełniająca koszyk oraz obsługująca wartość dostawy restauracji
            $dish_details_not_founded = false;
            $codeName = "";
            $min_price_num = 0;
            $code_found = false;
            $shopping_cart = array();
            if (isset($_GET['resid']))
            {
                $resid = $_GET['resid'];
                // Sprawdzenie czy podane id restauracji w linku znajduje się w bazie danych 
                $query = "SELECT id FROM restaurants WHERE id = ?";
                $statement = $this->dbh->prepare($query);
                $statement->execute(array($resid));
                if (!($statement->fetchObject())) header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
                else {
                    // Walidacja czy podane id w podsumowaniu znajduje się w cookies
                    if(!isset($_COOKIE[CookieHelper::get_shopping_cart_name($_GET['resid'])]))
                        header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
                }
            }
            else header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
            $summary_prices = array(
                'total' => '0',
                'total_num' => 0,
                'total_with_delivery' => '0',
                'diff_not_enough' => 0,
                'delivery_price' => '0,00',
                'percentage_discount' => '1',
                'saving' => ''
            );
            if (isset($_COOKIE[CookieHelper::get_shopping_cart_name($_GET['resid'])]))
            {
                $cart_cookie = json_decode($_COOKIE[CookieHelper::get_shopping_cart_name($_GET['resid'])], true);
                // Pętla iterująca po otrzymanej tablicy zdekodowanego pliku json.
                foreach ($cart_cookie as $dish)
                {
                    // Zapytanie pobierające potrzebne szczegóły dania
                    $query = "
                        SELECT d.id, d.name, d.description, REPLACE(CAST(r.delivery_price AS DECIMAL(10,2)), '.', ',') AS delivery_price,
                        REPLACE(CAST(d.price * :count AS DECIMAL(10,2)), '.', ',') AS total_dish_cost, 
                        IFNULL(REPLACE(CAST(min_price AS DECIMAL(10,2)), '.', ','), 0) AS min_price
                        FROM dishes d
                        INNER JOIN restaurants r ON d.restaurant_id = r.id WHERE d.id = :id
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->bindValue('count', $dish['count'], PDO::PARAM_INT);
                    $statement->bindValue('id', $dish['dishid']);
                    $statement->execute();
                    // sprawdź, czy podana potrawa z id odczytanym z jsona istnieje, jeśli nie, nie dodawaj do koszyka
                    if ($dish_details = $statement->fetchObject(DishDetailsCartModel::class))
                    {
                        // Uzupełnienie tablicy przechowującej szczegóły dania 
                        array_push($shopping_cart, array('cart_dishes' => $dish_details, 'count_of_dish' => $dish['count']));
                        $summary_prices['total_num'] += (float) str_replace(',', '.', $dish_details->total_dish_cost) * 100;
                        $min_price_num = (float) str_replace(',', '.', $dish_details->min_price) * 100;
                        $summary_prices['delivery_price'] = $dish_details->delivery_price;
                    }
                    else $dish_details_not_founded = true;
                    if (!empty($dish['code']))
                    {
                        $codeName = $dish['code'];
                        $code_found = true;
                    }
                }
                if ($code_found)
                {
                    $query = "
                        SELECT REPLACE(CAST(percentage_discount AS DECIMAL(10,2)), '.', ',') AS percentage_discount 
                        FROM discounts WHERE code = ?
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($codeName));
                    if ($percentage_discount = $statement->fetchObject())
                        $summary_prices['percentage_discount'] = $percentage_discount->percentage_discount;
                }
                if ($dish_details_not_founded) CookieHelper::delete_cookie(CookieHelper::get_shopping_cart_name($_GET['id']));
                else
                {
                    $delivery = (float) str_replace(',', '.', $dish_details->delivery_price) * 100;
                    // Jeżeli kod nie został przypisany, to wartość procentowa wyniesie 100, aby wzięte zostało 100% ceny
                    if ($summary_prices['percentage_discount'] == 1) $percent = 100;
                    else
                    {
                        // Przypisanie wartości, którą posiada kod rabatowy
                        $percent = (100 - (float) str_replace(',', '.', $summary_prices['percentage_discount']));
                        // Obliczenie ile zaoszczędził użytkownik
                        $summary_prices['saving'] = number_format(($summary_prices['total_num'] -
                            ($summary_prices['total_num'] * ($percent / 100))) / 100, 2, ',');
                    }
                    $calculate = (($summary_prices['total_num'] / 100) * $percent);
                    $summary_prices['total'] = number_format(($summary_prices['total_num'] * ($percent / 100)) / 100, 2, ',');
                    $summary_prices['total_with_delivery'] = number_format(($calculate + $delivery) / 100, 2, ',');
                    $summary_prices['diff_not_enough'] = $min_price_num - $summary_prices['total_num'];
                }
            }
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::ORDER_SUMMARY_PAGE, $e->getMessage(), true);
        }
        return array(
            'codeName' => $codeName,
            'resid' => $resid,
            'shopping_cart' => $shopping_cart,
            'summary_prices' => $summary_prices,
            'diff_not_enough' => number_format($summary_prices['diff_not_enough'] / 100, 2, ','),
            'not_enough_total_sum' => $min_price_num > $summary_prices['total_num'],
            'cart_is_empty' => !isset($_COOKIE[CookieHelper::get_shopping_cart_name($_GET['resid'])]),
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function dynamicOrdersList()
    {
        $all_orders = array();
        try
        {
            $this->dbh->beginTransaction();
            $query = "
                SELECT o.id, r.name, o.price, IF(o.status_id = 3, true, false) AS order_statement
                FROM orders AS o
                INNER JOIN restaurants AS r ON o.restaurant_id = r.id
                WHERE o.user_id = ?;
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
        }
        return array(
            'orders' => $all_orders,
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function dynamicSingleOrder()
    {
        $one_order = new ShowUserSingleOrderModel;
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

            $validation = !($one_order->status_id == 3 || $one_order->time_statement);
            $statement->closeCursor();
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
        }
        return array(
            'one_order' => $one_order,
            'validation' => $validation,
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function cancelOrder()
    {
        $redirect_url = 'user/orders/list';
        if (!isset($_GET['id'])) return $redirect_url;
        try
        {
            $this->dbh->beginTransaction();
            $query = "
                SELECT IF((TIMESTAMPDIFF(SECOND, date_order, NOW())) > 300, true, false) AS dif FROM orders
                WHERE user_id = :userid AND id = :id;
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('userid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
            $statement->bindValue('id', $_GET['id'], PDO::PARAM_INT);
            $statement->execute();
            $data = $statement->fetch(PDO::FETCH_ASSOC);

            if ($data['dif']) throw new Exception('Czas na anulowanie zamówienia o numerze ' . $_GET['id'] . ' minał!');

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
        SessionHelper::create_session_banner(SessionHelper::CANCEL_ORDER, $this->_banner_message, $this->_banner_error);
        return $redirect_url;
    }
}
