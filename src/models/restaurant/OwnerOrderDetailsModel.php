<?php

namespace App\Models\Restaurant;

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
