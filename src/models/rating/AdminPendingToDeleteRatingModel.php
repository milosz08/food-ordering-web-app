<?php

namespace App\Models\Rating;

use App\Core\ResourceLoader;
use App\Services\Helpers\ImagesHelper;

ResourceLoader::load_service_helper('ImagesHelper');

class AdminPendingToDeleteRatingModel
{
  public $it;
  public $id;
  public $opinion_id;
  public $type;
  public $description;
  public $send_date;
  public $description_s;
  public $sender;
  public $avg_grade;
  public $restaurant_grade;
  public $delivery_grade;
  public $avg_grade_stars;
  public $date_order;
  public $finish_order;
  public $date_diff;

  public function __construct()
  {
    $this->restaurant_grade = ImagesHelper::generate_stars_definitions($this->restaurant_grade, true);
    $this->delivery_grade = ImagesHelper::generate_stars_definitions($this->delivery_grade, true);
    $this->avg_grade_stars = ImagesHelper::generate_stars_definitions($this->avg_grade);
  }
}
