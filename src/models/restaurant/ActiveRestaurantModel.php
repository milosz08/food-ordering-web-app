<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: ActiveRestaurantModel.php                      *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-04, 00:45:33                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-04 00:47:24                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class ActiveRestaurantModel
{
    public $it; // liczba porządkowa resturacji
    public $id; // id restauracji z bazy danych
    public $name; // nazwa restauracji
    public $address; // adres restauracji
    public $count_of_dishes; // liczba potraw przypisanych do restauracji
}
