<?php

namespace App\Models\Restaurant;

class RestaurantPersistFilterModel
{
  public $open_selected_prst;
  public $delivery_free_selected_prst;
  public $has_discounts_selected_prst;
  public $has_profile_selected_prst;
  public $min_delivery_price_prst;
  public $grade_stars_prst;
  public $sort_parameters_prst;
  public $sort_directions_prst;

  public function __construct(RestaurantFilterModel $filter_model)
  {
    $this->open_selected_prst = $filter_model->open_selected;
    $this->delivery_free_selected_prst = $filter_model->delivery_free_selected;
    $this->has_discounts_selected_prst = $filter_model->has_discounts_selected;
    $this->has_profile_selected_prst = $filter_model->has_profile_selected;
    $this->min_delivery_price_prst = $filter_model->min_delivery_price['price'];
    $this->grade_stars_prst = $filter_model->grade_stars['stars'];
    $this->sort_parameters_prst = $filter_model->sort_parameters['sorted_by'];
    $this->sort_directions_prst = $filter_model->sort_directions['dir'];
  }

  public static function decode_to_filter_model($persist_data): RestaurantFilterModel
  {
    $filter_model = new RestaurantFilterModel;
    $filter_model->open_selected = $persist_data['open_selected_prst'];
    $filter_model->delivery_free_selected = $persist_data['delivery_free_selected_prst'];
    $filter_model->has_discounts_selected = $persist_data['has_discounts_selected_prst'];
    $filter_model->has_profile_selected = $persist_data['has_profile_selected_prst'];
    $filter_model->min_delivery_price['price'] = $persist_data['min_delivery_price_prst'];
    $filter_model->grade_stars['stars'] = $persist_data['grade_stars_prst'];
    $filter_model->sort_parameters['sorted_by'] = $persist_data['sort_parameters_prst'];
    $filter_model->sort_directions['dir'] = $persist_data['sort_directions_prst'];
    return $filter_model;
  }
}
