<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: ListRestaurantModel.php                        *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-12-31, 13:57:50                       *
 * Autor: patrick012016                                        *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-02 18:34:12                   *
 * Modyfikowany przez: patrick012016                           *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace App\Models;

class ListRestaurantModel
{
    public $it; // indeks wiersza
    public $id; // id z bazy danych
    public $name; // nazwa restauracji
    public $delivery_price; // koszt dostawy restauracji
    public $description; // opis restauracji
    public $baner_url; // zdjęcie baneru restauracji
    public $profile_url; // zdjęcie profilu restauracji
}
