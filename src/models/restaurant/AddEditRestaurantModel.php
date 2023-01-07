<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: AddEditRestaurantModel.php                     *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-05, 02:56:18                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-05 18:17:05                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class AddEditRestaurantModel
{
    public $name; // nazwa resturacji
    public $street; // ulica
    public $building_locale_nr; // numer budynku restauracji
    public $post_code; // kod pocztowy
    public $city; // miasto
    public $delivery_price; // cena za dostawę
    public $description; // opis restauracji
    public $banner_url; // baner restauracji
    public $profile_url; // zdjęcie restauracji (logo)
    public $phone_number; // numer telefonu do restauracji

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct()
    {
        $this->name = array('value' => $this->name, 'invl' => false, 'bts_class' => '');
        $this->street = array('value' => $this->street, 'invl' => false, 'bts_class' => '');
        $this->building_locale_nr = array('value' => $this->building_locale_nr, 'invl' => false, 'bts_class' => '');
        $this->post_code = array('value' => $this->post_code, 'invl' => false, 'bts_class' => '');
        $this->city = array('value' => $this->city, 'invl' => false, 'bts_class' => '');
        $this->delivery_price = array('value' => $this->delivery_price, 'invl' => false, 'bts_class' => '');
        $this->description = array('value' => $this->description, 'invl' => false, 'bts_class' => '');
        $this->banner_url = array('value' => $this->banner_url, 'invl' => false, 'bts_class' => '');
        $this->profile_url = array('value' => $this->profile_url, 'invl' => false, 'bts_class' => '');
        $this->phone_number = array('value' => $this->phone_number, 'invl' => false, 'bts_class' => '');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function all_is_valid()
    {
        return !($this->name['invl'] || $this->street['invl'] || $this->building_locale_nr['invl'] || $this->post_code['invl'] ||
            $this->city['invl'] || $this->delivery_price['invl'] || $this->description['invl'] || $this->banner_url['invl'] ||
            $this->profile_url['invl'] || $this->phone_number['invl']);
    }
}
