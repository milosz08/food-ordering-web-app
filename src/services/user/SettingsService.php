<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: SettingsService.php                            *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-07, 01:01:34                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-16 19:09:40                   *
 * Modyfikowany przez: cptn3m012                               *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\User\Services;

use PDO;
use Exception;
use App\Core\Config;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\EditUserProfileModel;
use App\Models\AddNewAddresUserModel;
use App\Models\UserAddressModel;
use App\Services\Helpers\ImagesHelper;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\ValidationHelper;

ResourceLoader::load_model('UserAddressModel', 'user');
ResourceLoader::load_model('EditUserProfileModel', 'user');
ResourceLoader::load_model('AddNewAddresUserModel', 'user');
ResourceLoader::load_service_helper('ImagesHelper');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('ValidationHelper');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class SettingsService extends MvcService
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
        $user = new EditUserProfileModel;
        try
        {
            $this->dbh->beginTransaction();

            $query = "
                SELECT first_name, last_name, email, street, post_code, city, building_nr, IFNULL(locale_nr, '') AS locale_nr,
                CONCAT(SUBSTRING(phone_number, 1, 3), ' ', SUBSTRING(phone_number, 3, 3), ' ', SUBSTRING(phone_number, 6, 3)) AS phone_number,
                photo_url AS profile_url
                FROM users INNER JOIN user_address ON users.id = user_address.user_id
                WHERE users.id = ?
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));
            $user = $statement->fetchObject(EditUserProfileModel::class);
            $profile_photo = $user->profile_url['value'];

            if (isset($_POST['save-changes-button']))
            {
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
                $user->phone_number = ValidationHelper::validate_field_regex('user-phone', Config::get('__REGEX_PHONE_PL__'));
                $user->profile_url = ValidationHelper::validate_image_regex('user-profile');

                if ($user->all_is_valid())
                {
                    // Zapytanie zwracające liczbę istniejących już kont o podanym loginie i/lub emailu
                    $query = "SELECT COUNT(id) FROM users WHERE email = ? AND NOT id = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($user->email['value'], $_SESSION['logged_user']['user_id']));
                    if ($statement->fetchColumn() > 0) throw new Exception('Podany email istnieje już w systemie.');

                    $query = "SELECT COUNT(*) FROM users WHERE phone_number = REPLACE(?, ' ', '') AND id <> ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($user->phone_number['value'], $_SESSION['logged_user']['user_id']));
                    if ($statement->fetchColumn() > 0) throw new Exception('Podany numer telefonu jest już zarejestrowany na innym koncie.');

                    $profile_url = ImagesHelper::upload_user_profile_image(
                        $user->profile_url, $_SESSION['logged_user']['user_id'], $profile_photo,
                    );

                    // Sekcja zapytań dodająca wprowadzone dane do tabel users i user_address
                    $query = "
                        UPDATE users SET first_name = ?, last_name = ?, email = ?, phone_number = REPLACE(?, ' ', ''),
                        photo_url = ? WHERE id = ?
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $user->first_name['value'], $user->last_name['value'], $user->email['value'], $user->phone_number['value'],
                        $profile_url, $_SESSION['logged_user']['user_id'],
                    ));
                    $query = "
                        UPDATE user_address SET street = ?, building_nr = ?, locale_nr = NULLIF(?,''), post_code = ?, city = ?
                        WHERE user_id = ? AND is_prime = 1
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $user->street['value'], $user->building_nr['value'], $user->locale_nr['value'], $user->post_code['value'],
                        $user->city['value'], $_SESSION['logged_user']['user_id'],
                    ));

                    $user->profile_url['value'] = $profile_url;
                    $_SESSION['logged_user']['user_full_name'] = $user->first_name['value'] . ' ' . $user->last_name['value'];
                    $_SESSION['logged_user']['user_profile_image'] = $profile_url;
                    $this->_banner_message = 'Zmiany zostały pomyślnie zapisane';
                    SessionHelper::create_session_banner(SessionHelper::USER_SETTINGS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
                }
            }
            $query = "
                SELECT id, CONCAT('ul. ', street, ' ', building_nr, IF(locale_nr, CONCAT('/', locale_nr), '')) AS address,
                CONCAT(post_code, ' ', city) AS post_city, IF(ROW_NUMBER() OVER(ORDER BY id) = 1, 'checked', '') AS checked
                FROM user_address WHERE user_id = ? AND is_prime = 0
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));
            while ($row = $statement->fetchObject(UserAddressModel::class)) array_push($addresses, $row);
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::USER_SETTINGS_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'user' => $user,
            'addresses' => $addresses,
            'has_profile' => !empty($user->profile_url['value']),
            'hide_profile_preview_class' => $user->profile_url['invl'] ? 'display-none' : '',
            'add_address_is_visible' => !(count($addresses) == 3),
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda odpowiadająca za obsługę dodawania nowych adresów użytkownika do bazy danych.
     */
    public function add_new_addres()
    {
        $redir_banner = SessionHelper::USER_SETTINGS_PAGE_BANNER;
        $redir = 'user/settings';
        if (isset($_GET['redir']))
        {
            $redir_banner = SessionHelper::ORDER_SUMMARY_PAGE_BANNER;
            $redir = $_GET['redir'];
        }
        $user = new AddNewAddresUserModel;
        try
        {
            $this->dbh->beginTransaction();

            $query = "SELECT count(*) FROM user_address WHERE user_id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));
            if ($statement->fetchColumn() == 4)
            {
                $this->dbh->commit();
                $statement->closeCursor();
                $message = 'Posiadasz już maksymalną ilość adresów przypisanych do konta.';
                SessionHelper::create_session_banner($redir_banner, $message, true);
                header('Location:' . __URL_INIT_DIR__ . $redir, true, 301);
                die;
            }
            if (isset($_POST['save-changes-add']))
            {
                $user->building_nr = ValidationHelper::validate_field_regex('building-number', Config::get('__REGEX_BUILDING_NO__'));
                if (!empty($_POST['local-number']))
                    $user->locale_nr = ValidationHelper::validate_field_regex('local-number', Config::get('__REGEX_BUILDING_NO__'));
                else
                    $user->locale_nr = array('value' => $_POST['local-number'], 'invl' => false, 'bts_class' => '');
                $user->post_code = ValidationHelper::validate_field_regex('post-code', Config::get('__REGEX_POSTCODE__'));
                $user->city = ValidationHelper::validate_field_regex('city', Config::get('__REGEX_CITY__'));
                $user->street = ValidationHelper::validate_field_regex('street', Config::get('__REGEX_STREET__'));

                if ($user->all_is_valid())
                {
                    // Sekcja zapytań dodająca wprowadzone dane do tabel users i user_address
                    $query = "
                        INSERT INTO user_address (street, building_nr, locale_nr, post_code, city, user_id)
                        VALUES (?,?,NULLIF(?,''),?,?,?)
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $user->street['value'], $user->building_nr['value'], $user->locale_nr['value'], $user->post_code['value'],
                        $user->city['value'], $_SESSION['logged_user']['user_id'],
                    ));
                    $this->dbh->commit();
                    $statement->closeCursor();
                    $this->_banner_message = 'Twój nowy adres został pomyślnie przypisany do Twojego konta.';
                    
                    SessionHelper::create_session_banner($redir_banner, $this->_banner_message, $this->_banner_error);
                    header('Location:' . __URL_INIT_DIR__ . $redir, true, 301);
                    die;
                }
            }
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::ADD_USER_NEW_ADDRESS_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'user' => $user,
            'redir_active' => isset($_GET['redir']),
            'redir_link' => $redir,
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda odpowiadająca za usuwanie wybranego adresu przekazanego do id URL.
     */
    public function delete_address()
    {
        if (!isset($_GET['id'])) return;
        try
        {
            $this->dbh->beginTransaction();

            $query = " DELETE FROM user_address WHERE user_id = ? AND is_prime = 0 AND id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id'], $_GET['id']));

            $this->_banner_message = 'Dodatkowy adres dostawy został pomyślnie usunięty z Twojego konta.';
            $statement->closeCursor();
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->_banner_error = true;
            $this->_banner_message = $e->getMessage();
        }
        SessionHelper::create_session_banner(SessionHelper::USER_SETTINGS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda odpowiada za otworzenie strony używającej view, dodawania adresu.
     */
    public function edit_alternative_address()
    {
        if (!isset($_GET['id'])) header('Location:' . __URL_INIT_DIR__ . 'user/settings', true, 301);
        $user = new UserAddressModel;
        try
        {
            $this->dbh->beginTransaction();
            $query = "
                SELECT street, post_code, city, building_nr, IFNULL(locale_nr, '') AS locale_nr
                FROM user_address WHERE user_id = ? AND id = ?
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id'], $_GET['id']));
            $user = $statement->fetchObject(EditUserProfileModel::class);
            if(empty($user)) header('Location:' . __URL_INIT_DIR__ . 'user/settings', true, 301);

            if (isset($_POST['save-changes-add']))
            {
                $user->building_nr = ValidationHelper::validate_field_regex('building-number', Config::get('__REGEX_BUILDING_NO__'));
                if (!empty($_POST['local-number']))
                    $user->locale_nr = ValidationHelper::validate_field_regex('local-number', Config::get('__REGEX_BUILDING_NO__'));
                else
                    $user->locale_nr = array('value' => $_POST['local-number'], 'invl' => false, 'bts_class' => '');
                $user->post_code = ValidationHelper::validate_field_regex('post-code', Config::get('__REGEX_POSTCODE__'));
                $user->city = ValidationHelper::validate_field_regex('city', Config::get('__REGEX_CITY__'));
                $user->street = ValidationHelper::validate_field_regex('street', Config::get('__REGEX_STREET__'));

                if ($user->all_is_valid())
                {
                    $query = "
                        UPDATE user_address SET street = ?, building_nr = ?, locale_nr = ?, post_code = ?, city = ? 
                        WHERE user_id = ? AND id = ?
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $user->street['value'], $user->building_nr['value'], $user->locale_nr['value'], $user->post_code['value'],
                        $user->city['value'], $_SESSION['logged_user']['user_id'], $_GET['id'],
                    ));
                    $this->dbh->commit();
                    $statement->closeCursor();
                    $this->_banner_message = 'Zmiany zostały pomyślnie zapisane';
                    SessionHelper::create_session_banner(SessionHelper::USER_SETTINGS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
                    header('Location:' . __URL_INIT_DIR__ . 'user/settings' , true, 301);
                }
            }
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::USER_SETTINGS_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'user' => $user
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda odpowiadająca za usuwanie zdjęcia profilowego użytkownika, który jest zalogowany do systemu.
     */
    public function delete_profile_image()
    {
        try
        {
            $this->dbh->beginTransaction();
            $query = "SELECT IFNULL(photo_url, 0) FROM users WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));
            $photo_url = $statement->fetchColumn();
            if ($photo_url == 0) throw new Exception('Wybrany użytkownik nie posiada zdjęcia profilowego.');
            
            $query = "UPDATE users SET photo_url = NULL WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));

            $this->_banner_message = 'Twoje zdjęcie profilowe zostało pomyślnie usunięte z konta.';
            $_SESSION['logged_user']['user_profile_image'] = 'static/images/default-profile-image.jpg';
            if (file_exists($photo_url)) unlink($photo_url);
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $this->_banner_error = true;
            $this->_banner_message = $e->getMessage();
        }
        SessionHelper::create_session_banner(SessionHelper::USER_SETTINGS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda umożliwiająca usunięcie konta użytkownika z systemu. Jedynie użytkownicy bez aktywnych zamówień mogą usunąć konto.
     */
    public function delete_account()
    {
        $password_confirmation = $_POST['password-confirmation'] ?? '';
        try
        {
            $this->dbh->beginTransaction();
            $query = "
                SELECT password FROM users WHERE id = :userid AND
                (SELECT COUNT(*) FROM orders WHERE user_id = :userid AND status_id = 1) = 0
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('userid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
            $statement->execute();
            $user_password = $statement->fetchColumn();
            if (!$user_password) throw new Exception('
                Usunięcie konta z co najmniej jednym aktywnym zamówieniem jest niemożliwe.
            ');
            if (!password_verify($password_confirmation, $user_password)) throw new Exception('
                Nieprawidłowe hasło. Spróbuj ponownie wprowadzając inne hasło.
            ');

            $query = "DELETE FROM users WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));

            rmdir('uploads/users/' . $_SESSION['logged_user']['user_id']);
            unset($_SESSION['logged_user']);
            $this->_banner_message = 'Twoje konto i wszystkie zasoby z nim powiązane zostało pomyślnie usunięte z systemu.';
            SessionHelper::create_session_banner(SessionHelper::LOGIN_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
            if ($this->dbh->inTransaction()) $this->dbh->commit();
            header('Location:' . __URL_INIT_DIR__ . 'auth/login', true, 301);
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            var_dump($e->getMessage());
            SessionHelper::create_session_banner(SessionHelper::USER_SETTINGS_PAGE_BANNER, $e->getMessage(), true);
        }
    }
}
