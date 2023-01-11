<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: RestaurantDetailsModel.php                     *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-03, 01:33:30                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-10 01:34:46                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class RestaurantDetailsModel
{
    public $id; //id z bazy danych
    public $full_name; // imię i nazwisko właściciela
    public $name; // nazwa restauracji
    public $delivery_price; // cena za dostawę
    public $description; // opis restauracji
    public $street; // ulica restauracji
    public $building_locale_nr; // numer lokalu
    public $city; // miasto restauracji
    public $post_code; // kod pocztowy
    public $accept; //czy zatwierdzona przez administratora
    public $status; // static restauracji
    public $count_of_dishes; // liczba potraw przypisanych do restauracji
    public $add_button_class; // przycisk do dodawania nieaktywny, wówczas jeżeli restauracja nie została jeszcze zaakceptowana
    public $address; // pełny adres restauracji
    public $phone_number; // numer telefonu do restauracji
    public $profile_url; // link do zdjęcia profilowego restauracji
    public $banner_url; // link do zdjęcia w tle restauracji
    public $min_price; // najniższa cena za jaką można kupić produkty

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    public function __construct()
    {
        $this->add_button_class = empty($this->accept) ? 'disabled' : '';
        $this->status = array(
            'text' => empty($this->accept) ? 'oczekująca' : 'aktywna',
            'color_bts' => empty($this->accept) ? 'text-danger' : 'text-success',
        );
    }
}
