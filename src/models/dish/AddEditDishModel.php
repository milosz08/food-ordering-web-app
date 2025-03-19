<?php

namespace App\Models\Dish;

class AddEditDishModel
{
  public $name; // nazwa potrawy
  public $description; // opis potrawy
  public $price; // cena za potrawę
  public $prepared_time; // średni czas przygotowania
  public $photo_url; // zdjęcie potrawy
  public $type; // typ potrawy (select box)
  public $custom_type; // nowy typ potrawy (select box)

  public function __construct()
  {
    $this->name = array('value' => $this->name, 'invalid' => false, 'bts_class' => '');
    $this->description = array('value' => $this->description, 'invalid' => false, 'bts_class' => '');
    $this->price = array('value' => $this->price, 'invalid' => false, 'bts_class' => '');
    $this->photo_url = array('value' => $this->photo_url, 'invalid' => false, 'bts_class' => '');
    $this->prepared_time = array('value' => $this->prepared_time, 'invalid' => false, 'bts_class' => '');
    $this->type = array('value' => $this->type, 'invalid' => false, 'bts_class' => '');
    $this->custom_type = array('value' => '', 'invalid' => false, 'bts_class' => '');
  }

  public function all_is_valid(): bool
  {
    return !(
      $this->name['invalid'] ||
      $this->description['invalid'] ||
      $this->price['invalid'] ||
      $this->photo_url['invalid'] ||
      $this->prepared_time['invalid'] ||
      $this->custom_type['invalid']
    );
  }
}
