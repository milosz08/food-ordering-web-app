<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: ProfileService.php                             *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 21:12:35                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-15 23:47:26                   *
 * Modyfikowany przez: Lukasz Krawczyk                         *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\User\Services;

use Exception;
use App\Core\Config;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\EditUserProfileModel;
use App\Models\AddNewAddresUserModel;
use App\Models\UserAddressModel;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\ValidationHelper;

ResourceLoader::load_model('EditUserProfileModel', 'user');
ResourceLoader::load_model('AddNewAddresUserModel', 'user');
ResourceLoader::load_model('UserAddressModel', 'user');

ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('ValidationHelper');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class ProfileService extends MvcService
{
    private $_banner_message = '';
    private $_banner_error = false;

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    protected function __construct()
    {
        parent::__construct();
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /*
     * Metoda odpowiadająca edycji profilu zalogowanego użytkownika.
     */
    public function edit_user_profile()
    {
        $addresses = array();
        $addAddressVisable = true;
        $selectedAddressId = null;
        $user = new EditUserProfileModel;
        try {
            $this->dbh->beginTransaction();

            $query = "
                SELECT first_name, last_name, email, street, post_code, city, building_nr, IFNULL(locale_nr, '') AS locale_nr
                FROM users INNER JOIN user_address ON users.id = user_address.user_id
                WHERE users.id = ?
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));
            $user = $statement->fetchObject(EditUserProfileModel::class);

            if (isset($_POST['save-changes-button'])) {
                $user->first_name = ValidationHelper::validate_field_regex('name', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{2,50}$/');
                $user->last_name = ValidationHelper::validate_field_regex('surname', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ \-]{2,50}$/');
                $user->email = ValidationHelper::validate_email_field('email');
                $user->building_nr = ValidationHelper::validate_field_regex('building-number', Config::get('__REGEX_BUILDING_NO__'));
                if (!empty($_POST['local-number']))
                    $user->locale_nr = ValidationHelper::validate_field_regex('local-number', Config::get('__REGEX_BUILDING_NO__'));
                else
                    $user->locale_nr = array('value' => $_POST['local-number'], 'invl' => false, 'bts_class' => '');
                $user->post_code = ValidationHelper::validate_field_regex('post-code', Config::get('__REGEX_POSTCODE__'));
                $user->city = ValidationHelper::validate_field_regex('city', Config::get('__REGEX_CITY__'));
                $user->street = ValidationHelper::validate_field_regex('street', Config::get('__REGEX_STREET__'));

                if ($user->all_is_valid()) {
                    // Zapytanie zwracające liczbę istniejących już kont o podanym loginie i/lub emailu
                    $query = "SELECT COUNT(id) FROM users WHERE email = ? AND NOT id = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($user->email['value'], $_SESSION['logged_user']['user_id']));
                    if ($statement->fetchColumn() > 0)
                        throw new Exception('Podany email istnieje już w systemie.');

                    // Sekcja zapytań dodająca wprowadzone dane do tabel users i user_address
                    $query = "UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ? ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(
                        array(
                            $user->first_name['value'],
                            $user->last_name['value'],
                            $user->email['value'],
                            $_SESSION['logged_user']['user_id'],
                        )
                    );

                    $query = "
                        UPDATE user_address SET street = ?, building_nr = ?, locale_nr = NULLIF(?,''), post_code = ?, city = ?
                        WHERE user_id = ? AND is_prime = 1
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(
                        array(
                            $user->street['value'],
                            $user->building_nr['value'],
                            $user->locale_nr['value'],
                            $user->post_code['value'],
                            $user->city['value'],
                            $_SESSION['logged_user']['user_id'],
                        )
                    );

                    $this->_banner_message = 'Zmiany zostały pomyślnie zapisane';
                    SessionHelper::create_session_banner(SessionHelper::USER_PROFILE_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
                    header('Location:' . __URL_INIT_DIR__ . 'user/profile' , true, 301);
                }
            }

            $query = "
                SELECT id, CONCAT('ul. ', street, ' ', building_nr, IF(locale_nr, CONCAT('/', locale_nr), '')) AS address, CONCAT(post_code, ' ', city)
                AS post_city, IF(ROW_NUMBER() OVER(ORDER BY id) = 1, 'checked', '') AS checked FROM user_address WHERE user_id = ? AND is_prime = 0
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));
            $number = 0;
            while ($row = $statement->fetchObject(UserAddressModel::class)) {
                array_push($addresses, $row);
                $number++;
            }
            // jeżeli załadowano 4 adresy to przycisk zniknie dzięki tej zmiennej.
            if ($number == 3)
                $addAddressVisable = false;

            // Wybranie usunięcia adresu 
            if (isset($_POST['delete-selected'])) {
                $selectedAddressId = $_POST['address'];
                header('Location:' . __URL_INIT_DIR__ . 'user/profile/delete-address?id=' . $selectedAddressId, true, 301);
            }
            // Wybranie edycji adresu
            if (isset($_POST['edit-address'])) {
                $selectedAddressId = $_POST['address'];
                header('Location:' . __URL_INIT_DIR__ . 'user/profile/edit-alternativ-address?id=' . $selectedAddressId, true, 301);
            }


            if ($this->dbh->inTransaction())
                $this->dbh->commit();

        } catch (Exception $e) {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::USER_PROFILE_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'user' => $user,
            'addresses' => $addresses,
            'addAddressVisable' => $addAddressVisable,
            'selectedAddressId' => $selectedAddressId
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda odpowiadająca za obsługę dodawania nowych adresów użytkownika do bazy danych.
     */
    public function add_new_addres()
    {
        $user = new AddNewAddresUserModel;
        $number_of_address = 4;
        try {
            $this->dbh->beginTransaction();

            if (isset($_POST['save-changes-add'])) {
                $query = "
                    SELECT count(*) FROM user_address WHERE user_id = ?
                ";
                $statement = $this->dbh->prepare($query);
                $statement->execute(array($_SESSION['logged_user']['user_id']));
                $total_records_of_address = $statement->fetchColumn();

                if ($number_of_address <= $total_records_of_address) {
                    throw new Exception('Posiadasz już maksymalną ilość adresów w bazie danych.');
                }

                $user->building_nr = ValidationHelper::validate_field_regex('building-number', Config::get('__REGEX_BUILDING_NO__'));
                if (!empty($_POST['local-number']))
                    $user->locale_nr = ValidationHelper::validate_field_regex('local-number', Config::get('__REGEX_BUILDING_NO__'));
                else
                    $user->locale_nr = array('value' => $_POST['local-number'], 'invl' => false, 'bts_class' => '');
                $user->post_code = ValidationHelper::validate_field_regex('post-code', Config::get('__REGEX_POSTCODE__'));
                $user->city = ValidationHelper::validate_field_regex('city', Config::get('__REGEX_CITY__'));
                $user->street = ValidationHelper::validate_field_regex('street', Config::get('__REGEX_STREET__'));

                if ($user->all_is_valid()) {
                    // Sekcja zapytań dodająca wprowadzone dane do tabel users i user_address
                    $query = "
                        INSERT INTO user_address (street, building_nr, locale_nr, post_code, city, user_id)
                        VALUES (?,?,NULLIF(?,''),?,?,?)
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(
                        array(
                            $user->street['value'],
                            $user->building_nr['value'],
                            $user->locale_nr['value'],
                            $user->post_code['value'],
                            $user->city['value'],
                            $_SESSION['logged_user']['user_id'],
                        )
                    );
                    $this->dbh->commit();
                    $statement->closeCursor();
                    $this->_banner_message = 'Twój nowy adres został dodany do bazy danych.';
                    SessionHelper::create_session_banner(SessionHelper::USER_PROFILE_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
                    header('Location:' . __URL_INIT_DIR__ . 'user/profile', true, 301);
                    die;
                }
                if ($this->dbh->inTransaction())
                    $this->dbh->commit();
            }
        } catch (Exception $e) {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::ADD_USER_NEW_ADDRESS_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'user' => $user,
        );
    }


    // funkcja odpowiadająca za usuwanie wybranego adresu przekazanego do id URL
    public function delete_address()
    {
        try {
            if (!isset($_GET['id']))
                header('Location:' . __URL_INIT_DIR__ . 'user/profile', true, 301);
            else {
                $addresID = $_GET['id'];
                $query = " DELETE FROM user_address WHERE user_id = ? AND is_prime = 0 AND id = ?";
                $statement = $this->dbh->prepare($query);
                $statement->execute(array($_SESSION['logged_user']['user_id'], $addresID));

                $this->_banner_message = 'Adres został poprawnie usunięty';
                SessionHelper::create_session_banner(SessionHelper::USER_PROFILE_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
                header('Location:' . __URL_INIT_DIR__ . 'user/profile', true, 301);
            }

        } catch (Exception $e) {
            SessionHelper::create_session_banner(SessionHelper::USER_PROFILE_PAGE_BANNER, $e->getMessage(), true);
        }
        return 1;
    }

    // funkcja odpowiada za otworzenie strony używającej view, dodawania adresu 
    public function edit_alternativ_address()
    {
        $user = new UserAddressModel;
        try {
            if (!isset($_GET['id']))
                header('Location:' . __URL_INIT_DIR__ . 'user/profile', true, 301);
            else {
                $this->dbh->beginTransaction();
                $addressID = $_GET['id'];

                $query = "
                SELECT  street, post_code, city, building_nr, IFNULL(locale_nr, '') AS locale_nr
                FROM user_address WHERE user_id = ? AND id = ?
                ";
                $statement = $this->dbh->prepare($query);
                $statement->execute(array($_SESSION['logged_user']['user_id'], $addressID));
                $user = $statement->fetchObject(EditUserProfileModel::class);

                if(empty($user))
                    header('Location:' . __URL_INIT_DIR__ . 'user/profile', true, 301);

                if (isset($_POST['save-changes-add'])) {
                    $user->building_nr = ValidationHelper::validate_field_regex('building-number', Config::get('__REGEX_BUILDING_NO__'));
                    if (!empty($_POST['local-number']))
                        $user->locale_nr = ValidationHelper::validate_field_regex('local-number', Config::get('__REGEX_BUILDING_NO__'));
                    else
                        $user->locale_nr = array('value' => $_POST['local-number'], 'invl' => false, 'bts_class' => '');
                    $user->post_code = ValidationHelper::validate_field_regex('post-code', Config::get('__REGEX_POSTCODE__'));
                    $user->city = ValidationHelper::validate_field_regex('city', Config::get('__REGEX_CITY__'));
                    $user->street = ValidationHelper::validate_field_regex('street', Config::get('__REGEX_STREET__'));

                    if ($user->all_is_valid()) {

                        $query = "
                        UPDATE user_address SET street = ?, building_nr = ?, locale_nr = ?, post_code = ?, city = ? 
                        WHERE user_id = ? AND id = ?
                    ";
                        $statement = $this->dbh->prepare($query);
                        $statement->execute(
                            array(
                                $user->street['value'],
                                $user->building_nr['value'],
                                $user->locale_nr['value'],
                                $user->post_code['value'],
                                $user->city['value'],
                                $_SESSION['logged_user']['user_id'],
                                $addressID
                            )
                        );
                        $this->dbh->commit();
                        $statement->closeCursor();
                        $this->_banner_message = 'Zmiany zostały pomyślnie zapisane';
                        SessionHelper::create_session_banner(SessionHelper::USER_PROFILE_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
                        header('Location:' . __URL_INIT_DIR__ . 'user/profile' , true, 301);

                    }
                }
                if ($this->dbh->inTransaction())
                    $this->dbh->commit();
            }
        } catch (Exception $e) {
            SessionHelper::create_session_banner(SessionHelper::USER_PROFILE_PAGE_BANNER, $e->getMessage(), true);
        }
        return array('user' => $user);
    }
}
