<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: RestaurantWithDishesPageModel.php              *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-14, 04:01:24                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:49:38                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

use App\Core\ResourceLoader;
use App\Services\Helpers\ImagesHelper;

ResourceLoader::load_service_helper('ImagesHelper');

class RestaurantWithDishesPageModel
{
    public $id; // id restauracji
    public $name; // nazwa restauracji
    public $delivery_price_no; // koszt dostawy restauracji (bez suffixu)
    public $delivery_price; // koszt dostawy restauracji (z suffixem)
    public $min_price; // minimalna cena za dostawę (bez suffixu)
    public $min_delivery_price; // minimalna cena za dostawę (z suffixem)
    public $description; // opis restauracji
    public $avg_grades; // średnia ocen restauracji
    public $total_grades; // wszystkich ocen restauracji
    public $grades_bts; // klasy bootstrapowe do ikon gwiazdek dla ocen restauracji
    public $opinions; // opinie użytkowników przypisane do konkretnej restauracji
    public $street_number; // ulica restauracji wraz z numerem lokalu
    public $city_post_code; // miasto wraz z kodem pocztowym
    public $phone_number; // numer telefonu do restauracji
    public $delivery_hours; // tablica z informacjami odnośnie godzin dostaw
    public $baner_url; // zdjęcie baneru restauracji
    public $profile_url; // zdjęcie profilu restauracji
    public $has_discounts; // posiada zniżki
    public $delivery_free; //darmowa dostawa

    public function __construct()
    {
        $this->grades_bts = ImagesHelper::generate_stars_definitions($this->avg_grades);
        $this->opinions = array();
        $this->delivery_hours = array();
    }
}
