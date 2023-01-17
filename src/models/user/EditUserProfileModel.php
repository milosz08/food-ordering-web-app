<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: EditUserProfileModel.php                       *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-07, 18:50:26                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-16 05:49:50                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class EditUserProfileModel
{
    public $first_name;
    public $last_name;
    public $email;
    public $street;
    public $post_code;
    public $city;
    public $building_nr;
    public $locale_nr;
    public $phone_number;
    public $profile_url;

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    public function __construct()
    {
        $this->first_name = array('value' => $this->first_name, 'invl' => false, 'bts_class' => '');
        $this->last_name = array('value' => $this->last_name, 'invl' => false, 'bts_class' => '');
        $this->email = array('value' => $this->email, 'invl' => false, 'bts_class' => '');
        $this->post_code = array('value' => $this->post_code, 'invl' => false, 'bts_class' => '');
        $this->city = array('value' => $this->city, 'invl' => false, 'bts_class' => '');
        $this->street = array('value' => $this->street, 'invl' => false, 'bts_class' => '');
        $this->building_nr = array('value' => $this->building_nr, 'invl' => false, 'bts_class' => '');
        $this->locale_nr = array('value' => $this->locale_nr, 'invl' => false, 'bts_class' => '');
        $this->phone_number = array('value' => $this->phone_number, 'invl' => false, 'bts_class' => '');
        $this->profile_url = array('value' => $this->profile_url, 'invl' => false, 'bts_class' => '');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function all_is_valid()
    {
        return !($this->first_name['invl'] || $this->last_name['invl'] || $this->email['invl'] || $this->post_code['invl'] ||
            $this->city['invl'] || $this->street['invl'] || $this->building_nr['invl'] || $this->locale_nr['invl'] ||
            $this->phone_number['invl']);
    }
}
