<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: SummaryService.php                             *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-13, 04:17:43                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-16 21:09:38                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Order\Services;

use PDO;
use Exception;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\UserAddressModel;
use App\Models\DishDetailsCartModel;
use App\Services\Helpers\CookieHelper;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service_helper('CookieHelper');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_model('UserAddressModel', 'user');
ResourceLoader::load_model('DishDetailsCartModel', 'cart');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class SummaryService extends MvcService
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
     * Metoda zwracająca podsumowane dane zamówienia na podstawie koszyka zachowywanego w pliku cookie oraz wszystkie adresy (dodatkowe oraz
     * ten główny) przypisane do użytkownika
     */
    public function get_order_summary_and_user_addresses()
    {
        if (!isset($_GET['resid'])) header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
        $cookie = $_COOKIE[CookieHelper::get_shopping_cart_name($_GET['resid'])] ?? null;
        $dish_details_not_founded = false;
        $addresses = array();
        $shopping_cart = array();
        try
        {
            $this->dbh->beginTransaction();
            $query = "
                SELECT id, CONCAT('ul. ', street, ' ', building_nr, IF(locale_nr, CONCAT('/', locale_nr), '')) AS address,
                CONCAT(post_code, ' ', city) AS post_city, IF(ROW_NUMBER() OVER(ORDER BY id) = 1, 'checked', '') AS checked,
                IF(is_prime, '(domyślny)', '') AS is_default
                FROM user_address WHERE user_id = ?
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));
            while ($row = $statement->fetchObject(UserAddressModel::class)) array_push($addresses, $row);

            // Sprawdzenie czy podane id restauracji w linku znajduje się w bazie danych 
            $query = "SELECT id FROM restaurants WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['resid']));
            if (!($statement->fetchObject())) header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
            // Walidacja czy podane id w podsumowaniu znajduje się w cookies
            if (!isset($cookie)) header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);

            $cart_cookie = json_decode($cookie, true);
            $summary_prices = array(
                'total' => '0', 'total_num' => 0, 'total_with_delivery' => '0', 'delivery_price' => '0,00',
                'percentage_discount' => '1', 'saving' => '', 'total_without_discount' => ''
            );
            // Pętla iterująca po otrzymanej tablicy zdekodowanego pliku json.
            foreach ($cart_cookie['dishes'] as $dish)
            {
                // Zapytanie pobierające potrzebne szczegóły dania
                $query = "
                    SELECT d.id, d.name, d.description, IFNULL(REPLACE(CAST(r.delivery_price AS DECIMAL(10,2)), '.', ','), '0,00')
                    AS delivery_price,
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
            }
            if ($min_price_num > $summary_prices['total_num'])
            {
                $message = '
                    Aby przejść do finalizacji zamówienia, musisz przekroczyć minimalną wartość produktów w koszyku ustaloną
                    przez właściciela restauracji.
                ';
                $statement->closeCursor();
                $this->dbh->commit();
                SessionHelper::create_session_banner(SessionHelper::RESTAURANT_DISHES_PAGE_BANNER, $message, true);
                header('Location:' . __URL_INIT_DIR__ . 'restaurants/restaurant-dishes?id=' . $_GET['resid'], true, 301);
                die;
            }
            if (!empty($cart_cookie['code']))
            {
                $query = "
                    SELECT REPLACE(CAST(percentage_discount AS DECIMAL(10,2)), '.', ',') AS percentage_discount 
                    FROM discounts WHERE code = ?
                ";
                $statement = $this->dbh->prepare($query);
                $statement->execute(array($cart_cookie['code']));
                if ($percentage_discount = $statement->fetchColumn())
                {
                    $summary_prices['percentage_discount'] = $percentage_discount;
                    $code_name = $cart_cookie['code'];
                }
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
                    $summary_prices['saving'] = number_format(
                        ($summary_prices['total_num'] - ($summary_prices['total_num'] * ($percent / 100))) / 100, 2, ',');
                }
                $calculate = (( $summary_prices['total_num'] / 100) * $percent);
                $summary_prices['total'] = number_format(( $summary_prices['total_num'] * ($percent / 100)) / 100, 2, ',');
                $summary_prices['total_with_delivery'] = number_format(($calculate + $delivery) / 100, 2, ',');
                $summary_prices['total_without_discount'] = number_format($summary_prices['total_num'] / 100, 2, ',');
            }
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::ORDER_SUMMARY_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'addresses' => $addresses,
            'code_name' => $code_name ?? '',
            'res_id' => $_GET['resid'],
            'shopping_cart' => $shopping_cart,
            'summary_prices' => $summary_prices,
            'cart_is_empty' => !isset($cookie),
            'is_still_free_addresses' => count($addresses) < 4
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda odpowiada za usuwanie zawartości koszyka i anulowanie tworzonego zamówienia poprzez powrócenie do strony z restauracją.
     */
    public function cancel_place_order()
    {
        if (!isset($_GET['resid'])) header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
        $cookie = $_COOKIE[CookieHelper::get_shopping_cart_name($_GET['resid'])];
        if (isset($cookie))
        {
            $this->_banner_message = '
                Składanie zamówienia zostało przez Ciebie anulowane oraz wszystkie produkty znajdujące się w koszyku zostały usunięte.
            ';
            CookieHelper::delete_cookie(CookieHelper::get_shopping_cart_name($_GET['resid']));
        }
        else
        {
            $this->_banner_message = 'Wystąpił błąd podczas usuwania zamówienia.';
            $this->_banner_error = true;   
        }
        SessionHelper::create_session_banner(SessionHelper::RESTAURANT_DISHES_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
        return $_GET['resid'];
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda odpowiadająca za zapisywanie nowych danych o zamówieniu, walidacja oraz zwracanie id zamówienia. Metoda uruchamiana jest
     * poprzez formularz składania zamówienia na stronie /order/summary. Dodatkowo wysyłanie wiadomości email do użytkownika i właścicela
     * restauracji.
     */
    public function place_new_order()
    {
        $cookie = $_COOKIE[CookieHelper::get_shopping_cart_name($_POST['resid'])] ?? null;
        if (!isset($_POST['resid']) || !isset($cookie)) header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
        try
        {
            $delivery = $_POST['delivery'];
            $discount_id = null;
            $total_price = 0;
            $estimate_time = 0;
            $order_id = null;
            $this->dbh->beginTransaction();
            $query = "
                SELECT COUNT(*) FROM ((restaurants AS r
                INNER JOIN restaurant_hours AS h ON r.id = h.restaurant_id)
                INNER JOIN weekdays AS wk ON h.weekday_id = wk.id)
                WHERE wk.name_eng = LOWER(DAYNAME(NOW())) AND h.open_hour <= CURTIME() AND h.close_hour >= CURTIME()
                AND r.id = ? AND accept = 1
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_POST['resid']));
            if ($statement->fetchColumn() == 0)
            {
                $this->_banner_message = 'Wybrana restauracja nie istnieje, bądź nie jest otwarta.';
                SessionHelper::create_session_banner(SessionHelper::HOME_RESTAURANTS_LIST_PAGE_BANNER, $this->_banner_message, true);
                header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
                die;
            }
            $query = "SELECT COUNT(id) FROM orders WHERE user_id = ? AND status_id = 1";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));
            $count_of_active_orders = $statement->fetchColumn();
            if ($count_of_active_orders > 5)
            {
                $this->_banner_message = '
                    Masz aktualnie ' . $count_of_active_orders . ' aktywnych zamówień. Nowego zamówienia możesz dokonać, jeśli liczba
                    twoich aktywnych zamówień jest mniejsza niż 5.
                ';
                SessionHelper::create_session_banner(SessionHelper::HOME_RESTAURANTS_LIST_PAGE_BANNER, $this->_banner_message, true);
                header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
                die;
            }
            $cart_cookie = json_decode($cookie, true);
            if (!isset($cart_cookie)) header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
            foreach ($cart_cookie['dishes'] as $dish)
            {
                $query = "
                    SELECT price * :count AS total,
                    (prepared_time + prepared_time * ((:count - 1) * 0.5)) * 60 AS estimate_prepared_time
                    FROM dishes WHERE id = :id
                ";
                $statement = $this->dbh->prepare($query);
                $statement->bindValue('id', $dish['dishid'], PDO::PARAM_INT);
                $statement->bindValue('count', $dish['count'], PDO::PARAM_INT);
                $statement->execute();
                $dish_values = $statement->fetch(PDO::FETCH_ASSOC);
                $total_price  += ((float) $dish_values['total']) * 100;
                $estimate_time += (int) $dish_values['estimate_prepared_time'];
            }
            if (!empty($cart_cookie['code'])) // cena po rabacie jezeli jest
            {
                $discount_data = $cart_cookie['code'];
                $query = "
                    SELECT d.id, r.delivery_price AS delivery_price,
                    CAST((((100 - percentage_discount) / 100 * :price) + IFNULL(r.delivery_price, 0)) AS DECIMAL(10,2)) AS total_price
                    FROM discounts AS d INNER JOIN restaurants AS r
                    ON d.restaurant_id = r.id WHERE d.code = :discount_data AND d.restaurant_id= :resid
                ";
                $statement = $this->dbh->prepare($query);
                $statement->bindValue('discount_data', $discount_data);
                $statement->bindValue('resid', $_POST['resid'], PDO::PARAM_INT);
                $statement->bindValue('price', $total_price / 100);
                $statement->execute();
                $temp = $statement->fetch(PDO::FETCH_ASSOC);
                $discount_id = $temp['id'];
                $total_price = $temp['total_price'];

                $query = "UPDATE discounts SET usages = usages + 1 WHERE id = ?";
                $statement = $this->dbh->prepare($query);
                $statement->execute(array($discount_id));
            }
            else $total_price /= 100;
            // uzyskiwanie adresu
            $query = "SELECT id FROM user_address WHERE user_id = ? AND is_prime = 1";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));
            $user_address = $statement->fetchColumn();
            if (!$user_address) header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);

            // dodawanie zamowienia do bazy
            $query = "
                INSERT INTO orders(user_id, discount_id, order_adress, delivery_type, restaurant_id, price, date_order, estimate_time)
                VALUES(?,?,?,?,?,?,NOW(),SEC_TO_TIME(?))
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array(
                $_SESSION['logged_user']['user_id'], $discount_id, $user_address, $delivery, $_POST['resid'], $total_price, $estimate_time,
            ));

            //pobieranie id generowanego zamowienia
            $query = "SELECT LAST_INSERT_ID()";
            $statement = $this->dbh->prepare($query);
            $statement->execute();   
            $order_id = $statement->fetchColumn();

            //dodawanie zamowionych dań do orders_with_dishes
            foreach ($cart_cookie['dishes'] as $dish)
            {
                $query = "INSERT INTO orders_with_dishes (order_id, dish_id) VALUES(?,?)";
                $statement = $this->dbh->prepare($query);
                $statement->execute(array($order_id, $dish['dishid']));
            }
            $this->_banner_message = '
                Zamówienie o numerze <strong>#' . $order_id . '</strong> zostało prawidłowo złożone i posiada w obecnej chwili status
                <strong>w przygotowaniu</strong>. Aby śledzić zamówienie, przejdź <a href="' . __URL_INIT_DIR__ . 'user/profile" 
                class="alert-link">tutaj</a>.
            ';
            CookieHelper::delete_cookie(CookieHelper::get_shopping_cart_name($_POST['resid']));
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $this->_banner_error = true;
            $this->_banner_message = $e->getMessage();
        }
        SessionHelper::create_session_banner(SessionHelper::NEW_ORDER_DETAILS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
        return $order_id;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda odpowiadająca za pobieranie szczegółów nowo stworzonego zamówienia i zwracanie ich do widoku.
     */
    public function get_new_order_details()
    {
        if (!isset($_GET['id'])) header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
        try
        {
            $this->dbh->beginTransaction();

            // tutaj kod

            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::NEW_ORDER_DETAILS_PAGE_BANNER, $e->getMessage(), true);
        }
        return array();
    }
}
