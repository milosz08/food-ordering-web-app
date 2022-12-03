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
<<<<<<< HEAD
 * Ostatnia modyfikacja: 2022-12-02 19:52:26                   *
 * Modyfikowany przez: BubbleWaffle                            *
=======
 * Ostatnia modyfikacja: 2022-12-03 13:36:02                   *
 * Modyfikowany przez: patrick012016                           *
>>>>>>> 87ffcd5189aefe53e6b6983d354577273240bbd0
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services;

use PDO;
use Exception;

use App\Utils\Utils;
use App\Core\MvcService;

class AuthService extends MvcService
{
    private $_error;

    //--------------------------------------------------------------------------------------------------------------------------------------

    protected function __construct()
    {
        parent::__construct();
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Funkcja odpowiadająca za dodawanie nowych danych użytkownika i ich sprawdzanie z istniejącą bazą danych.
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
                $v_street = Utils::validate_field_regex('registration-street', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ ]{2,100}$/');

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
                $this->_error = $e->getMessage();
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
                'error' => $this->_error,
            );
        }
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Funkcja odpowiadający za pobieranie danych użytkownika i ich sprawdzanie z istniejącą bazą danych.
     * Jeśli użytkownik istnieje następuje (tymczasowo) przekierowanie do strony głównej.
     */
    public function login_user()
    {
        if (isset($_POST['form-login']))
        {
            $login = Utils::validate_field_regex('login', '/^[a-zA-Z0-9]{5,30}$/');
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
                $this->_error = $e->getMessage();
            }
            return array(
                'v_login' => $login,
                'v_password' => $password,
                'error' => $this->_error,
            );
        }
    }  
}
