<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: LoginService.php                               *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 18:56:31                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-07 01:36:00                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Auth\Services;

use PDO;
use Exception;
use App\Core\Config;
use App\Core\MvcService;
use App\Core\MvcProtector;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\ValidationHelper;

ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('ValidationHelper');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class LoginService extends MvcService
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
     * Metoda odpowiadający za pobieranie danych użytkownika i ich sprawdzanie z istniejącą bazą danych.
     * Jeśli użytkownik istnieje następuje przekierowanie do strony odpowiadającej rangą użytkownika.
     */
    public function login_user()
    {
        if (isset($_POST['form-login']))
        {
            $login_email = ValidationHelper::validate_field_regex('login_email', Config::get('__REGEX_LOGINEMAIL__'));
            $password = ValidationHelper::validate_field_regex('pass', Config::get('__REGEX_PASSWORD__'));
            try
            {
                $query = "
                    SELECT users.id AS id, is_activated, role_id, roles.name AS role_name, CONCAT(first_name,' ', last_name) AS full_name,
                    password, photo_url, role_eng
                    FROM users INNER JOIN roles ON users.role_id=roles.id 
                    WHERE login = :login OR email = :login
                ";
                $statement = $this->dbh->prepare($query);
                $statement->bindValue(':login', $login_email['value']);
                $statement->execute();
                $result = $statement->fetch(PDO::FETCH_ASSOC);
                
                if (!$result) throw new Exception('Konto z podanymi parametrami nie istnieje w systemie.');

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
                    'is_normal_user' => $result['role_name'] == MvcProtector::USER,
                    'user_role' => array(
                        'role_id' => $result['role_id'],
                        'role_name' => $result['role_name'],
                        'role_eng' => $result['role_eng'],
                    ),
                    'user_full_name' => $result['full_name'],
                    'user_profile_image' => $result['photo_url'] ?? 'static/images/default-profile-image.jpg',
                );
                if ($result['role_name'] == MvcProtector::ADMIN) $redir_url = 'admin/dashboard';
                else if ($result['role_name'] == MvcProtector::OWNER) $redir_url = 'owner/dashboard';
                else $redir_url = '';
                header('Location:' . __URL_INIT_DIR__ . $redir_url, true, 301); // jeśli wszystko się powiedzie, przejdź do strony
            }
            catch (Exception $e)
            {
                $this->_banner_message = $e->getMessage();
                $this->_banner_error = true;
            }
            SessionHelper::create_session_banner(SessionHelper::LOGIN_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
            return array(
                'login_email' => $login_email,
                'password' => $password,
            );
        }
    }
}
