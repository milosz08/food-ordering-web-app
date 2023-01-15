<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: DiscountResDetailsModel.php                    *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-12, 06:44:09                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-15 01:41:16                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class DiscountResDetailsModel
{
    public $id;
    public $res_id;
    public $code;
    public $description;
    public $percentage_discount;
    public $total_usages;
    public $expired_date;
    public $increase_time_active;
    public $increase_usages_active;
    public $expired_bts_class;
    public $status;
}
