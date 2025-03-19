<?php

namespace App\Models\Discount;

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
