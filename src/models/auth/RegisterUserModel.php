<?php

namespace App\Models\Auth;

class RegisterUserModel
{
  public $name;
  public $surname;
  public $login;
  public $password;
  public $password_rep;
  public $email;
  public $building_nr;
  public $locale_nr;
  public $post_code;
  public $city;
  public $street;
  public $phone_number;

  public function __construct()
  {
    $this->name = array('value' => $this->name, 'invalid' => false, 'bts_class' => '');
    $this->surname = array('value' => $this->surname, 'invalid' => false, 'bts_class' => '');
    $this->login = array('value' => $this->login, 'invalid' => false, 'bts_class' => '');
    $this->password = array('value' => $this->password, 'invalid' => false, 'bts_class' => '');
    $this->password_rep = array('value' => $this->password_rep, 'invalid' => false, 'bts_class' => '');
    $this->email = array('value' => $this->email, 'invalid' => false, 'bts_class' => '');
    $this->building_nr = array('value' => $this->building_nr, 'invalid' => false, 'bts_class' => '');
    $this->locale_nr = array('value' => $this->locale_nr, 'invalid' => false, 'bts_class' => '');
    $this->post_code = array('value' => $this->post_code, 'invalid' => false, 'bts_class' => '');
    $this->city = array('value' => $this->city, 'invalid' => false, 'bts_class' => '');
    $this->street = array('value' => $this->street, 'invalid' => false, 'bts_class' => '');
    $this->phone_number = array('value' => $this->phone_number, 'invalid' => false, 'bts_class' => '');
  }

  public function all_is_valid(): bool
  {
    return !(
      $this->name['invalid'] ||
      $this->surname['invalid'] ||
      $this->login['invalid'] ||
      $this->password['invalid'] ||
      $this->password_rep['invalid'] ||
      $this->email['invalid'] ||
      $this->building_nr['invalid'] ||
      $this->locale_nr['invalid'] ||
      $this->post_code['invalid'] ||
      $this->city['invalid'] ||
      $this->street['invalid'] ||
      $this->phone_number['invalid']
    );
  }
}
