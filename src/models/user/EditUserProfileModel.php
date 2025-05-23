<?php

namespace App\Models\User;

class EditUserProfileModel
{
  public $first_name;
  public $last_name;
  public $email;
  public $street;
  public $post_code;
  public $city;
  public $building_nr;
  public $locale_nr;
  public $phone_number;
  public $profile_url;

  public function __construct()
  {
    $this->first_name = array('value' => $this->first_name, 'invalid' => false, 'bts_class' => '');
    $this->last_name = array('value' => $this->last_name, 'invalid' => false, 'bts_class' => '');
    $this->email = array('value' => $this->email, 'invalid' => false, 'bts_class' => '');
    $this->post_code = array('value' => $this->post_code, 'invalid' => false, 'bts_class' => '');
    $this->city = array('value' => $this->city, 'invalid' => false, 'bts_class' => '');
    $this->street = array('value' => $this->street, 'invalid' => false, 'bts_class' => '');
    $this->building_nr = array('value' => $this->building_nr, 'invalid' => false, 'bts_class' => '');
    $this->locale_nr = array('value' => $this->locale_nr, 'invalid' => false, 'bts_class' => '');
    $this->phone_number = array('value' => $this->phone_number, 'invalid' => false, 'bts_class' => '');
    $this->profile_url = array('value' => $this->profile_url, 'invalid' => false, 'bts_class' => '');
  }

  public function all_is_valid(): bool
  {
    return !(
      $this->first_name['invalid'] ||
      $this->last_name['invalid'] ||
      $this->email['invalid'] ||
      $this->post_code['invalid'] ||
      $this->city['invalid'] ||
      $this->street['invalid'] ||
      $this->building_nr['invalid'] ||
      $this->locale_nr['invalid'] ||
      $this->phone_number['invalid']
    );
  }
}
