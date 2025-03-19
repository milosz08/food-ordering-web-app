<?php

namespace App\Services\Helpers;

use App\Core\ResourceLoader;
use App\Models\Restaurant\RestaurantHourModel;
use DateTime;
use Exception;

ResourceLoader::load_model('RestaurantHourModel', 'restaurant');

class ValidationHelper
{
  public static function validate_email_field(string $value): array
  {
    $without_blanks = trim(htmlspecialchars($_POST[$value]));
    if (empty($without_blanks) || !filter_var($without_blanks, FILTER_VALIDATE_EMAIL)) {
      return array('value' => $without_blanks, 'invalid' => true, 'bts_class' => 'is-invalid');
    }
    return array('value' => $without_blanks, 'invalid' => false, 'bts_class' => '');
  }

  public static function validate_image_regex(string $value): array
  {
    if (!isset($_FILES[$value]) || empty($_FILES[$value]['name'])) {
      return array('value' => '', 'invalid' => false, 'bts_class' => '', 'path' => '', 'ext' => '');
    }
    $path = $_FILES[$value]['tmp_name'];
    $imgValue = $_FILES[$value]['name'];
    $ext = pathinfo($imgValue, PATHINFO_EXTENSION);
    $without_blanks = trim(htmlspecialchars($imgValue));

    $image_info = getimagesize($path);
    $image_size = filesize($path);

    if (strpos($value, 'banner')) {
      if (($image_info[1] / $image_info[0]) >= 0.47 || ($image_info[1] / $image_info[0]) <= 0.42 || $image_size > 5000000) {
        return array('value' => $without_blanks, 'invalid' => true, 'bts_class' => 'is-invalid', 'path' => $path, 'ext' => $ext);
      }
    }
    if (strpos($value, 'profile')) {
      if ($image_info[0] != $image_info[1] || $image_size > 5000000) {
        return array('value' => $without_blanks, 'invalid' => true, 'bts_class' => 'is-invalid', 'path' => $path, 'ext' => $ext);
      }
    }
    return array('value' => $without_blanks, 'invalid' => false, 'bts_class' => '', 'path' => $path, 'ext' => $ext);
  }

  public static function validate_exact_fields($valueFirst, string $fieldSecond): array
  {
    if ($valueFirst['value'] !== $_POST[$fieldSecond]) {
      return array('value' => $_POST[$fieldSecond], 'invalid' => true, 'bts_class' => 'is-invalid');
    }
    return array('value' => $_POST[$fieldSecond], 'invalid' => false, 'bts_class' => '');
  }

  public static function validate_hour(RestaurantHourModel $hour_obj)
  {
    $first_field = 'restaurant-' . $hour_obj->identifier . '-open-hour';
    $second_field = 'restaurant-' . $hour_obj->identifier . '-close-hour';
    $closed_field = 'restaurant-' . $hour_obj->identifier . '-closed';
    $hour_obj->is_closed = isset($_POST[$closed_field]) ? 'checked' : '';

    if (isset($_POST[$first_field])) {
      $hour_obj->open_hour = array('value' => $_POST[$first_field], 'invalid' => false, 'bts_class' => '');
      try {
        new DateTime($_POST[$first_field]);
      } catch (Exception $e) {
        $hour_obj->open_hour = array('value' => $_POST[$first_field], 'invalid' => true, 'bts_class' => 'is-invalid');
        return;
      }
    }
    if (isset($_POST[$second_field])) {
      $hour_obj->close_hour = array('value' => $_POST[$second_field], 'invalid' => false, 'bts_class' => '');
      try {
        new DateTime($_POST[$second_field]);
      } catch (Exception $e) {
        $hour_obj->close_hour = array('value' => $_POST[$second_field], 'invalid' => true, 'bts_class' => 'is-invalid');
        return;
      }
    }
    if ((empty($_POST[$first_field]) || empty($_POST[$second_field])) && !isset($_POST[$closed_field])) {
      throw new Exception('
        Brak wartości godziny otwarcia lub zamknięcia w dniu <strong>' . $hour_obj->name . '</strong>. W przypadku braku
        zaznaczenia opcji "Zamknięte w tym dniu tygodnia" należy wprowadzić godzinę otwarcia i zamknięcia lokalu.
      ');
    }
  }

  public static function check_optional($optional_name, $toggler_name, $pattern): array
  {
    if (!isset($_POST[$toggler_name])) {
      return ValidationHelper::validate_field_regex($optional_name, $pattern);
    }
    return array('value' => '', 'invalid' => false, 'bts_class' => '');
  }

  public static function validate_field_regex(string $value, string $pattern): array
  {
    $without_blanks = trim(htmlspecialchars($_POST[$value]));
    if (empty($without_blanks) || !preg_match($pattern, $without_blanks)) {
      return array('value' => $without_blanks, 'invalid' => true, 'bts_class' => 'is-invalid');
    }
    return array('value' => $without_blanks, 'invalid' => false, 'bts_class' => '');
  }
}
