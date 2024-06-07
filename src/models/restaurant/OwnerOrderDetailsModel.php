<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: OwnerOrderDetailsModel.php                     *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-16, 15:39:42                       *
 * Autor: BubbleWaffle                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:48:22                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

class OwnerOrderDetailsModel
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

    public function __construct()
    {
        $this->dishes_value = array();
    }
}
