<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: RestaurantModel.php                            *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-12-09, 21:18:35                       *
 * Autor: Lukasz Krawczyk                                      *
 *                                                             *
 * Ostatnia modyfikacja: 2022-12-16 02:13:24                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace App\Models;

class RestaurantModel
{
    public $id; //id z bazy danych
    public $name; // nazwa restauracji
    public $address; // adres lokalu 
    public $accept; //czy zatwierdzona przez administratora
}
