<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: OwnerRatingModel.php                           *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-14, 08:19:02                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-15 07:19:17                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

use App\Core\ResourceLoader;
use App\Services\Helpers\ImagesHelper;

ResourceLoader::load_service_helper('ImagesHelper');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class OwnerRatingModel
{
    public $id;
    public $it;
    public $res_id;
    public $order_id;
    public $signature;
    public $restaurant_grade;
    public $delivery_grade;
    public $give_on;
    public $description;
    public $avg_grade;
    public $avg_grade_stars;
    public $delivery_restaurant;
    public $date_order;
    public $finish_order;
    public $date_diff;
    public $order_dishes;
    public $status;
    public $status_bts_class;

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct()
    {
        $this->restaurant_grade = ImagesHelper::generate_stars_definitions($this->restaurant_grade, true);
        $this->delivery_grade = ImagesHelper::generate_stars_definitions($this->delivery_grade, true);
        $this->avg_grade_stars = ImagesHelper::generate_stars_definitions($this->avg_grade);
        $this->order_dishes = array();
    }
}
