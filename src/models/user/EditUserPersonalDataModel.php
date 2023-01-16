<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: EditUserPersonalDataModel.php                  *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-16, 13:43:29                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-16 14:33:56                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class EditUserPersonalDataModel
{
    public $id;
    public $first_name;
    public $last_name;
    public $login;
    public $email;
    public $building_no;
    public $locale_no;
    public $phone_number;
    public $post_code;
    public $city;
    public $profile_url;
    public $street;

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct()
    {
        $this->first_name = array('value' => $this->first_name, 'invl' => false, 'bts_class' => '');
        $this->last_name = array('value' => $this->last_name, 'invl' => false, 'bts_class' => '');
        $this->login = array('value' => $this->login, 'invl' => false, 'bts_class' => '');
        $this->email = array('value' => $this->email, 'invl' => false, 'bts_class' => '');
        $this->building_no = array('value' => $this->building_no, 'invl' => false, 'bts_class' => '');
        $this->locale_no = array('value' => $this->locale_no, 'invl' => false, 'bts_class' => '');
        $this->phone_number = array('value' => $this->phone_number, 'invl' => false, 'bts_class' => '');
        $this->post_code = array('value' => $this->post_code, 'invl' => false, 'bts_class' => '');
        $this->city = array('value' => $this->city, 'invl' => false, 'bts_class' => '');
        $this->profile_url = array('value' => $this->profile_url, 'invl' => false, 'bts_class' => '');
        $this->street = array('value' => $this->street, 'invl' => false, 'bts_class' => '');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function all_is_valid()
    {
        return !($this->first_name['invl'] || $this->last_name['invl'] || $this->email['invl'] || $this->post_code['invl'] ||
            $this->city['invl'] || $this->street['invl'] || $this->building_no['invl'] || $this->locale_no['invl'] ||
            $this->phone_number['invl'] || $this->login['invl']);
    }
}
