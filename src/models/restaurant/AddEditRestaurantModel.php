<?php

namespace App\Models\Restaurant;

class AddEditRestaurantModel
{
  public $name; // nazwa restauracji
  public $street; // ulica
  public $building_locale_nr; // numer budynku restauracji
  public $post_code; // kod pocztowy
  public $city; // miasto
  public $delivery_price; // cena za dostawę
  public $description; // opis restauracji
  public $banner_url; // baner restauracji
  public $profile_url; // zdjęcie restauracji (logo)
  public $phone_number; // numer telefonu do restauracji
  public $delivery_free; // darmowa dostawa
  public $min_price; // najniższa cena za jaką można złożyć zamówienie

  public function __construct()
  {
    $this->name = array('value' => $this->name, 'invalid' => false, 'bts_class' => '');
    $this->street = array('value' => $this->street, 'invalid' => false, 'bts_class' => '');
    $this->building_locale_nr = array('value' => $this->building_locale_nr, 'invalid' => false, 'bts_class' => '');
    $this->post_code = array('value' => $this->post_code, 'invalid' => false, 'bts_class' => '');
    $this->city = array('value' => $this->city, 'invalid' => false, 'bts_class' => '');
    $this->delivery_price = array('value' => $this->delivery_price, 'invalid' => false, 'bts_class' => '');
    $this->description = array('value' => $this->description, 'invalid' => false, 'bts_class' => '');
    $this->banner_url = array('value' => $this->banner_url, 'invalid' => false, 'bts_class' => '');
    $this->profile_url = array('value' => $this->profile_url, 'invalid' => false, 'bts_class' => '');
    $this->phone_number = array('value' => $this->phone_number, 'invalid' => false, 'bts_class' => '');
    $this->min_price = array('value' => $this->min_price, 'invalid' => false, 'bts_class' => '');
  }

  public function all_is_valid(): bool
  {
    return !(
      $this->name['invalid'] ||
      $this->street['invalid'] ||
      $this->building_locale_nr['invalid'] ||
      $this->post_code['invalid'] ||
      $this->city['invalid'] ||
      $this->delivery_price['invalid'] ||
      $this->description['invalid'] ||
      $this->banner_url['invalid'] ||
      $this->profile_url['invalid'] ||
      $this->phone_number['invalid'] ||
      $this->min_price['invalid']
    );
  }
}
