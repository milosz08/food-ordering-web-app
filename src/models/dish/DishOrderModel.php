<?php

namespace App\Models\Dish;

use App\Core\ResourceLoader;

ResourceLoader::load_model('DishModel', 'Dish');

class DishOrderModel extends DishModel
{
  public $res_id;
  public $dishes_count;
  public $total_dish_cost;
}
