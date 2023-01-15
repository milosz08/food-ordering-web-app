<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: RestaurantAdminModel.php                       *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-15, 15:23:45                       *
 * Autor: patrick012016                                        *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-15 15:30:55                   *
 * Modyfikowany przez: patrick012016                           *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

 namespace App\Models;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 
class RestaurantAdminModel
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
    public $address; // pełny adres restauracji
    public $phone_number; // numer telefonu do restauracji
    public $profile_url; // link do zdjęcia profilowego restauracji
    public $banner_url; // link do zdjęcia w tle restauracji
    public $min_price; // najniższa cena za jaką można kupić produkty
    public $discounts_count; // ilość kodów rabatowych jakie oferuje restauracja

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    public function __construct()
    {
        $this->status = array(
            'text' => empty($this->accept) ? 'oczekująca' : 'aktywna',
            'color_bts' => empty($this->accept) ? 'text-danger' : 'text-success',
        );
    }
}
 