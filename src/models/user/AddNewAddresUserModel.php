<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: AddNewAddresUserModel.php                      *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-12, 21:23:35                       *
 * Autor: patrick012016                                        *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:49:49                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

class AddNewAddresUserModel
{
    public $street;
    public $post_code;
    public $city;
    public $building_nr;
    public $locale_nr;

    public function __construct()
    {
        $this->post_code = array('value' => $this->post_code, 'invl' => false, 'bts_class' => '');
        $this->city = array('value' => $this->city, 'invl' => false, 'bts_class' => '');
        $this->street = array('value' => $this->street, 'invl' => false, 'bts_class' => '');
        $this->building_nr = array('value' => $this->building_nr, 'invl' => false, 'bts_class' => '');
        $this->locale_nr = array('value' => $this->locale_nr, 'invl' => false, 'bts_class' => '');
    }

    public function all_is_valid()
    {
        return !($this->post_code['invl'] || $this->city['invl'] || $this->street['invl'] || $this->building_nr['invl'] || $this->locale_nr['invl']);
    }
}
