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
 * Ostatnia modyfikacja: 2022-12-12 01:02:17                   *
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
                $v_surname = Utils::validate_field_regex('registration-surname', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ \-]{2,50}$/');
                $v_login = Utils::validate_field_regex('registration-login', Config::get('__REGEX_LOGIN__'));
                $v_password = Utils::validate_field_regex('registration-password', Config::get('__REGEX_PASSWORD__'));
                $v_password_rep = Utils::validate_exact_fields($v_password, 'registration-password-rep');
                $v_email = Utils::validate_email_field('registration-email');
                $account_type = $_POST['registration-role'];
                $v_building_no = Utils::validate_field_regex('registration-building-number', Config::get('__REGEX_BUILDING_NO__'));
                if (!empty($_POST['registration-local-number']))
                    $v_locale_no = Utils::validate_field_regex('registration-local-number', Config::get('__REGEX_BUILDING_NO__'));
                else
                    $v_locale_no = array('value' => $_POST['registration-local-number'], 'invl' => false, 'bts_class' => '');
                $v_post_code = Utils::validate_field_regex('registration-post-code', Config::get('__REGEX_POSTCODE__'));
                $v_city = Utils::validate_field_regex('registration-city', Config::get('__REGEX_CITY__'));
                $v_street = Utils::validate_field_regex('registration-street', Config::get('__REGEX_STREET__'));

                $this->dbh->beginTransaction();

                if (!($v_name['invl'] || $v_surname['invl'] || $v_login['invl'] || $v_password['invl'] || $v_email['invl'] ||
                      $v_building_no['invl'] || $v_locale_no['invl'] || $v_post_code['invl'] || $v_city['invl'] || $v_street['invl'] ||
                      $v_password_rep['invl']))
                {
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
                    $rnd_ota_token = Utils::generate_random_seq();
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
                        wysłanego na podany podczas rejestracji adres email. Nieaktywowane konto w przeciągu <strong><48 godzin/strong>
                        zostanie automatycznie usunięte z systemu.
                    ';
                    $_SESSION['successful_register_user'] = array(
                        'banner_message' => $this->_banner_message,
                        'show_banner' => !empty($this->_banner_message),
                        'banner_class' => 'alert-warning',
                    );
                    header('Location:' . __URL_INIT_DIR__ . 'auth/register', true, 301);
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
                'v_password_rep' => $v_password_rep,
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
            $login_email = Utils::validate_field_regex('login_email', Config::get('__REGEX_LOGINEMAIL__'));
            $password = Utils::validate_field_regex('pass', Config::get('__REGEX_PASSWORD__'));
            try
            {
                $query = "
                    SELECT users.id AS id, is_activated, role_id, roles.name AS role_name, CONCAT(first_name,' ', last_name) AS full_name,
                    password, photo_url
                    FROM users INNER JOIN roles ON users.role_id=roles.id 
                    WHERE login = :login OR email = :login
                ";
                $statement = $this->dbh->prepare($query);
                $statement->bindValue(':login', $login_email['value']);
                $statement->execute();
                $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($result) == 0) throw new Exception('Konto z podanymi parametrami nie istnieje w systemie.');
                $result = $result[0];

                if (!password_verify($password['value'], $result['password']))
                    throw new Exception('Nieprawidłowy login i/lub hasło. Spróbuj ponownie.');

                // sprawdzanie, czy użytkownik ma aktywowane konto, jeśli nie wyświetlenie linka do wysyłki wiadomości email z tokenem OTA
                if ($result['is_activated'] == 0)
                {
                    $redir_link = __URL_INIT_DIR__ . 'auth/account/activate/resend/code&userid=' . $result['id'];
                    throw new Exception('
                        Twoje konto nie zostało aktywowane. Aby aktywować konto sprawdź stwoją skrzynkę pocztową. W celu wysłania ponownie
                        kodu aktywacyjnego, <a class="alert-link" href="' . $redir_link . '">kliknij tutaj</a>.
                    ');
                }
                $statement->closeCursor();

                $_SESSION['logged_user'] = array(
                    'user_id' => $result['id'],
                    'user_role' => array('role_id' => $result['role_id'], 'role_name' => $result['role_name']),
                    'user_full_name' => $result['full_name'],
                    'user_profile_image' => $result['photo_url'] ?? 'static/images/default-profile-image.svg',
                );
                header('Location:' . __URL_INIT_DIR__, true, 301); // jeśli wszystko się powiedzie, przejdź do strony głównej
            }
            catch (Exception $e)
            {
                $this->_banner_message = $e->getMessage();
                $this->_banner_error = true;
            }
            return array(
                'v_loginemail' => $login_email,
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
        $login_email = array('value' => '', 'invl' => false, 'bts_class' => '');
        if (isset($_POST['form-send-request-change-pass']))
        {
            try
            {
                $this->dbh->beginTransaction();
                $login_email = Utils::validate_field_regex('login_email', Config::get('__REGEX_LOGINEMAIL__'));
                
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
                    INSERT INTO ota_user_token (ota_token, expiration_date, user_id, type_id)
                    VALUES (?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), ?, (SELECT id FROM ota_token_types WHERE type='change password' LIMIT 1))
                ";
                $statement = $this->dbh->prepare($query);
                $rnd_ota_token = Utils::generate_random_seq();
                $statement->execute(array($rnd_ota_token, $user_data['id']));

                $email_request_vars = array(
                    'user_full_name' => $user_data['full_name'],
                    'basic_server_path' => Config::get('__DEF_APP_HOST__'),
                    'ota_token' => $rnd_ota_token,
                );
                $subject = 'Reset hasła dla użytkownika ' . $user_data['full_name'];
                $this->smtp_client->send_message($user_data['email'], $subject, 'renew-password', $email_request_vars);

                $statement->closeCursor();
                $this->dbh->commit();
                $this->_banner_message = 'Na adres email ' . $user_data['email'] . ' została wysłana wiadomość z linkiem autoryzacyjnym.';
                $this->_banner_error = false;
        
                $_SESSION['attempt_change_password'] = array(
                    'banner_message' => $this->_banner_message,
                    'banner_error' => $this->_banner_error,
                    'show_banner' => !empty($this->_banner_message),
                    'banner_class' => $this->_banner_error ? 'alert-danger' : 'alert-success',
                );
                header('Location:' . __URL_INIT_DIR__ .  'auth/password/renew/request', true, 301);
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
            if (!isset($_GET['code'])) throw new Exception('W celu zmiany hasła należy podać kod autoryzacyjny.');
            if (!preg_match(Config::get('__REGEX_OTA__'), $_GET['code']))
                throw new Exception('Podany kod nie jest prawidłowym kodem autoryzacyjnym.');
            
            $query = "
                SELECT user_id FROM ota_user_token WHERE ota_token = ? AND expiration_date >= NOW() AND is_used = false
                AND type_id = (SELECT id FROM ota_token_types WHERE type='change password' LIMIT 1)
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['code']));

            $user_id = $statement->fetchColumn();
            if (empty($user_id))
            {
                $redir_link = __URL_INIT_DIR__ . 'auth/password/renew/request';
                throw new Exception('
                    Podany kod autoryzacyjny nie istnieje lub wygasł. Aby wygenerować token
                    <a class="alert-link" href="' . $redir_link . '">kliknij tutaj</a>.
                ');
            }
            if (isset($_POST['form-send-change-pass']))
            {
                $v_password = Utils::validate_field_regex('change-password', Config::get('__REGEX_PASSWORD__'));
                $v_password_rep = Utils::validate_exact_fields($v_password, 'change-password-rep');
                if (!($v_password['invl'] || $v_password_rep['invl']))
                {
                    $query = "UPDATE ota_user_token SET is_used = true WHERE ota_token = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($_GET['code']));
                    
                    $query = "UPDATE users SET password = ? WHERE id = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $this->passwd_hash($v_password['value']),
                        $user_id,
                    ));
                    $redir_link = __URL_INIT_DIR__ . 'auth/login';
                    $this->_banner_message = '
                        Twoje hasło zostało zmienione. Kliknij <a class="alert-link" href="' . $redir_link . '">tutaj</a> aby 
                        zalogować się na konto.
                    ';
                    $this->_banner_error = false;
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
            'show_banner' => !empty($this->_banner_message),
            'banner_class' => $this->_banner_error ? 'alert-danger' : 'alert-success',
            'show_change_password' => $show_change_password,
        );
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda odpowiedzialna za wysłanie rządania aktywowania konta na podstawie tokenu OTA w parametrze GET. Jeśli token istnieje, nie
     * został wykorzystany i nie został przedawniony, zmienany jest jego status na wykorzystany oraz konto użytkownika zostaje aktywowane.
     * Od tej pory użytkownik może logować się do systemu.
     */
    public function attempt_activate_account()
    {
        try
        {
            $this->dbh->beginTransaction();

            if (!isset($_GET['code'])) throw new Exception('W celu ukończenia aktywacji konta należy podać kod autoryzacyjny.');
            if (!preg_match(Config::get('__REGEX_OTA__'), $_GET['code']))
                throw new Exception('Podany kod nie jest prawidłowym kodem autoryzacyjnym.');
            
            // zapytanie złożone sprawdzające token czy istnieje, czy nie jest przedawiony, czy nie został już użyty oraz czy konto
            // użytkownika powiązane z tym tokem nie zostało już zaktywowane
            $query = "
                SELECT user_id FROM ota_user_token AS ota
                INNER JOIN users AS us ON ota.user_id = us.id
                WHERE ota_token = ? AND expiration_date >= NOW() AND is_used = false AND is_activated = 0
                AND type_id = (SELECT id FROM ota_token_types WHERE type='activate account' LIMIT 1)
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['code']));

            $user_id = $statement->fetchColumn();
            if (empty($user_id)) throw new Exception('Podany kod autoryzacyjny wygasł lub Twoje konto zostało już aktywowane.');
            
            $query = "UPDATE ota_user_token SET is_used = true WHERE ota_token = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['code']));
                    
            $query = "UPDATE users SET is_activated = 1 WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($user_id));
            $this->_banner_message = '
                Twoje konto zostało aktywowane. Wpisz poniżej dane w formularzu, aby zalogować się na nowo utworzone konto.
            ';
            $this->_banner_error = false;

            $statement->closeCursor();
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->_banner_message = $e->getMessage();
            $this->_banner_error = true;
            $this->dbh->rollback();
        }
        $_SESSION['attempt_activate_account'] = array(
            'banner_message' => $this->_banner_message,
            'show_banner' => !empty($this->_banner_message),
            'banner_class' => $this->_banner_error ? 'alert-danger' : 'alert-success',
        );
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda odpowiedzialna za ponowne wysłanie wiadomości email do użytkownika z tokenem. Jeśli token nie wygasł i nie został użyty, jest
     * on wykorzystywany. W przeciwnym wypadku generowany jest nowy token, zapisywany w bazie i wysyłany w wiadomości email w postaci linku.
     * Użytkownik klikając w link zostanie przeniesiony do strony weryfikującej poprawność tokenu i aktywującej konto.
     */
    public function resend_account_activation_link()
    {
        try
        {
            $this->dbh->beginTransaction();

            if (!isset($_GET['userid'])) throw new Exception('W celu ponownego wysłania tokenu należy podać identyfikator użytkownika.');

            $query = "
                SELECT user_id AS id, ota_token, email, CONCAT(first_name, ' ', last_name) AS full_name FROM ota_user_token AS ota
                INNER JOIN users AS us ON ota.user_id = us.id
                WHERE user_id = ? AND expiration_date >= NOW() AND is_used = false
                AND type_id = (SELECT id FROM ota_token_types WHERE type = 'activate account' LIMIT 1)
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['userid']));

            $user_data = $statement->fetchAll(PDO::FETCH_ASSOC);
            $ota_token = '';
            if (count($user_data) == 0)
            {
                $query = "
                    INSERT INTO ota_user_token (ota_token, expiration_date, user_id, type_id)
                    VALUES (?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), ?, 
                    (SELECT id FROM ota_token_types WHERE type = 'activate account' LIMIT 1))
                ";
                $statement = $this->dbh->prepare($query);
                $rnd_ota_token = Utils::generate_random_seq();
                $statement->execute(array($rnd_ota_token, $_GET['userid']));
                $ota_token = $rnd_ota_token;
            }
            else $user_data = $user_data[0];
            $ota_token = $user_data['ota_token'];
            
            $email_request_vars = array(
                'user_full_name' => $user_data['full_name'],
                'basic_server_path' => Config::get('__DEF_APP_HOST__'),
                'ota_token' => $ota_token,
                'regenerate_link' => 'auth/account/activate/resend/code?userid=' . $user_data['id'],
            );
            $subject = 'Aktywacja konta dla użytkownika ' . $user_data['full_name'];
            $this->smtp_client->send_message($user_data['email'], $subject, 'activate-account', $email_request_vars);

            $this->_banner_message = 'Na adres email skojarzony z kontem ' . $user_data['full_name'] . ' został wysłany kod autoryzacyjny.';
            $this->_banner_error = false;
            $statement->closeCursor();
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->_banner_message = $e->getMessage();
            $this->_banner_error = true;
            $this->dbh->rollback();
        }
        $_SESSION['attempt_activate_account'] = array(
            'banner_message' => $this->_banner_message,
            'show_banner' => !empty($this->_banner_message),
            'banner_class' => $this->_banner_error ? 'alert-danger' : 'alert-success',
        );
    }
}
