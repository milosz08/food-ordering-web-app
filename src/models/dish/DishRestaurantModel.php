<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: DishRestaurantModel.php                        *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-04, 02:30:13                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:47:26                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

class DishRestaurantModel
{
    public $it; // liczba porządkowa
    public $d_id; // id potrawy z bazy danych
    public $d_name; // nazwa potrawy z bazy danych
    public $d_type; // typ potrawy
    public $r_id; // id restauracji do której przypisana jest potrawa
    public $r_name; // nazwa restauracji do której przypisana jest potrawa
    public $r_description; // opis restauracji do której przypisana jest potrawa
}
