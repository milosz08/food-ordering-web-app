<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: DishService.php                                *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-12-20, 19:10:30                       *
 * Autor: Lukasz Krawczyk                                      *
 *                                                             *
 * Ostatnia modyfikacja: 2022-12-20 19:37:05                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services;

use App\Core\MvcService;

class DishService extends MvcService
{
    //--------------------------------------------------------------------------------------------------------------------------------------

    protected function __construct()
    {
        parent::__construct();
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda odpowiadająca za dodawanie danych nowej restauracji oraz sprawdzanie ich z istniejącą bazą danych.
     * Jeśli restauracja została pomyślnie dodana następuje (tymczasowo) przekierowanie do strony głównej.
     */
    public function add_dish()
    {


    }
}
