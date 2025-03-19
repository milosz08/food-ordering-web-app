<?php

namespace App\Models\Discount;

class AddEditDiscountModel
{
  public $code;
  public $description;
  public $percentage_discount;
  public $max_usages;
  public $expired_date;

  public function __construct()
  {
    $this->code = array('value' => $this->code, 'invalid' => false, 'bts_class' => '');
    $this->description = array('value' => $this->description, 'invalid' => false, 'bts_class' => '');
    $this->percentage_discount = array('value' => $this->percentage_discount, 'invalid' => false, 'bts_class' => '');
    $this->max_usages = array('value' => $this->max_usages, 'invalid' => false, 'bts_class' => '');
    $this->expired_date = array('value' => $this->expired_date, 'invalid' => false, 'bts_class' => '');
  }

  public function all_is_valid(): bool
  {
    return !(
      $this->code['invalid'] ||
      $this->description['invalid'] ||
      $this->percentage_discount['invalid'] ||
      $this->max_usages['invalid'] ||
      $this->expired_date['invalid']
    );
  }
}
