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
 * Ostatnia modyfikacja: 2022-11-27 00:11:41                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services;

use App\Core\MvcService;

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
                // tutaj zapytania SQL
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
