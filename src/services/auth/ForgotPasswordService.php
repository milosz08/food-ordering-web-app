<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: ForgotPasswordService.php                      *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 19:44:39                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-12 03:05:44                   *
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

class ForgotPasswordService extends MvcService
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
     * Metoda odpowiadająca za wysłanie rządania zmiany zapomnianego hasła. Metoda na podstawie podanego adresu email/loginu wysyłana na
     * skorelowany z nim adres email wiadomość z linkiem i tokenem umożliwiającym zmianę hasła.
     */
    public function forgot_password_request()
    {
        $login_email = array('value' => '', 'invl' => false, 'bts_class' => '');
        if (isset($_POST['form-send-request-change-pass']))
        {
            try
            {
                $this->dbh->beginTransaction();
                $login_email = ValidationHelper::validate_field_regex('login_email', Config::get('__REGEX_LOGINEMAIL__'));
                
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
                $rnd_ota_token = AuthHelper::generate_random_seq();
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
                SessionHelper::create_session_banner(SessionHelper::FORGOT_PASSWORD_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
                header('Location:' . __URL_INIT_DIR__ . 'auth/forgot-password', true, 301);
                die;
            }
            catch (Exception $e)
            {
                $this->dbh->rollback();
                SessionHelper::create_session_banner(SessionHelper::FORGOT_PASSWORD_PAGE_BANNER, $e->getMessage(), true);
            }
        }
        return array(
            'v_login_email' => $login_email,
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda zmieniająca hasło do konta na podstawie tokenu przesłanego w parametrze GET lub wpisanego w formularzu w widoku przez
     * użytkownika wysyłającego żądanie zmiany hasła do konta
     */
    public function forgot_password_change()
    {
        $show_change_password = true;
        $redir_link = __URL_INIT_DIR__ . 'auth/forgot-password';
        $v_password = array('value' => '', 'invl' => false, 'bts_class' => '');
        $v_password_rep = array('value' => '', 'invl' => false, 'bts_class' => '');
        try
        {
            $this->dbh->beginTransaction();
            if (!isset($_GET['code'])) header('Location:' . $redir_link);
            if (!preg_match(Config::get('__REGEX_OTA__'), $_GET['code']))
                throw new Exception('Podany kod nie jest prawidłowym kodem autoryzacyjnym.');
            
            $query = "
                SELECT user_id FROM ota_user_token
                WHERE ota_token = ? AND expiration_date >= NOW() AND is_used = false
                AND type_id = (SELECT id FROM ota_token_types WHERE type='change password' LIMIT 1)
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['code']));

            $user_id = $statement->fetchColumn();
            if (empty($user_id))
            {
                throw new Exception('
                    Podany kod autoryzacyjny nie istnieje lub wygasł. Aby wygenerować token
                    <a class="alert-link" href="' . $redir_link . '">kliknij tutaj</a>.
                ');
            }
            if (isset($_POST['form-send-change-pass']))
            {
                $v_password = ValidationHelper::validate_field_regex('change-password', Config::get('__REGEX_PASSWORD__'));
                $v_password_rep = ValidationHelper::validate_exact_fields($v_password, 'change-password-rep');
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
                    $this->_banner_message = 'Twoje hasło zostało pomyślnie zmienione. Możesz zalogować się na konto.';

                    SessionHelper::create_session_banner(SessionHelper::LOGIN_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
                    header('Location: ' . __URL_INIT_DIR__ . 'auth/login', true, 301);
                    die;
                }
            }
            $statement->closeCursor();
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $show_change_password = false;
            SessionHelper::create_session_banner(SessionHelper::FORGOT_PASSWORD_CHANGE_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'v_password' => $v_password,
            'v_password_rep' => $v_password_rep,
            'show_change_password' => $show_change_password,
        );
    }
}
