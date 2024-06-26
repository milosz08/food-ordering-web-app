<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: RestaurantDetailsModel.php                     *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-10, 17:24:38                       *
 * Autor: Lukasz Krawczyk                                      *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:50:16                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

class RestaurantDetailsModel
{
    public $id;
    public $dish_name;
    public $description;
    public $photo_url;
    public $price;
    public $prepared_time;
    public $dish_type_name;
    public $distinct_dish_type;
}
