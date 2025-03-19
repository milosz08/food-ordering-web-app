<?php

namespace App\Models\Rating;

use App\Core\ResourceLoader;
use App\Services\Helpers\ImagesHelper;

ResourceLoader::load_service_helper('ImagesHelper');

class OpinionModel
{
  public $signature;
  public $restaurant_grade;
  public $delivery_grade;
  public $give_on;
  public $description;

  public function __construct()
  {
    $this->restaurant_grade = ImagesHelper::generate_stars_definitions($this->restaurant_grade, true);
    $this->delivery_grade = ImagesHelper::generate_stars_definitions($this->delivery_grade, true);
  }
}
