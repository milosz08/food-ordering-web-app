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
 * Ostatnia modyfikacja: 2023-01-05 17:39:51                   *
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
}
