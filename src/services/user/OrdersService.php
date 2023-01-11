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
 * Ostatnia modyfikacja: 2023-01-11 22:37:07                   *
 * Modyfikowany przez: BubbleWaffle                            *
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
use App\Services\Helpers\ValidationHelper;

ResourceLoader::load_model('ShowUserOrdersListModel', 'user');
ResourceLoader::load_model('ShowUserSingleOrderModel', 'user');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('ValidationHelper');

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

                setcookie("discount", time() - 3600);

                $this->_banner_message = 'Kod rabatowy został usunięty';
                SessionHelper::create_session_banner(SessionHelper::ORDER_FINISH_PAGE, $this->_banner_message, false);
                header('Location:' . __URL_INIT_DIR__ . 'user/orders/order-finish', true, 301);
            }
        } catch (Exception $e) {
            SessionHelper::create_session_banner(SessionHelper::ORDER_FINISH_PAGE, $this->_banner_message, $this->_banner_error);
        }
        return array(
        );
    }

    public function fillAdress()
    {
        try {
            if (!(isset($_COOKIE['discount'])))
                setcookie("discount", time() - 3600);

            $v_cookie = $_COOKIE['discount'];

            $this->dbh->beginTransaction();
            
            $query = "SELECT id, value FROM discount WHERE name = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(
                array(
                    $v_cookie
                )
            );
            $value_discount = $statement->fetchAll(PDO::FETCH_ASSOC);

            $is_discount_code_exist = false;

            if ($v_cookie != null && count($value_discount) > 0) {
                $is_discount_code_exist = true;
            }

            
            $query = "SELECT street, post_code, city, building_nr, locale_nr FROM user_address WHERE user_id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));
            $user = $statement->fetchAll(PDO::FETCH_ASSOC);

            $v_post_code = array('value' => $user[0]['post_code'], 'invl' => false, 'bts_class' => '');
            $v_city = array('value' => $user[0]['city'], 'invl' => false, 'bts_class' => '');
            $v_street = array('value' => $user[0]['street'], 'invl' => false, 'bts_class' => '');
            $v_building_no = array('value' => $user[0]['building_nr'], 'invl' => false, 'bts_class' => '');
            $v_locale_no = array('value' => $user[0]['locale_nr'], 'invl' => false, 'bts_class' => '');

            // Obsługa dodawania kodu rabatowego do plików cookies
            if (isset($_POST['discount-button'])) {
                $discount = ValidationHelper::validate_field_regex('discount', '/^[\w]+$/');
                // Pobranie ID kodu promocyjnego jeżeli istnieje
                $query = "SELECT id, value FROM discount WHERE name = ?";
                $statement = $this->dbh->prepare($query);
                $statement->execute(
                    array(
                        $discount['value']
                    )
                );
                if ($statement->fetchColumn() == 0) {
                    $this->_banner_message = 'Podany kod rabatowy nie istnieje';
                    SessionHelper::create_session_banner(SessionHelper::ORDER_FINISH_PAGE, $this->_banner_message, true, 'alert-danger');
                    header('Location:' . __URL_INIT_DIR__ . 'user/orders/order-finish', true, 301);
                } else {
                    setcookie("discount", $discount['value']);
                    $this->_banner_message = 'Poprawnie dodano rabat ' . $discount['value'];
                    SessionHelper::create_session_banner(SessionHelper::ORDER_FINISH_PAGE, $this->_banner_message, false);
                    header('Location:' . __URL_INIT_DIR__ . 'user/orders/order-finish', true, 301);
                }
            }

            if (isset($_POST['oder-button'])) {
                $v_building_no = ValidationHelper::validate_field_regex('building-number', Config::get('__REGEX_BUILDING_NO__'));
                if (!empty($_POST['local-number']))
                    $v_locale_no = ValidationHelper::validate_field_regex('local-number', Config::get('__REGEX_BUILDING_NO__'));
                else
                    $v_locale_no = array('value' => $_POST['local-number'], 'invl' => false, 'bts_class' => '');
                $v_post_code = ValidationHelper::validate_field_regex('post-code', Config::get('__REGEX_POSTCODE__'));
                $v_city = ValidationHelper::validate_field_regex('city', Config::get('__REGEX_CITY__'));
                $v_street = ValidationHelper::validate_field_regex('street', Config::get('__REGEX_STREET__'));

                if (!($v_building_no['invl'] || $v_locale_no['invl'] || $v_post_code['invl'] || $v_city['invl'] || $v_street['invl'])) {
                    // Sprawdzanie czy podany adres jest przypisany do jakiegoś użytkownika, bądź istnieje w bazie danych 
                    $query = "SELECT id FROM user_address WHERE street = ? AND building_nr = ? AND locale_nr = ? AND 
                    post_code = ? AND city = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(
                        array(
                            $v_street['value'],
                            $v_building_no['value'],
                            $v_locale_no['value'],
                            $v_post_code['value'],
                            $v_city['value'],
                        )
                    );

                    // Jeżeli podany adres nie istnieje w bazie, to utwórz
                    if ($statement->fetchColumn() == 0) {
                        $query = "INSERT INTO user_address (street, building_nr, locale_nr, post_code, city) VALUES (?,?,?,?,?)";
                        $statement = $this->dbh->prepare($query);
                        $statement->execute(
                            array(
                                $v_street['value'],
                                $v_building_no['value'],
                                $v_locale_no['value'],
                                $v_post_code['value'],
                                $v_city['value'],
                            )
                        );
                    }
                    // Pobranie ID wybranego adresu dostawy
                    $query = "SELECT id FROM user_address WHERE street = ? AND building_nr = ? AND locale_nr = ? AND 
                    post_code = ? AND city = ?	";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(
                        array(
                            $v_street['value'],
                            $v_building_no['value'],
                            $v_locale_no['value'],
                            $v_post_code['value'],
                            $v_city['value'],
                        )
                    );
                    $adress_id = $statement->fetchAll(PDO::FETCH_ASSOC);
                    $price = 69.99;

                    $int_value = (int) $value_discount[0]['value'];

                    if (count($value_discount) > 0 && $price > $int_value) {
                        $price = $price - $int_value;
                    }

                    // Pobranie opcji dostawy przy pomocy radioButton
                    $delivery_type = $_POST["flexRadioDefault"];

                    $query = "INSERT INTO orders (user_id, status_id, discount_id, order_adress, delivery_type, price) VALUES (?,?,?,?,?,?)";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(
                        array(
                            $_SESSION['logged_user']['user_id'],
                            // Status zamówienia z założenia ustawiony na "W trakcie realizacji" 
                            1,
                            $value_discount[0]['id'],
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
            }
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        } catch (Exception $e) {
            $this->dbh->rollback();
            $this->_banner_error = true;
            $this->_banner_message = $e->getMessage();
            SessionHelper::create_session_banner(SessionHelper::ORDER_FINISH_PAGE, $this->_banner_message, $this->_banner_error);
        }
        return array(
            'is_discount_code_exist' => $is_discount_code_exist,
            'v_cookie' => $v_cookie,
            'v_building_no' => $v_building_no,
            'v_locale_no' => $v_locale_no,
            'v_post_code' => $v_post_code,
            'v_city' => $v_city,
            'v_street' => $v_street,
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
