<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: AddEditRestaurantGradeModel.php                *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-16, 09:41:12                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:47:35                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

class AddEditRestaurantGradeModel
{
    public $id;
    public $restaurant_grade;
    public $delivery_grade;
    public $description;
    public $order_id;
    public $anonymously;
    public $res_grade_stars;
    public $delivery_grade_stars;
    public $anonymous_is_checked;

    public function __construct()
    {
        $this->description = array('value' => $this->description, 'invl' => false, 'bts_class' => '');
        $this->res_grade_stars = array(
            'invl' => false,
            'data' => array(
                array('value' => '1', 'checked' => ''),
                array('value' => '2', 'checked' => ''),
                array('value' => '3', 'checked' => ''),
                array('value' => '4', 'checked' => ''),
                array('value' => '5', 'checked' => ''),
            ),
        );
        $this->delivery_grade_stars = array(
            'invl' => false,
            'data' => array(
                array('value' => '1', 'checked' => ''),
                array('value' => '2', 'checked' => ''),
                array('value' => '3', 'checked' => ''),
                array('value' => '4', 'checked' => ''),
                array('value' => '5', 'checked' => ''),
            ),
        );
    }

    public function all_is_valid()
    {
        return !($this->description['invl'] || $this->res_grade_stars['invl'] || $this->delivery_grade_stars['invl']);
    }
}
