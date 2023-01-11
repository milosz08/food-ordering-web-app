<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: ValidationHelper.php                           *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-05, 01:19:05                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-10 02:16:32                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services\Helpers;

use DateTime;
use Exception;
use App\Core\Config;
use App\Core\ResourceLoader;
use App\Models\RestaurantHourModel;

ResourceLoader::load_model('RestaurantHourModel', 'restaurant');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class ValidationHelper
{
    public static function validate_field_regex(string $value, string $pattern)
    {
        $without_blanks = trim(htmlspecialchars($_POST[$value]));
        if (empty($without_blanks) || !preg_match($pattern, $without_blanks))
            return array('value' => $without_blanks, 'invl' => true, 'bts_class' => 'is-invalid');
        return array('value' => $without_blanks, 'invl' => false, 'bts_class' => '');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function validate_email_field(string $value)
    {
        $without_blanks = trim(htmlspecialchars($_POST[$value]));
        if (empty($without_blanks) || !filter_var($without_blanks, FILTER_VALIDATE_EMAIL))
            return array('value' => $without_blanks, 'invl' => true, 'bts_class' => 'is-invalid');
        return array('value' => $without_blanks, 'invl' => false, 'bts_class' => '');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function validate_image_regex(string $value)
    {
        if (!isset($_FILES[$value]) || empty($_FILES[$value]['name'])) 
            return array('value' => '', 'invl' => false, 'bts_class' => '', 'path' => '', 'ext' => '');

        $path = $_FILES[$value]['tmp_name'];
        $imgValue = $_FILES[$value]['name'];
        $ext = pathinfo($imgValue, PATHINFO_EXTENSION);
        $without_blanks = trim(htmlspecialchars($imgValue));

        $image_info = getimagesize($path);
        $image_size = filesize($path);

        if (strpos($value, 'banner'))
        {
            if (($image_info[1]/$image_info[0]) >= 0.47 || ($image_info[1]/$image_info[0]) <= 0.42  || $image_size > 5000000) 
                return array('value' => $without_blanks, 'invl' => true, 'bts_class' => 'is-invalid', 'path' => $path, 'ext' => $ext);
        }
        if (strpos($value, 'profile'))
        {
            if ($image_info[0] != $image_info[1]  || $image_size > 5000000)
                return array('value' => $without_blanks, 'invl' => true, 'bts_class' => 'is-invalid', 'path' => $path, 'ext' => $ext);
        }
        return array('value' => $without_blanks, 'invl' => false, 'bts_class' => '', 'path' => $path, 'ext' => $ext);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function validate_exact_fields($valueFirst, string $fieldSecond)
    {
        if ($valueFirst['value'] !== $_POST[$fieldSecond])
            return array('value' => $_POST[$fieldSecond], 'invl' => true, 'bts_class' => 'is-invalid');
        return array('value' => $_POST[$fieldSecond], 'invl' => false, 'bts_class' => '');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function validate_hour(RestaurantHourModel &$hour_obj)
    {
        $first_field = 'restaurant-' . $hour_obj->identifier . '-open-hour';
        $second_field = 'restaurant-' . $hour_obj->identifier . '-close-hour';
        $closed_field = 'restaurant-' . $hour_obj->identifier . '-closed';
        $hour_obj->is_closed = isset($_POST[$closed_field]) ? 'checked' : '';
        
        if (isset($_POST[$first_field]))
        {
            $hour_obj->open_hour = array('value' => $_POST[$first_field], 'invl' => false, 'bts_class' => '');
            $open_time = new DateTime($_POST[$first_field]);
            if (!$open_time)
            {
                $hour_obj->open_hour = array('value' => $_POST[$first_field], 'invl' => true, 'bts_class' => 'is-invalid');
                return;
            }
        }
        if (isset($_POST[$second_field]))
        {
            $hour_obj->close_hour = array('value' => $_POST[$second_field], 'invl' => false, 'bts_class' => '');
            $close_time = new DateTime($_POST[$second_field]);
            if (!$close_time)
            {
                $hour_obj->close_hour = array('value' => $_POST[$second_field], 'invl' => true, 'bts_class' => 'is-invalid');
                return;
            }
        }
        if ((empty($_POST[$first_field]) || empty($_POST[$second_field])) && !isset($_POST[$closed_field])) throw new Exception('
            Brak wartości godziny otwarcia i/lub zamknięcia w dniu <strong>' . $hour_obj->name . '</strong>. W przypadku braku zaznaczenia 
            opcji "Zamknięte w  tym dniu tygodnia" należy wprowadzić godzinę otwarcia i zamknięcia lokalu.
        ');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function check_optional($optional_name, $toggler_name, $pattern_config_name)
    {
        if (!isset($_POST[$toggler_name]))
            return ValidationHelper::validate_field_regex($optional_name, Config::get($pattern_config_name));
        return array('value' => '', 'invl' => false, 'bts_class' => '');
    }
}
