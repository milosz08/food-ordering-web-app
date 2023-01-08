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
 * Ostatnia modyfikacja: 2023-01-08 01:05:40                   *
 * Modyfikowany przez: Lukasz Krawczyk                         *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\User\Services;

use PDO;
use Exception;
use App\Core\Config;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\ValidationHelper;

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

            $this->dbh->beginTransaction();

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
                    $this->_banner_message = 'Zamówienie zostało pomyślnie złożone';
                    SessionHelper::create_session_banner(SessionHelper::ORDER_FINISH_PAGE, $this->_banner_message, false);
                    header('Location:' . __URL_INIT_DIR__ . 'user/orders/order-finish', true, 301);
                }
            }
            $this->dbh->commit();
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
}
