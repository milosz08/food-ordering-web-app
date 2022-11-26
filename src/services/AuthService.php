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
 * Ostatnia modyfikacja: 2022-11-26 22:09:26                   *
 * Modyfikowany przez: patrick012016                           *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services;

use App\Core\MvcService;

class AuthService extends MvcService
{
    protected function __construct()
    {
        parent::__construct();
    }

    protected $name;
    protected $surname;
    protected $login;
    protected $password;
    protected $email;
    protected $account_type;
    protected $local_number;
    protected $post_code;
    protected $city;
    protected $street;

    public function nameCheck($nameCheck){ //Sprawdzanie poprawności imienia.
        if (empty($nameCheck)) {
            $nameCheck = "is-invalid";
            return $nameCheck;
        }elseif (!ctype_alpha($nameCheck)) {
            $nameCheck = "is-invalid";
            return $nameCheck;
        }else return "is-valid";
    }

    public function surnameCheck($surnameCheck){ //Sprawdzanie poprawności nazwiska.
        if (empty($surnameCheck)) {
            $surnameCheck = "is-invalid";
            return $surnameCheck;
        }elseif (!ctype_alpha($surnameCheck)) {
            $surnameCheck = "is-invalid";
            return $surnameCheck;
        }else return "is-valid";
    }

    public function loginCheck($loginCheck){ //Sprawdzanie poprawności loginu.

        $loginSize = strlen($loginCheck);

        if (empty($loginCheck)) {
            $loginCheck = "is-invalid";
            return $loginCheck;
        }elseif (ctype_space($loginCheck)) {
            $loginCheck = "is-invalid";
            return $loginCheck;
        }elseif ($loginSize < 6 || $loginSize > 12) {
            $loginCheck = 3;
            return $loginCheck;
        }else return "is-valid";
    }

    public function passwordCheck($passwordCheck){ //Sprawdzanie poprawności hasła.

        $passwordSize = strlen($passwordCheck);

        if (empty($passwordCheck)) {
            $passwordCheck = "is-invalid";
            return $passwordCheck;
        }elseif (ctype_space($passwordCheck)) {
            $passwordCheck = "is-invalid";
            return $passwordCheck;
        }elseif ($passwordSize < 8 || $passwordSize > 18) {
            $passwordCheck = "is-invalid";
            return $passwordCheck;
        }else return "is-valid";
    }

    public function emailCheck($emailCheck){ //Sprawdzanie poprawności email.
        if (empty($emailCheck)) {
            $emailCheck = "is-invalid";
            return $emailCheck;
        }elseif (!filter_var($emailCheck, FILTER_VALIDATE_EMAIL)) {
            $emailCheck = "is-invalid";
            return $emailCheck;
        }else return "is-valid";
    }

    public function local_numberCheck($lnCheck){ //Sprawdzanie poprawności lokalu i budynku.
        if (empty($lnCheck)) {
            $lnCheck = "is-invalid";
            return $lnCheck;
        }elseif (empty($lnCheck)) { //NIE MA JESZCZE! Cza wymyśleć dla 123, 123/3 :v
            $lnCheck = "is-invalid";
            return $lnCheck;
        }else return "is-valid";
    }

    public function post_codeCheck($post_codeCheck){ //Sprawdzanie poprawności kodu pocztowego.
        if (empty($post_codeCheck)) {
            $post_codeCheck = "is-invalid";
            return $post_codeCheck;
        }elseif (empty($post_codeCheck)) { //Tutaj tesz :(
            $post_codeCheck = "is-invalid";
            return $post_codeCheck;
        }else return "is-valid";
    }

    public function cityCheck($cityCheck){ //Sprawdzanie poprawności miasta.
        if (empty($cityCheck)) {
            $cityCheck = "is-invalid";
            return $cityCheck;
        }elseif (!ctype_alpha($cityCheck)) { //LoL, no namiasto też cza
            $cityCheck = "is-invalid";
            return $cityCheck;
        }else return "is-valid";
    }

    public function streetCheck($streetCheck){ //Sprawdzanie poprawności ulicy.
        if (empty($streetCheck)) {
            $streetCheck = "is-invalid";
            return $streetCheck;
        }elseif (!ctype_alpha(str_replace(' ', '', $streetCheck))) {
            $streetCheck = "is-invalid";
            return $streetCheck;
        }else return "is-valid";
    }

    public function registration(){
        
        if(isset($_POST['registration-button'])){
            $name = $_POST['registration-name'];
            $errorName = $this->nameCheck($name);

            $surname = $_POST['registration-surname'];
            $errorSurname = $this->surnameCheck($surname);

            $login = $_POST['registration-login'];
            $errorLogin = $this->loginCheck($login);

            $password = $_POST['registration-password'];
            $errorPassword = $this->passwordCheck($password);

            $email = $_POST['registration-email'];
            $errorEmail = $this->emailCheck($email);

            if (isset($_POST['registration-role'])) {
                $account_type = $_POST['registration-role'];
            }
            
            $local_number = $_POST['registration-local-number'];
            $errorLN = $this->local_numberCheck($local_number);
            
            $post_code = $_POST['registration-post-code'];
            $errorPC = $this->post_codeCheck($post_code);

            $city = $_POST['registration-city'];
            $errorCity = $this->cityCheck($city);

            $street = $_POST['registration-street'];
            $errorStreet = $this->streetCheck($street);

            if ($errorName != "is-valid" || $errorSurname != "is-valid" || $errorLogin != "is-valid" || $errorPassword != "is-valid" 
            || $errorEmail != "is-valid" || $errorLN != "is-valid" || $errorPC != "is-valid" || $errorCity != "is-valid" 
            || $errorStreet != "is-valid") {
                return array($name, $surname, $login, $email, $local_number, $post_code, $city, $street, $errorName, $errorSurname, 
                $errorLogin, $errorPassword, $errorEmail, $errorLN, $errorPC, $errorCity, $errorStreet);
            }else{
                //Tutaj będą zapytania sql.
                return array($name, $surname, $login, $email, $local_number, $post_code, $city, $street, $errorName, $errorSurname, 
                $errorLogin, $errorPassword, $errorEmail, $errorLN, $errorPC, $errorCity, $errorStreet);
            }
        }
    }

//------------------------------------------------------------------------------------------------------------------------------------------

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

//------------------------------------------------------------------------------------------------------------------------------------------

}
