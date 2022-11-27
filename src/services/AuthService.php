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
 * Ostatnia modyfikacja: 2022-11-27 21:23:12                   *
 * Modyfikowany przez: BubbleWaffle                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services;

use App\Core\MvcService;
use PDO;

class AuthService extends MvcService
{
    //--------------------------------------------------------------------------------------------------------------------------------------

    protected function __construct()
    {
        parent::__construct();
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    public function register()
    {
        if(isset($_POST['registration-button']))
        {
            $error = false;

            $v_name = $this->validate_field_regex('registration-name', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{2,50}$/');
            $v_surname = $this->validate_field_regex('registration-surname', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{2,50}$/');
            $v_login = $this->validate_field_regex('registration-login', '/^[a-zA-Z0-9]{5,30}$/');
            $v_password = $this->validate_field_regex('registration-password', '/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/');
            $v_email = $this->validate_email_field('registration-email');
            if (isset($_POST['registration-role']))
            {
                $account_type = $_POST['registration-role'];
            }
            $v_building_no = $this->validate_field_regex('registration-building-number', '/^[0-9]{1,5}$/');
            $v_locale_no = $this->validate_field_regex('registration-local-number', '/^([0-9]+(?:[a-z]{0,1})){1,5}$/');
            $v_post_code = $this->validate_field_regex('registration-post-code', '/^[0-9]{2}-[0-9]{3}$/');
            $v_city = $this->validate_field_regex('registration-city', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{2,60}$/');
            $v_street = $this->validate_field_regex('registration-street', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{2,100}$/');

            if (!($v_name['invl'] || $v_surname['invl'] || $v_login['invl'] || $v_password['invl'] || $v_email['invl'] ||
                $v_building_no['invl'] || $v_locale_no['invl'] || $v_post_code['invl'] || $v_city['invl'] || $v_street['invl']))
            {
                /*---Zapytanie zwracające liczbę istniejących już kont o podanym loginie i/lub emailu---*/
                $isQuery = 'SELECT COUNT(*) FROM users WHERE login = ? OR email = ?';
                $isStatement = $this->dbh->prepare($isQuery);
                $isStatement->bindParam(1, $v_login['value']);
                $isStatement->bindParam(2, $v_email['value']);
                $isStatement->execute();
                $countExistingUsers = $isStatement->fetchColumn();

                /*---if zwracający błąd jeżeli konto już istnieje---*/       
                if ($countExistingUsers > 0) {
                    $error = true;
                }else{
                    /*---Sekcja zapytań dodająca wprowadzone dane do tabel users i user_address---*/   
                    $addUserQuery = 'INSERT INTO users (first_name, last_name, login, password, email, role_id)
                                 VALUES (?, ?, ?, ?, ?, ?)';
                    $addUserStatement = $this->dbh->prepare($addUserQuery);
                    $addUserStatement->bindParam(1, $v_name['value']);
                    $addUserStatement->bindParam(2, $v_surname['value']);
                    $addUserStatement->bindParam(3, $v_login['value']);
                    $addUserStatement->bindValue(4, sha1($v_password['value']));
                    $addUserStatement->bindParam(5, $v_email['value']);
                    $addUserStatement->bindValue(6, $account_type, PDO::PARAM_INT);
                    $addUserStatement->execute();

                    $userIDQuery = 'SELECT id FROM users WHERE login = ?';
                    $userIDStatement = $this->dbh->prepare($userIDQuery);
                    $userIDStatement->bindParam(1, $v_login['value']);
                    $userIDStatement->execute();
                    $userID = $userIDStatement->fetch(PDO::FETCH_NUM);
                    var_dump($userID);

                    $UserAdressQuery = 'INSERT INTO user_address (street, building_nr, post_code, city, user_id)
                                    VALUES (?, ?, ?, ?, ?)';
                    $UserAdressStatement = $this->dbh->prepare($UserAdressQuery);
                    $UserAdressStatement->bindParam(1, $v_street['value']);
                    $UserAdressStatement->bindParam(2, $v_building_no['value']);
                    $UserAdressStatement->bindParam(3, $v_post_code['value']);
                    $UserAdressStatement->bindParam(4, $v_city['value']);
                    $UserAdressStatement->bindParam(5, $userID[0], PDO::PARAM_INT);
                    $UserAdressStatement->execute();
                    header('Location:index.php?action=home/welcome');
                }
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
                'error' => $error
            );
        }
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Funkcja odpowiadający za pobieranie danych użytkownika i ich sprawdzanie z istniejąca baza danych.
     * Jeśli użytkownik istnieje następuje (tymczasowo) przekierowanie do strony głównej.
     */
    public function login_user()
    {
        if (isset($_POST['form-login'])) {
            $login = $_POST['login'];
            $password = $_POST['pass'];
            $temp = $password;
            $password = sha1($temp);

            /* zapytanie pobierające użytkownika na podstawie loginu oraz zahaszowanego hasła */
            $query = "
            SELECT users.id FROM users INNER JOIN roles ON users.role_id=roles.id 
            WHERE (login = '$login' OR email = '$login') AND password = '$password'";
            
            $statement = $this->dbh->prepare($query);
            $statement->execute();
            $countUsers = $statement->fetchAll();
            $numberOfUsers = count($countUsers);

            if ($numberOfUsers <= 0) {
                $loginError = "is-invalid";
                $passError = "is-invalid";
            } else {
                header('Location:index.php?action=home/welcome');
            }
            return array($loginError, $passError);
        }
    }  
    
    //--------------------------------------------------------------------------------------------------------------------------------------

    private function validate_field_regex($value, $pattern)
    {
        $without_blanks = trim(htmlspecialchars($_POST[$value]));
        if (empty($without_blanks) || !preg_match($pattern, $without_blanks))
        {
            return array('value' => $without_blanks, 'invl' => true, 'bts_class' => 'is-invalid');
        }
        return array('value' => $without_blanks, 'invl' => false, 'bts_class' => 'is-valid');
    }

    //--------------------------------------------------------------------------------------------------------------------------------------
    
    private function validate_email_field($value)
    {
        $without_blanks = trim(htmlspecialchars($_POST[$value]));
        if (empty($without_blanks) || !filter_var($without_blanks, FILTER_VALIDATE_EMAIL))
        {
            return array('value' => $without_blanks, 'invl' => true, 'bts_class' => 'is-invalid');
        }
        return array('value' => $without_blanks, 'invl' => false, 'bts_class' => 'is-valid');
    }
}
