<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: AcceptationModel.php                           *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-12-14, 20:11:28                       *
 * Autor: BubbleWaffle                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2022-12-14 20:15:26                   *
 * Modyfikowany przez: BubbleWaffle                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace App\Models;

class AcceptationModel
{
    public $id; // id z bazy danych
    public $first_name; // imię właściciela
    public $last_name; // nazwisko właściciela
    public $name; // nazwa restauracji
    public $street; // ulica 
    public $building_locale_nr; // numer budynku
    public $post_code; // adres pocztowy
    public $city; // miasto
}
