<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: ResDiscountsRowModel.php                       *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-12, 03:51:27                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:47:02                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

class ResDiscountsRowModel
{
    public $it;
    public $id;
    public $code;
    public $address;
    public $all_discounts;
    public $count_of_active_discounts;
    public $count_of_inactive_discounts;
    public $hide_codes;

    public function __construct()
    {
        $this->count_of_inactive_discounts = $this->all_discounts - $this->count_of_active_discounts;
    }
}
