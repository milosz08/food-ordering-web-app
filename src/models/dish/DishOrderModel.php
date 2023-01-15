<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: DishOrderModel.php                             *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-14, 10:31:16                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-14 10:50:44                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

use App\Core\ResourceLoader;

ResourceLoader::load_model('DishModel', 'dish');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class DishOrderModel extends DishModel
{
    public $res_id;
    public $dishes_count;
    public $total_dish_cost;
}
