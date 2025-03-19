<?php

namespace App\Models\User;

class AddNewAddressUserModel
{
  public $street;
  public $post_code;
  public $city;
  public $building_nr;
  public $locale_nr;

  public function __construct()
  {
    $this->post_code = array('value' => $this->post_code, 'invalid' => false, 'bts_class' => '');
    $this->city = array('value' => $this->city, 'invalid' => false, 'bts_class' => '');
    $this->street = array('value' => $this->street, 'invalid' => false, 'bts_class' => '');
    $this->building_nr = array('value' => $this->building_nr, 'invalid' => false, 'bts_class' => '');
    $this->locale_nr = array('value' => $this->locale_nr, 'invalid' => false, 'bts_class' => '');
  }

  public function all_is_valid(): bool
  {
    return !(
      $this->post_code['invalid'] ||
      $this->city['invalid'] ||
      $this->street['invalid'] ||
      $this->building_nr['invalid'] ||
      $this->locale_nr['invalid']
    );
  }
}
