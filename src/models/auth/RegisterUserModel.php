<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: RegisterUserModel.php                          *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-07, 19:29:28                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-07 19:34:18                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class RegisterUserModel
{
    public $name;
    public $surname;
    public $login;
    public $password;
    public $password_rep;
    public $email;
    public $building_nr;
    public $locale_nr;
    public $post_code;
    public $city;
    public $street;

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct()
    {
        $this->name = array('value' => $this->name, 'invl' => false, 'bts_class' => '');
        $this->surname = array('value' => $this->surname, 'invl' => false, 'bts_class' => '');
        $this->login = array('value' => $this->login, 'invl' => false, 'bts_class' => '');
        $this->password = array('value' => $this->password, 'invl' => false, 'bts_class' => '');
        $this->password_rep = array('value' => $this->password_rep, 'invl' => false, 'bts_class' => '');
        $this->email = array('value' => $this->email, 'invl' => false, 'bts_class' => '');
        $this->building_nr = array('value' => $this->building_nr, 'invl' => false, 'bts_class' => '');
        $this->locale_nr = array('value' => $this->locale_nr, 'invl' => false, 'bts_class' => '');
        $this->post_code = array('value' => $this->post_code, 'invl' => false, 'bts_class' => '');
        $this->city = array('value' => $this->city, 'invl' => false, 'bts_class' => '');
        $this->street = array('value' => $this->street, 'invl' => false, 'bts_class' => '');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function all_is_valid()
    {
        return !($this->name['invl'] || $this->surname['invl'] || $this->login['invl'] || $this->password['invl'] ||
            $this->password_rep['invl'] || $this->email['invl'] || $this->building_nr['invl'] || $this->locale_nr['invl'] ||
            $this->post_code['invl'] || $this->city['invl'] || $this->street['invl']);
    }
}
