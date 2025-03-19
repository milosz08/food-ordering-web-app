<?php

namespace App\Models\Rating;

class AddEditRestaurantGradeModel
{
  public $id;
  public $restaurant_grade;
  public $delivery_grade;
  public $description;
  public $order_id;
  public $anonymously;
  public $res_grade_stars;
  public $delivery_grade_stars;
  public $anonymous_is_checked;

  public function __construct()
  {
    $this->description = array('value' => $this->description, 'invalid' => false, 'bts_class' => '');
    $this->res_grade_stars = array(
      'invalid' => false,
      'data' => array(
        array('value' => '1', 'checked' => ''),
        array('value' => '2', 'checked' => ''),
        array('value' => '3', 'checked' => ''),
        array('value' => '4', 'checked' => ''),
        array('value' => '5', 'checked' => ''),
      ),
    );
    $this->delivery_grade_stars = array(
      'invalid' => false,
      'data' => array(
        array('value' => '1', 'checked' => ''),
        array('value' => '2', 'checked' => ''),
        array('value' => '3', 'checked' => ''),
        array('value' => '4', 'checked' => ''),
        array('value' => '5', 'checked' => ''),
      ),
    );
  }

  public function all_is_valid(): bool
  {
    return !(
      $this->description['invalid'] ||
      $this->res_grade_stars['invalid'] ||
      $this->delivery_grade_stars['invalid']
    );
  }
}
