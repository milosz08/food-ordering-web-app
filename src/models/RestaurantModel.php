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
 * Ostatnia modyfikacja: 2022-12-09 22:19:34                   *
 * Modyfikowany przez: Lukasz Krawczyk                         *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace App\Models;

 class RestaurantModel
 {
     public $name; // nazwa restauracji
     public $street; // ulica 
     public $building_locale_nr; // numer budynku
     public $post_code; // adres pocztowy
     public $city; // miasto
     public $delivery_price; //cena dostawy
     public $id; //cena dostawy
 }
