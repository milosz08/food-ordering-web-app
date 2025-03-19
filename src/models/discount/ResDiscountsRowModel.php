<?php

namespace App\Models\Discount;

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
