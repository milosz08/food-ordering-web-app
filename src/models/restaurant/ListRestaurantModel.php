<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: ListRestaurantModel.php                        *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-06, 19:58:50                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-06 22:42:42                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class ListRestaurantModel
{
    public $id; // id z bazy danych
    public $name; // nazwa restauracji
    public $delivery_price; // koszt dostawy restauracji
    public $description; // opis restauracji
    public $baner_url; // zdjęcie baneru restauracji
    public $profile_url; // zdjęcie profilu restauracji
    public $dish_types; // typy potraw oferowane przez restaurację
}
