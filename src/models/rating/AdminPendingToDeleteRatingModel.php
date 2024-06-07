<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: AdminPendingToDeleteRatingModel.php            *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-15, 06:46:34                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:47:41                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

use App\Core\ResourceLoader;
use App\Services\Helpers\ImagesHelper;

ResourceLoader::load_service_helper('ImagesHelper');

class AdminPendingToDeleteRatingModel
{
    public $it;
    public $id;
    public $opinion_id;
    public $type;
    public $description;
    public $send_date;
    public $description_s;
    public $sender;
    public $avg_grade;
    public $restaurant_grade;
    public $delivery_grade;
    public $avg_grade_stars;
    public $date_order;
    public $finish_order;
    public $date_diff;

    public function __construct()
    {
        $this->restaurant_grade = ImagesHelper::generate_stars_definitions($this->restaurant_grade, true);
        $this->delivery_grade = ImagesHelper::generate_stars_definitions($this->delivery_grade, true);
        $this->avg_grade_stars = ImagesHelper::generate_stars_definitions($this->avg_grade);
    }
}
