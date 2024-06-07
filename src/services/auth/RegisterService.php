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
 * Ostatnia modyfikacja: 2024-06-08 00:57:55                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Auth\Services;

use PDO;
use Exception;
use App\Core\Config;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\RegisterUserModel;
use App\Services\Helpers\AuthHelper;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\ValidationHelper;

ResourceLoader::load_model('RegisterUserModel', 'auth');
ResourceLoader::load_service_helper('AuthHelper');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('ValidationHelper');

class RegisterService extends MvcService
{
    private $_banner_message = '';
    private $_banner_error = false;

    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * Metoda odpowiadająca za dodawanie nowych danych użytkownika i ich sprawdzanie z istniejącą bazą danych.
     */
    public function register_user()
    {
        $user = new RegisterUserModel;
        if(isset($_POST['registration-button']))
        {
            try
            {
                $user->name = ValidationHelper::validate_field_regex('registration-name', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{2,50}$/');
                $user->surname = ValidationHelper::validate_field_regex('registration-surname', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ \-]{2,50}$/');
                $user->login = ValidationHelper::validate_field_regex('registration-login', Config::get('__REGEX_LOGIN__'));
                $user->password = ValidationHelper::validate_field_regex('registration-password', Config::get('__REGEX_PASSWORD__'));
                $user->password_rep = ValidationHelper::validate_exact_fields($user->password, 'registration-password-rep');
                $user->email = ValidationHelper::validate_email_field('registration-email');
                $user->building_nr = ValidationHelper::validate_field_regex('registration-building-number', Config::get('__REGEX_BUILDING_NO__'));
                if (!empty($_POST['registration-local-number'])) $user->locale_nr = ValidationHelper::validate_field_regex(
                    'registration-local-number', Config::get('__REGEX_BUILDING_NO__')
                );
                else $user->locale_nr = array('value' => $_POST['registration-local-number'], 'invl' => false, 'bts_class' => '');
                $user->post_code = ValidationHelper::validate_field_regex('registration-post-code', Config::get('__REGEX_POSTCODE__'));
                $user->city = ValidationHelper::validate_field_regex('registration-city', Config::get('__REGEX_CITY__'));
                $user->street = ValidationHelper::validate_field_regex('registration-street', Config::get('__REGEX_STREET__'));
                $user->phone_number = ValidationHelper::validate_field_regex('user-phone', Config::get('__REGEX_PHONE_PL__'));

                if ($user->all_is_valid())
                {
                    $this->dbh->beginTransaction();
                    // Zapytanie zwracające liczbę istniejących już kont o podanym loginie i/lub emailu
                    $query = "SELECT COUNT(*) FROM users WHERE login = ? OR email = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($user->name['value'], $user->email['value']));
                    if ($statement->fetchColumn() > 0) throw new Exception('Podany użytkownik istnieje już w systemie. Podaj inne dane.');

                    $query = "SELECT COUNT(*) FROM users WHERE phone_number = REPLACE(?, ' ', '')";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($user->phone_number['value']));
                    if ($statement->fetchColumn() > 0) throw new Exception('Podany numer telefonu jest już zarejestrowany na innym koncie.');

                    // Sekcja zapytań dodająca wprowadzone dane do tabel users i user_address
                    $query = "
                        INSERT INTO users (first_name, last_name, login, password, email, phone_number, role_id)
                        VALUES (?,?,?,?,?,REPLACE(?, ' ', ''),?)
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $user->name['value'],
                        $user->surname['value'],
                        $user->login['value'],
                        $this->passwd_hash($user->password['value']),
                        $user->email['value'],
                        $user->phone_number['value'],
                        (int)$_POST['registration-role'],
                    ));
                    $query = "SELECT id, CONCAT(first_name, ' ', last_name) AS full_name FROM users WHERE login = ?";
                    $statement_id = $this->dbh->prepare($query);
                    $statement_id->execute(array($user->login['value']));
                    $user_data = $statement_id->fetch(PDO::FETCH_ASSOC);

                    $query = "
                        INSERT INTO user_address (street, building_nr, locale_nr, post_code, city, is_prime, user_id)
                        VALUES (?,?,NULLIF(?,''),?,?,1,?)
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $user->street['value'],
                        $user->building_nr['value'],
                        $user->locale_nr['value'],
                        $user->post_code['value'],
                        $user->city['value'],
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
                        'regenerate_link' => 'auth/activate-account/resend?userid=' . $user_data['id'],
                    );
                    $subject = 'Aktywacja konta dla użytkownika ' . $user_data['full_name'];
                    $this->smtp_client->send_message($user->email['value'], $subject, 'activate-account', $email_request_vars);

                    $statement->closeCursor();
                    $statement_id->closeCursor();
                    $this->_banner_message = '
                        Twoje konto zostało pomyślnie stworzone. Aby móc zalogować się na konto, musisz je aktywować przy pomocy linku
                        wysłanego na podany podczas rejestracji adres email. Nieaktywowane konto w przeciągu <strong>48 godzin</strong>
                        zostanie automatycznie usunięte z systemu.
                    ';
                    if ($this->dbh->inTransaction()) $this->dbh->commit();
                    SessionHelper::create_session_banner(SessionHelper::LOGIN_PAGE_BANNER, $this->_banner_message,
                        $this->_banner_error, 'alert-warning'
                    );
                    header('Location:' . __URL_INIT_DIR__ . 'auth/login', true, 301);
                    die;
                }
            }
            catch (Exception $e)
            {
                $this->dbh->rollback();
                SessionHelper::create_session_banner(SessionHelper::REGISTER_PAGE_BANNER, $e->getMessage(), true);
            }
            return array(
                'user' => $user,
            );
        }
    }
}
