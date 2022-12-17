<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: Utils.php                                      *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-11-28, 20:29:37                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2022-12-10 22:07:47                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Utils;

class Utils
{
    private const SEQ_CHARS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    //--------------------------------------------------------------------------------------------------------------------------------------

    public static function generate_random_seq($seq_count = 10)
    {
        $random_seq = '';
        for ($i = 0; $i < $seq_count; $i++) $random_seq .= self::SEQ_CHARS[rand(0, strlen(self::SEQ_CHARS) - 1)];
        return $random_seq;
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    public static function validate_field_regex($value, $pattern)
    {
        $without_blanks = trim(htmlspecialchars($_POST[$value]));
        if (empty($without_blanks) || !preg_match($pattern, $without_blanks))
            return array('value' => $without_blanks, 'invl' => true, 'bts_class' => 'is-invalid');
        return array('value' => $without_blanks, 'invl' => false, 'bts_class' => '');
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    public static function validate_email_field($value)
    {
        $without_blanks = trim(htmlspecialchars($_POST[$value]));
        if (empty($without_blanks) || !filter_var($without_blanks, FILTER_VALIDATE_EMAIL))
            return array('value' => $without_blanks, 'invl' => true, 'bts_class' => 'is-invalid');
        return array('value' => $without_blanks, 'invl' => false, 'bts_class' => '');
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    public static function validate_image_regex($value)
    {
        if (!isset($_FILES[$value])) return array('value' => '', 'invl' => false, 'bts_class' => '', 'path' => '', 'ext' => '');

        $path = $_FILES[$value]['tmp_name'];
        $imgValue = $_FILES[$value]['name'];
        $ext = pathinfo($imgValue, PATHINFO_EXTENSION);
        $without_blanks = trim(htmlspecialchars($imgValue));

        $image_info = getimagesize($path);
        $image_size = filesize($path);

        if ($value == 'restaurant-banner')
        {
            if (($image_info[1]/$image_info[0]) >= 0.47 || ($image_info[1]/$image_info[0]) <= 0.42  || $image_size > 5000000) 
                return array('value' => $without_blanks, 'invl' => true, 'bts_class' => 'is-invalid', 'path' => $path, 'ext' => $ext);
        }
        
        if ($value == 'restaurant-profile')
        {
            if ($image_info[0] != $image_info[1]  || $image_size > 5000000)
                return array('value' => $without_blanks, 'invl' => true, 'bts_class' => 'is-invalid', 'path' => $path, 'ext' => $ext);
        }
        return array('value' => $without_blanks, 'invl' => false, 'bts_class' => '', 'path' => $path, 'ext' => $ext);
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    public static function validate_exact_fields($valueFirst, $fieldSecond)
    {
        if ($valueFirst['value'] !== $_POST[$fieldSecond])
            return array('value' => $_POST[$fieldSecond], 'invl' => true, 'bts_class' => 'is-invalid');
        return array('value' => $_POST[$fieldSecond], 'invl' => false, 'bts_class' => '');
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    public static function check_session_and_unset($session_value_key)
    {
        $session_value = null;
        if (isset($_SESSION[$session_value_key]))
        {
            $session_value = $_SESSION[$session_value_key];
            unset($_SESSION[$session_value_key]);
        }
        return $session_value;
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    public static function fill_banner_with_form_data($form_data, $banner_data)
    {
        if (!isset($banner_data)) $banner_data = array(
            'banner_message' => $form_data['banner_message'] ?? '',
            'banner_error' => false,
            'show_banner' => !empty($form_data['banner_message']),
            'banner_class' => isset($form_data['banner_error']) && $form_data['banner_error'] ? 'alert-danger' : 'alert-success',
        );
        return $banner_data;
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    public static function get_pagination_nav($curr_page, $total_per_page, $total_pages, $base_url)
    {
        return array(
            'first_page' => array(
                'is_active' => $curr_page != 1 ? '' : 'disabled',
                'url' => $base_url . '?page=1&total=' . $total_per_page,
            ),
            'prev_page' => array(
                'is_active' => $curr_page - 1 > 0 ? '' : 'disabled',
                'url' => $base_url . '?page=' . $curr_page - 1 . '&total=' . $total_per_page,  
            ),
            'next_page' => array(
                'is_active' => $curr_page < $total_pages ? '' : 'disabled',
                'url' => $base_url . '?page=' . $curr_page + 1 . '&total=' . $total_per_page, 
            ),
            'last_page' => array(
                'is_active' => $curr_page != $total_pages ? '' : 'disabled',
                'url' => $base_url . '?page=' . $total_pages . '&total=' . $total_per_page,
            ),
        );
    }
}
