<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: ShowUserSingleOrderModel.php                   *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-10, 17:31:09                       *
 * Autor: BubbleWaffle                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:50:25                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

class ShowUserSingleOrderModel
{
    public $first_name;
    public $last_name;
    public $email;
    public $city;
    public $street;
    public $building_nr;
    public $locale_nr;
    public $post_code;
    public $status_name;
    public $order_type;
    public $discount_id;
    public $date_order;
    public $id;
    public $status_id;
    public $time_statement;
    public $dish_name;
    public $dish_amount;
    public $dishes_value;
    public $is_grade_active;
    public $is_grade_editable;
    public $grade_id;

    public function __construct()
    {
        $this->dishes_value = array();
    }
}
