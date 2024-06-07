<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: OpinionModel.php                               *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-14, 01:21:16                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:47:44                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

use App\Core\ResourceLoader;
use App\Services\Helpers\ImagesHelper;

ResourceLoader::load_service_helper('ImagesHelper');

class OpinionModel
{
    public $signature;
    public $restaurant_grade;
    public $delivery_grade;
    public $give_on;
    public $description;

    public function __construct()
    {
        $this->restaurant_grade = ImagesHelper::generate_stars_definitions($this->restaurant_grade, true);
        $this->delivery_grade = ImagesHelper::generate_stars_definitions($this->delivery_grade, true);
    }
}
