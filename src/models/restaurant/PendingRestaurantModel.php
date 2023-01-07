<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: PendingRestaurantModel.php                     *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-12-14, 20:11:28                       *
 * Autor: BubbleWaffle                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-03 01:33:49                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class PendingRestaurantModel
{
    public $it; // indeks wiersza
    public $id; //id z bazy danych
    public $full_name; // imię i nazwisko właściciela
    public $name; // nazwa restauracji
    public $address; // adres lokalu 
}
