<?php

namespace App\Models\Restaurant;

class RestaurantFilterModel
{
  public $open_selected;
  public $delivery_free_selected;
  public $has_discounts_selected;
  public $has_profile_selected;
  public $min_delivery_price;
  public $grade_stars;
  public $sort_parameters;
  public $sort_directions;
  public $filter_query;
  public $sorting_query;

  public function __construct()
  {
    $this->filter_query = '';
    $this->sorting_query = '';
    $this->min_delivery_price = array(
      'query' => '',
      'price' => '-',
      'data' => array(
        array('text' => 'Wyświetl wszystkie', 'value' => '-', 'checked' => 'checked'),
        array('text' => 'Bez minimalnej kwoty', 'value' => '0', 'checked' => ''),
        array('text' => '35,00 zł lub mniej', 'value' => '35', 'checked' => ''),
        array('text' => '50,00 zł lub mniej', 'value' => '50', 'checked' => ''),
        array('text' => '51,00 zł lub więcej', 'value' => '51', 'checked' => ''),
      ),
    );
    $this->grade_stars = array(
      'query' => '',
      'stars' => '',
      'data' => array(
        array('value' => '1', 'checked' => ''),
        array('value' => '2', 'checked' => ''),
        array('value' => '3', 'checked' => ''),
        array('value' => '4', 'checked' => ''),
        array('value' => '5', 'checked' => ''),
      ),
    );
    $this->sort_parameters = array(
      'sorted_by' => '-',
      'data' => array(
        array('text' => '-', 'value' => '-', 'selected' => ''),
        array('text' => 'Ceny oferowanych potraw', 'value' => 'dish-price', 'selected' => ''),
        array('text' => 'Ceny dostawy zamówienia', 'value' => 'delivery-price', 'selected' => ''),
        array('text' => 'Czasu dostawy zamówienia', 'value' => 'delivery-time', 'selected' => ''),
        array('text' => 'Oceny restauracji', 'value' => 'restaurant-rating', 'selected' => ''),
        array('text' => 'Popularności restauracji', 'value' => 'count-of-grades', 'selected' => ''),
        array('text' => 'Alfabetycznie według nazwy restauracji', 'value' => 'name-alphabetically', 'selected' => ''),
      ),
    );
    $this->sort_directions = array(
      'dir' => 'ASC',
      'data' => array(
        array('text' => 'Rosnąco', 'value' => 'ASC', 'selected' => 'selected'),
        array('text' => 'Malejąco', 'value' => 'DESC', 'selected' => ''),
      ),
    );
  }

  public function find_parameter_and_fill($value, &$array, $custom_value = 'selected')
  {
    foreach ($array as &$data_attribute) {
      if ($data_attribute['value'] == $value) {
        $data_attribute[$custom_value] = $custom_value;
      } else $data_attribute[$custom_value] = '';
    }
  }

  public function combined_filter_query(): string
  {
    return $this->filter_query . ' ' . $this->min_delivery_price['query'] . ' ' . $this->grade_stars['query'];
  }
}
