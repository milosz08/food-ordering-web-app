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
 * Ostatnia modyfikacja: 2022-12-16 01:57:34                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace App\Models;

class AcceptationModel
{
    public $id; // id z bazy danych
    public $full_name; // imię i nazwisko właściciela
    public $name; // nazwa restauracji
    public $address; // adres restauracji 
}
