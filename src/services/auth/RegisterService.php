<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: RegisterService.php                            *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 19:22:24                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-07 00:42:37                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Auth\Services;

use PDO;
use Exception;
use App\Core\Config;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Helpers\AuthHelper;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\ValidationHelper;

ResourceLoader::load_service_helper('AuthHelper');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('ValidationHelper');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class RegisterService extends MvcService
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
     * Metoda odpowiadająca za dodawanie nowych danych użytkownika i ich sprawdzanie z istniejącą bazą danych.
     */
    public function register_user()
    {
        if(isset($_POST['registration-button']))
        {
            try
            {
                $v_name = ValidationHelper::validate_field_regex('registration-name', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{2,50}$/');
                $v_surname = ValidationHelper::validate_field_regex('registration-surname', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ \-]{2,50}$/');
                $v_login = ValidationHelper::validate_field_regex('registration-login', Config::get('__REGEX_LOGIN__'));
                $v_password = ValidationHelper::validate_field_regex('registration-password', Config::get('__REGEX_PASSWORD__'));
                $v_password_rep = ValidationHelper::validate_exact_fields($v_password, 'registration-password-rep');
                $v_email = ValidationHelper::validate_email_field('registration-email');
                $account_type = $_POST['registration-role'];
                $v_building_no = ValidationHelper::validate_field_regex('registration-building-number', Config::get('__REGEX_BUILDING_NO__'));
                if (!empty($_POST['registration-local-number']))
                    $v_locale_no = ValidationHelper::validate_field_regex('registration-local-number', Config::get('__REGEX_BUILDING_NO__'));
                else
                    $v_locale_no = array('value' => $_POST['registration-local-number'], 'invl' => false, 'bts_class' => '');
                $v_post_code = ValidationHelper::validate_field_regex('registration-post-code', Config::get('__REGEX_POSTCODE__'));
                $v_city = ValidationHelper::validate_field_regex('registration-city', Config::get('__REGEX_CITY__'));
                $v_street = ValidationHelper::validate_field_regex('registration-street', Config::get('__REGEX_STREET__'));

                if (!($v_name['invl'] || $v_surname['invl'] || $v_login['invl'] || $v_password['invl'] || $v_email['invl'] ||
                      $v_building_no['invl'] || $v_locale_no['invl'] || $v_post_code['invl'] || $v_city['invl'] || $v_street['invl'] ||
                      $v_password_rep['invl']))
                {
                    $this->dbh->beginTransaction();
                    // Zapytanie zwracające liczbę istniejących już kont o podanym loginie i/lub emailu
                    $query = "SELECT COUNT(id) FROM users WHERE login = ? OR email = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($v_login['value'], $v_email['value']));
                    
                    if ($statement->fetchColumn() > 0) throw new Exception('Podany użytkownik istnieje już w systemie. Podaj inne dane.');

                    // Sekcja zapytań dodająca wprowadzone dane do tabel users i user_address
                    $query = "INSERT INTO users (first_name, last_name, login, password, email, role_id) VALUES (?,?,?,?,?,?)";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $v_name['value'],
                        $v_surname['value'],
                        $v_login['value'],
                        $this->passwd_hash($v_password['value']),
                        $v_email['value'],
                        (int)$account_type,
                    ));
                    $query = "SELECT id, email, CONCAT(first_name, ' ', last_name) AS full_name FROM users WHERE login = ?";
                    $statement_id = $this->dbh->prepare($query);
                    $statement_id->execute(array($v_login['value']));

                    $query = "INSERT INTO user_address (street, building_locale_nr, post_code, city, user_id) VALUES (?,?,?,?,?)";
                    $statement = $this->dbh->prepare($query);
                    $user_data = $statement_id->fetchAll(PDO::FETCH_ASSOC)[0];
                    $statement->execute(array(
                        $v_street['value'],
                        empty($v_locale_no['value']) ? $v_building_no['value'] : $v_building_no['value'] . '/' . $v_locale_no['value'],
                        $v_post_code['value'],
                        $v_city['value'],
                        $user_data['id'],
                    ));

                    // sekcja odpowiedzialna za generowanie OTA tokenu, wstawiania do tabeli i wysyłanie wiadomości email z wygenerowanym
                    // tokenem. Jeśli wszystko się powiedzie, użytkownik w wiadomości email po kliknięciu aktywuje konto
                    $query = "
                        INSERT INTO ota_user_token (ota_token, expiration_date, user_id, type_id)
                        VALUES (?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), ?, 
                        (SELECT id FROM ota_token_types WHERE type='activate account' LIMIT 1))
                    ";
                    $statement = $this->dbh->prepare($query);
                    $rnd_ota_token = AuthHelper::generate_random_seq();
                    $statement->execute(array($rnd_ota_token, $user_data['id']));

                    $email_request_vars = array(
                        'user_full_name' => $user_data['full_name'],
                        'basic_server_path' => Config::get('__DEF_APP_HOST__'),
                        'ota_token' => $rnd_ota_token,
                        'regenerate_link' => 'auth/account/activate/resend/code?userid=' . $user_data['id'],
                    );
                    $subject = 'Aktywacja konta dla użytkownika ' . $user_data['full_name'];
                    $this->smtp_client->send_message($user_data['email'], $subject, 'activate-account', $email_request_vars);

                    $statement->closeCursor();
                    $statement_id->closeCursor();
                    $this->_banner_message = '
                        Twoje konto zostało pomyślnie stworzone. Aby móc zalogować się na konto, musisz je aktywować przy pomocy linku
                        wysłanego na podany podczas rejestracji adres email. Nieaktywowane konto w przeciągu <strong>48 godzin</strong>
                        zostanie automatycznie usunięte z systemu.
                    ';
                    $this->dbh->commit();
                    SessionHelper::create_session_banner(SessionHelper::LOGIN_PAGE_BANNER, $this->_banner_message,
                        $this->_banner_error, 'alert-warning'
                    );
                    header('Location:' . __URL_INIT_DIR__ . 'auth/login', true, 301);
                }
            }
            catch (Exception $e)
            {
                $this->dbh->rollback();
                $this->_banner_error = true;
                $this->_banner_message = $e->getMessage();
            }
            SessionHelper::create_session_banner(SessionHelper::REGISTER_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
            return array(
                'v_name' => $v_name,
                'v_surname' => $v_surname,
                'v_login' => $v_login,
                'v_password' => $v_password,
                'v_password_rep' => $v_password_rep,
                'v_email' => $v_email,
                'v_building_no' => $v_building_no,
                'v_locale_no' => $v_locale_no,
                'v_post_code' => $v_post_code,
                'v_city' => $v_city,
                'v_street' => $v_street,
            );
        }
    }
}
