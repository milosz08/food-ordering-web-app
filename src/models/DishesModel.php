<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: DishesModel.php                                *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-12-23, 23:06:25                       *
 * Autor: patrick012016                                        *
 *                                                             *
 * Ostatnia modyfikacja: 2022-12-24 09:45:18                   *
 * Modyfikowany przez: patrick012016                           *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

class DishesModel
{
    public $id; // id z bazy danych
    public $name; // nazwa dania
    public $type; // typ dania
    public $description; // opis dania
    public $price; // cena dania
}
