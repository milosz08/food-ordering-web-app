<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: ImagesHelper.php                               *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-05, 02:41:03                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-16 05:53:39                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services\Helpers;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class ImagesHelper
{
    public static function upload_restaurant_images($v_profile, $v_banner, $rest_id, $default_profile = '', $default_baner = '')
    {
        $images_paths = array('banner' => $default_baner, 'profile' => $default_profile);
        $image_dir_path = 'uploads/restaurants/' . $_GET['id'] . '/';
        if (!empty($v_profile['value']))
        {
            if (!file_exists($image_dir_path)) mkdir($image_dir_path, 0777, true);
            $images_paths['profile'] = $image_dir_path . $_GET['id'] . '_profile.' . $v_profile['ext'];
            move_uploaded_file($v_profile['path'], $images_paths['profile']);
        }
        if (!empty($v_banner['value']))
        {
            if (!file_exists($image_dir_path)) mkdir($image_dir_path, 0777, true);
            $images_paths['banner'] = $image_dir_path . $_GET['id'] . '_banner.' . $v_banner['ext'];
            move_uploaded_file($v_banner['path'], $images_paths['banner']);
        }
        return $images_paths;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function upload_dish_image($v_profile, $dish_id, $default_profile = '')
    {
        $profile = $default_profile;
        if (!empty($v_profile['value']))
        {
            $image_dir_path = 'uploads/restaurants/' . $_GET['resid'] . '/dishes/';
            if (!file_exists($image_dir_path)) mkdir($image_dir_path, 0777, true);
            $profile = $image_dir_path . $dish_id . '_dish_profile.' . $v_profile['ext'];
            move_uploaded_file($v_profile['path'], $profile);
        }
        return $profile;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function generate_stars_definitions($avg_grades, $is_integer = false)
    {
        if ($is_integer) $grade_n = intval($avg_grades);
        else $grade_n = floatval(str_replace(',', '.', $avg_grades));
        $grades_bts = array(array('star' => ''), array('star' => ''), array('star' => ''), array('star' => ''), array('star' => ''));
        for ($i = 0; $i < 5; $i++)
        {
            if ($grade_n < $i + 1 && $grade_n > $i) $grades_bts[$i]['star'] = '-half';
            else if ($grade_n >= $i + 1) $grades_bts[$i]['star'] = '-fill';
        }
        return $grades_bts;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function upload_user_profile_image($v_profile, $user_id, $default_profile = '')
    {
        $profile = $default_profile;
        if (!empty($v_profile['value']))
        {
            $image_dir_path = 'uploads/users/' . $user_id . '/';
            if (!file_exists($image_dir_path)) mkdir($image_dir_path, 0777, true);
            $profile = $image_dir_path . $user_id . '_user_profile.' . $v_profile['ext'];
            move_uploaded_file($v_profile['path'], $profile);
        }
        return $profile;
    }
}
