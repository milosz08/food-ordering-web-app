<?php

namespace App\Models\Dish;

class DishRestaurantModel
{
  public $it; // liczba porządkowa
  public $d_id; // id potrawy z bazy danych
  public $d_name; // nazwa potrawy z bazy danych
  public $d_type; // typ potrawy
  public $r_id; // id restauracji do której przypisana jest potrawa
  public $r_name; // nazwa restauracji do której przypisana jest potrawa
  public $r_description; // opis restauracji do której przypisana jest potrawa
}
