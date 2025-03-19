<?php

namespace App\Models\User;

class EditUserPersonalDataModel
{
  public $id;
  public $first_name;
  public $last_name;
  public $login;
  public $email;
  public $building_no;
  public $locale_no;
  public $phone_number;
  public $post_code;
  public $city;
  public $profile_url;
  public $street;

  public function __construct()
  {
    $this->first_name = array('value' => $this->first_name, 'invalid' => false, 'bts_class' => '');
    $this->last_name = array('value' => $this->last_name, 'invalid' => false, 'bts_class' => '');
    $this->login = array('value' => $this->login, 'invalid' => false, 'bts_class' => '');
    $this->email = array('value' => $this->email, 'invalid' => false, 'bts_class' => '');
    $this->building_no = array('value' => $this->building_no, 'invalid' => false, 'bts_class' => '');
    $this->locale_no = array('value' => $this->locale_no, 'invalid' => false, 'bts_class' => '');
    $this->phone_number = array('value' => $this->phone_number, 'invalid' => false, 'bts_class' => '');
    $this->post_code = array('value' => $this->post_code, 'invalid' => false, 'bts_class' => '');
    $this->city = array('value' => $this->city, 'invalid' => false, 'bts_class' => '');
    $this->profile_url = array('value' => $this->profile_url, 'invalid' => false, 'bts_class' => '');
    $this->street = array('value' => $this->street, 'invalid' => false, 'bts_class' => '');
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
      $this->building_no['invalid'] ||
      $this->locale_no['invalid'] ||
      $this->phone_number['invalid'] ||
      $this->login['invalid']
    );
  }
}
