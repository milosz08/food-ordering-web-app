<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: DishModel.php                                  *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-12-23, 23:06:25                       *
 * Autor: patrick012016                                        *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-03 01:29:02                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class DishModel
{
    public $id; // id z bazy danych
    public $name; // nazwa dania
    public $type; // typ dania
    public $description; // opis dania
    public $price; // cena dania
}
