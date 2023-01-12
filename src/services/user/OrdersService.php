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
 * Ostatnia modyfikacja: 2023-01-12 17:42:56                   *
 * Modyfikowany przez: Lukasz Krawczyk                         *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\User\Services;

use PDO;
use Exception;
use App\Core\Config;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\ShowUserOrdersListModel;
use App\Services\Helpers\SessionHelper;
use App\Models\ShowUserSingleOrderModel;
use App\Services\Helpers\CookieHelper;
use App\Services\Helpers\ValidationHelper;
use App\Models\DishDetailsCartModel;

ResourceLoader::load_model('ShowUserOrdersListModel', 'user');
ResourceLoader::load_model('ShowUserSingleOrderModel', 'user');
ResourceLoader::load_model('DishDetailsCartModel', 'cart');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('ValidationHelper');
ResourceLoader::load_service_helper('CookieHelper');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class OrdersService extends MvcService
{
    private $_banner_message = '';
    private $_banner_error = false;

    protected function __construct()
    {
        parent::__construct();
    }

    public function deleteDiscountCode()
    {
        try {
            if (isset($_POST['delate-code'])) {

                if (!isset($_GET['id'])) header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
                else
                    $resid = $_GET['id'];
                    
                $shopping_cart = array();
                if (isset($_COOKIE[CookieHelper::get_shopping_cart_name($_GET['resid'])])) {
                    $cart_cookie = json_decode($_COOKIE[CookieHelper::get_shopping_cart_name($_GET['resid'])], true);
                    foreach ($cart_cookie as $dish) {
                        $dish['code'] = "";
                        array_push($shopping_cart, $dish);
                    }
                    $this->_banner_message = 'Usunięto rabat';
                    SessionHelper::create_session_banner(SessionHelper::ORDER_FINISH_PAGE, $this->_banner_message, false);
                    header('Location:' . __URL_INIT_DIR__ . 'user/orders/order-summary?resid=' . $resid, true, 301);
                }
            }
        } catch (Exception $e) {
            SessionHelper::create_session_banner(SessionHelper::ORDER_FINISH_PAGE, $this->_banner_message, $this->_banner_error);
        }
        return 1;
    }

    public function fillAdress()
    {
        try {
            $this->dbh->beginTransaction();

            // Tablice pomocnicze kolejno uzupełniająca koszyk oraz obsługująca wartość dostawy restauracji
            $dish_details_not_founded = false;
            $min_price_num = 0;
            $code_found = false;
            $shopping_cart = array();
            $resid = $_GET['resid'];
            $summary_prices = array('total' => '0', 'total_num' => 0, 'total_with_delivery' => '0', 'diff_not_enough' => 0, 
                                        'delivery_price' => '0,00', 'percentage_discount' => '1');
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
                        $summary_prices['total_num'] += (float)str_replace(',', '.', $dish_details->total_dish_cost) * 100;
                        $min_price_num = (float)str_replace(',', '.', $dish_details->min_price) * 100;
                        $summary_prices['delivery_price'] = $dish_details->delivery_price;
                        
                    }
                    else $dish_details_not_founded = true;

                    if(!empty($dish['code']))
                    {
                        $codeName = $dish['code']; 
                        $code_found = true;
                    }
                }
                if($code_found)
                {
                    $query = "SELECT REPLACE(CAST(percentage_discount AS DECIMAL(10,2)), '.', ',') AS percentage_discount 
                    FROM discounts WHERE code = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(
                        array(
                            $codeName
                        )
                    );
                    if ($percentage_discount = $statement->fetchObject()) {
                        $summary_prices['percentage_discount'] = $percentage_discount->percentage_discount;
                    }
                }
                if ($dish_details_not_founded) CookieHelper::delete_cookie(CookieHelper::get_shopping_cart_name($_GET['id']));
                else
                {
                    $delivery = (float)str_replace(',', '.', $dish_details->delivery_price) * 100;

                    if ($summary_prices['percentage_discount'] == 1)
                        $percent = 100;
                    else  
                        $percent = (100 - (float) str_replace(',', '.', $summary_prices['percentage_discount']));

                    $calculate = (($summary_prices['total_num']/100) * $percent);
                    $summary_prices['total'] = number_format(($summary_prices['total_num']*($percent/100)) / 100, 2, ',');
                    $summary_prices['total_with_delivery'] = number_format(($calculate + $delivery) / 100, 2, ',');
                    $summary_prices['diff_not_enough'] = $min_price_num - $summary_prices['total_num'];
                }
            }
            
            // Obsługa dodawania kodu rabatowego do plików cookies
            if (isset($_POST['discount-button'])) {
                if (isset($_COOKIE[CookieHelper::get_shopping_cart_name($_GET['resid'])]))
                {
                    $discount = ValidationHelper::validate_field_regex('discount', '/^[\w]+$/');
                    // Pobranie ID kodu promocyjnego jeżeli istnieje
                    $query = "SELECT id FROM discounts WHERE code = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(
                        array(
                            $discount['value']
                        )
                    );
                    if($statement->fetchColumn() == 0) {
                        $this->_banner_message = 'Podany kod rabatowy nie istnieje';
                        SessionHelper::create_session_banner(SessionHelper::ORDER_FINISH_PAGE, $this->_banner_message, true, 'alert-danger');
                        header('Location:' . __URL_INIT_DIR__ . 'user/orders/order-summary?resid=' . $resid, true, 301);
                    }
                     else {
                        $tempArray = json_decode($_COOKIE[CookieHelper::get_shopping_cart_name($_GET['resid'])]);
                        $isCodeExist = false;
                        $new_json_array = array();
                        foreach($tempArray as $cookieElements)
                        {
                            $cookieElements->code = $discount['value'];
                            array_push($new_json_array, $cookieElements);
                        }
                        if($isCodeExist == false)
                            CookieHelper::set_non_expired_cookie(CookieHelper::get_shopping_cart_name($_GET['resid']), json_encode($new_json_array));
    
                        $this->_banner_message = 'Poprawnie dodano rabat ' . $discount['value'];
                        SessionHelper::create_session_banner(SessionHelper::ORDER_FINISH_PAGE, $this->_banner_message, false);
                        header('Location:' . __URL_INIT_DIR__ . 'user/orders/order-summary?resid=' . $resid, true, 301);
                    }
                }
            }

            /* work in progress
            if (isset($_POST['oder-button'])) {
                    $adress_id = $statement->fetchAll(PDO::FETCH_ASSOC);
                    $price = 69.99;


                    // Pobranie opcji dostawy przy pomocy radioButton
                    $delivery_type = $_POST["flexRadioDefault"];

                    $query = "INSERT INTO orders (user_id, status_id, order_adress, delivery_type, price) VALUES (?,?,?,?,?)";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(
                        array(
                            $_SESSION['logged_user']['user_id'],
                            // Status zamówienia z założenia ustawiony na "W trakcie realizacji" 
                            1,
                            $adress_id[0]['id'],
                            $delivery_type,
                            $price
                        )
                    );
                    $statement->closeCursor();
                    $this->dbh->commit();
                    $this->_banner_message = 'Zamówienie zostało pomyślnie złożone';
                    SessionHelper::create_session_banner(SessionHelper::ORDER_FINISH_PAGE, $this->_banner_message, false);
                    header('Location:' . __URL_INIT_DIR__ . 'user/orders/order-finish', true, 301);
                    die;
                }
            */
            if ($this->dbh->inTransaction()) $this->dbh->commit();

        } catch (Exception $e) {
            $this->dbh->rollback();
            $this->_banner_error = true;
            $this->_banner_message = $e->getMessage();
            SessionHelper::create_session_banner(SessionHelper::ORDER_FINISH_PAGE, $this->_banner_message, $this->_banner_error);
        }
        return array(
            'shopping_cart' => $shopping_cart,
            'summary_prices' => $summary_prices,
            'diff_not_enough' => number_format($summary_prices['diff_not_enough'] / 100, 2, ','),
            'not_enough_total_sum' => $min_price_num > $summary_prices['total_num'],
            'cart_is_empty' => !isset($_COOKIE[CookieHelper::get_shopping_cart_name($_GET['resid'])]),
        );
    }

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
                WHERE o.user_id = :userid;
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('userid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
            $statement->execute();

            /* Pętla wypełniająca tablicę zamówieniami */
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

            if ($one_order->status_id == 3 || $one_order->time_statement == true) {
                $validation = false;
            } else
                $validation = true;

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

    public function cancelOrder()
    {
        $redirect_url = 'user/orders/list';
        if (!isset($_GET['id'])) return $redirect_url;
        try
        {
            $this->dbh->beginTransaction();

            $query = "
                SELECT IF((TIMESTAMPDIFF(SECOND, date_order, NOW())) > 300, true, false) AS dif
                FROM orders
                WHERE user_id = :userid AND id = :id;
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('userid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
            $statement->bindValue('id', $_GET['id'], PDO::PARAM_INT);
            $statement->execute();
            $data = $statement->fetch(PDO::FETCH_ASSOC);

            if ($data['dif'] == true) {
                $this->_banner_message = 'Zamówienie o numerze ' . $_GET['id'] . ' nie może zostać już anulowane!';
                $this->_banner_error = true;
            } else {
                $query = "UPDATE orders SET status_id = 3 WHERE id = ?";
                $statement = $this->dbh->prepare($query);
                $statement->execute(array($_GET['id']));
                $this->_banner_message = 'Pomyślnie anulowano zamówienie o nr: ' . $_GET['id'];
                $statement->closeCursor();
                $this->dbh->commit();
            }

        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $this->_banner_message = $e->getMessage();
            $this->_banner_error = true;
        }
        SessionHelper::create_session_banner(SessionHelper::CANCEL_ORDER, $this->_banner_message, $this->_banner_error);
        return $redirect_url;
    }
}
