<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: DiscountRowModel.php                           *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-12, 03:39:12                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:46:57                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

class DiscountRowModel
{
    public $it;
    public $id;
    public $res_id;
    public $code;
    public $percentage_discount;
    public $max_usages;
    public $expired_date;
    public $res_name;
    public $expired_bts_class;
}
