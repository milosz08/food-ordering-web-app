<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: UserModel.php                                  *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-11-10, 23:09:46                       *
 * Autor: Milosz08                                             *
 *                                                             *
 * Ostatnia modyfikacja: 2022-11-11 05:33:20                   *
 * Modyfikowany przez: Milosz08                                *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Przykładowy model. Model jest prostą klasą dostarczającą jedynie chronione pola, konstruktor i metody zwracające wartości (gettery).  *
 * Modele można wykorzystywać w PDO w przypadku mapowania (przepisywania) rekordu na obiekt. Wówczas pola takiej klasy muszą mieć        *
 * takie same nazwy jak uzyskiwane nazwy tabel (bądź ich aliasy) w zapytaniu SQL. Modele można również stowować w przypadku              *
 * przekazywania bardziej złożonych danych do widoków poprzez metody kontrolerów.                                                        *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class UserModel
{
    protected $name;
    protected $surname;

    //--------------------------------------------------------------------------------------------------------------------------------------

    public function __construct($name, $surname)
    {
        $this->name = $name;
        $this->surname = $surname;
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    public function get_name()
    {
        return $this->name;
    }

    public function get_surname()
    {
        return $this->surname;
    }
}   
