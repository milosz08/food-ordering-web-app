<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: AuthService.php                                *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-11-24, 11:15:26                       *
 * Autor: Blazej Kubicius                                      *
 *                                                             *
 * Ostatnia modyfikacja: 2022-12-03 18:26:56                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services;

use PDO;
use Exception;

use App\Core\Config;
use App\Utils\Utils;
use App\Core\MvcService;

class AuthService extends MvcService
{
    private $_banner_message = '';
    private $_show_banner = false;
    private $_banner_error = false;

    //--------------------------------------------------------------------------------------------------------------------------------------

    protected function __construct()
    {
        parent::__construct();
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda odpowiadająca za dodawanie nowych danych użytkownika i ich sprawdzanie z istniejącą bazą danych.
     */
    public function register()
    {
        if(isset($_POST['registration-button']))
        {
            try
            {
                $v_name = Utils::validate_field_regex('registration-name', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{2,50}$/');
                $v_surname = Utils::validate_field_regex('registration-surname', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{2,50}$/');
                $v_login = Utils::validate_field_regex('registration-login', '/^[a-zA-Z0-9]{5,30}$/');
                $v_password = Utils::validate_field_regex('registration-password', '/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/');
                $v_email = Utils::validate_email_field('registration-email');
                $account_type = $_POST['registration-role'];
                $v_building_no = Utils::validate_field_regex('registration-building-number', '/^[0-9]{1,5}$/');
                if (!empty($_POST['registration-local-number']))
                    $v_locale_no = Utils::validate_field_regex('registration-local-number', '/^([0-9]+(?:[a-z]{0,1})){1,5}$/');
                else
                    $v_locale_no = array('value' => $_POST['registration-local-number'], 'invl' => false, 'bts_class' => '');
                $v_post_code = Utils::validate_field_regex('registration-post-code', '/^[0-9]{2}-[0-9]{3}$/');
                $v_city = Utils::validate_field_regex('registration-city', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{2,60}$/');
                $v_street = Utils::validate_field_regex('registration-street', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{2,100}$/');

                $this->dbh->beginTransaction();

                if (!($v_name['invl'] || $v_surname['invl'] || $v_login['invl'] || $v_password['invl'] || $v_email['invl'] ||
                    $v_building_no['invl'] || $v_locale_no['invl'] || $v_post_code['invl'] || $v_city['invl'] || $v_street['invl']))
                {
                    // Zapytanie zwracające liczbę istniejących już kont o podanym loginie i/lub emailu
                    $query = "SELECT COUNT(id) FROM users WHERE login = ? OR email = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $v_login['value'],
                        $v_email['value']
                    ));
                    
                    if ($statement->fetchColumn() > 0) throw new Exception('Podany użytkownik istnieje już w systemie. Podaj inne dane.');

                    // Sekcja zapytań dodająca wprowadzone dane do tabel users i user_address
                    $query = "INSERT INTO users (first_name, last_name, login, password, email, role_id) VALUES (?,?,?,?,?,?)";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $v_name['value'],
                        $v_surname['value'],
                        $v_login['value'],
                        sha1($v_password['value']),
                        $v_email['value'],
                        (int)$account_type,
                    ));
                    $query = "SELECT id FROM users WHERE login = ?";
                    $statement_id = $this->dbh->prepare($query);
                    $statement_id->execute(array($v_login['value']));

                    $query = "INSERT INTO user_address (street, building_locale_nr, post_code, city, user_id) VALUES (?,?,?,?,?)";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $v_street['value'],
                        empty($v_locale_no['value']) ? $v_building_no['value'] : $v_building_no['value'] . '/' . $v_locale_no['value'],
                        $v_post_code['value'],
                        $v_city['value'],
                        $statement_id->fetch(PDO::FETCH_NUM)[0],
                    ));
                    $statement->closeCursor();
                    $statement_id->closeCursor();
                    header('Location:index.php?action=auth/login');
                }
                $this->dbh->commit();
            }
            catch (Exception $e)
            {   
                $this->dbh->rollback();
                $this->_banner_message = $e->getMessage();
                $this->_banner_error = true;
            }
            return array(
                'v_name' => $v_name,
                'v_surname' => $v_surname,
                'v_login' => $v_login,
                'v_password' => $v_password,
                'v_email' => $v_email,
                'v_building_no' => $v_building_no,
                'v_locale_no' => $v_locale_no,
                'v_post_code' => $v_post_code,
                'v_city' => $v_city,
                'v_street' => $v_street,
                'banner_message' => $this->_banner_message,
                'banner_error' => $this->_banner_error,
            );
        }
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda odpowiadający za pobieranie danych użytkownika i ich sprawdzanie z istniejącą bazą danych.
     * Jeśli użytkownik istnieje następuje (tymczasowo) przekierowanie do strony głównej.
     */
    public function login_user()
    {
        if (isset($_POST['form-login']))
        {
            $login = Utils::validate_field_regex('login', '/^[a-zA-Z0-9@.]{5,100}$/');
            $password = Utils::validate_field_regex('pass', '/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/');
            try
            {
                // zapytanie pobierające użytkownika na podstawie loginu oraz zahaszowanego hasła
                $query = "
                    SELECT users.id FROM users INNER JOIN roles ON users.role_id=roles.id 
                    WHERE (login = :login OR email = :login) AND password = :pass
                ";
                $statement = $this->dbh->prepare($query);
                $statement->bindValue(':login', $login['value']);
                $statement->bindValue(':pass', sha1($password['value']));
                $statement->execute();
                if (count($statement->fetchAll()) > 0) header('Location:index.php?action=home/welcome');
                throw new Exception('Nieprawidłowy login i/lub hasło. Spróbuj ponownie.');
            }
            catch (Exception $e)
            {
                $this->_banner_message = $e->getMessage();
                $this->_banner_error = true;
            }
            return array(
                'v_login' => $login,
                'v_password' => $password,
                'banner_message' => $this->_banner_message,
                'banner_error' => $this->_banner_error,
            );
        }
    }
    
    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda odpowiadająca za wysłanie rządania zmiany zapomnianego hasła. Metoda na podstawie podanego adresu email/loginu wysyłana na
     * skorelowany z nim adres email wiadomość z linkiem i tokenem umożliwiającym zmianę hasła.
     */
    public function attempt_renew_password()
    {
        $login_email = '';
        if (isset($_POST['form-send-request-change-pass']))
        {
            $login_email = Utils::validate_field_regex('login_email', '/^[a-zA-Z0-9@.]{5,100}$/');
            if ($login_email['invl'])
            {
                return array(
                    'v_login_email' => $login_email,
                    'banner_message' => $this->_banner_message,
                    'banner_error' => $this->_banner_error,
                );
            }
            try
            {
                $this->dbh->beginTransaction();

                $query = "
                    SELECT id, email, CONCAT(first_name, ' ', last_name) AS full_name FROM users
                    WHERE login = :login_email OR email = :login_email
                ";
                $statement = $this->dbh->prepare($query);
                $statement->bindValue(':login_email', $login_email['value']);
                $statement->execute();

                $user_data = $statement->fetchAll(PDO::FETCH_ASSOC);
                if (empty($user_data))
                    throw new Exception('Podany login/adres email nie jest przypisany do żadnego konta w systemie.');

                $user_data = $user_data[0];
                $query = "
                    INSERT INTO ota_user_token (ota_token, expiration_date, user_id)
                    VALUES (?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), ?)
                ";
                $statement = $this->dbh->prepare($query);
                $rnd_ota_token = Utils::generate_random_seq();
                $statement->execute(array(
                    $rnd_ota_token,
                    $user_data['id'],
                ));

                $email_request_vars = array(
                    'user_full_name' => $user_data['full_name'],
                    'basic_server_path' => Config::get('__DEF_APP_HOST__'),
                    'ota_token' => $rnd_ota_token,
                );
                $subject = 'Reset hasła dla konta ' . $user_data['full_name'];
                $this->smtp_client->send_message($user_data['email'], $subject, 'renew-password', $email_request_vars);

                $this->_banner_message = 'Na adres email ' . $user_data['email'] . ' została wysłana wiadomość z linkiem autoryzacyjnym.';
                $this->_banner_error = false;
                $login_email['value'] = '';
                $_POST = array();
                $statement->closeCursor();
                $this->dbh->commit();
            }
            catch (Exception $e)
            {
                $this->_banner_message = $e->getMessage();
                $this->_banner_error = true;
                $this->dbh->rollback();
            }
        }
        return array(
            'v_login_email' => $login_email,
            'banner_message' => $this->_banner_message,
            'banner_error' => $this->_banner_error,
        );
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda zmieniająca hasło do konta na podstawie tokenu przesłanego w parametrze GET lub wpisanego w formularzu w widoku przez
     * użytkownika wysyłającego żądanie zmiany hasła do konta
     */
    public function renew_change_password()
    {
        $show_change_password = true;
        $v_password = array('value' => '', 'invl' => false, 'bts_class' => '');
        $v_password_rep = array('value' => '', 'invl' => false, 'bts_class' => '');
        try 
        {
            $this->dbh->beginTransaction();
            if (!isset($_GET['code'])) throw new Exception('Podany kod autoryzacyjny jest nieprawidłowy lub nie istnieje.');
            
            $query = "
                SELECT user_id FROM ota_user_token WHERE ota_token = ? AND expiration_date >= NOW() AND is_used = false
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array(
                $_GET['code'],
            ));

            $user_id = $statement->fetchColumn();
            if (empty($user_id))
            {
                $redir_link = 'index.php?action=auth/password/renew/request';
                throw new Exception('
                    Podany kod autoryzacyjny nie istnieje lub wygasł. Aby wygenerować token
                    <a class="alert-link" href="' . $redir_link . '">kliknij tutaj</a>.
                ');
            }
            
            if (isset($_POST['form-send-change-pass']))
            {
                $v_password = Utils::validate_field_regex('change-password', '/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/');
                if ($v_password['value'] != $_POST['change-password-rep'])
                    $v_password_rep = array('value' => $_POST['change-password-rep'], 'invl' => true, 'bts_class' => 'is-invalid');

                if (!($v_password['invl'] || $v_password_rep['invl']))
                {
                    $query = "UPDATE ota_user_token SET is_used = true WHERE ota_token = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($_GET['code']));
                    
                    $query = "UPDATE users SET password = ? WHERE id = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        sha1($v_password['value']),
                        $user_id,
                    ));
                    $this->_banner_message = '
                        Twoje hasło zostało zmienione. Kliknij <a class="alert-link" href="index.php?action=auth/login">tutaj</a> aby 
                        zalogować się na konto.
                    ';
                    $this->_banner_error = false;
                    $v_password['value'] = '';
                    $v_password_rep['value'] = '';
                    $_POST = array();
                }
            }
            $statement->closeCursor();
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->_banner_message = $e->getMessage();
            $this->_banner_error = true;
            $this->dbh->rollback();
            $show_change_password = false;
        }
        return array(
            'v_password' => $v_password,
            'v_password_rep' => $v_password_rep,
            'banner_message' => $this->_banner_message,
            'banner_error' => $this->_banner_error,
            'show_change_password' => $show_change_password,
        );
    }
}
