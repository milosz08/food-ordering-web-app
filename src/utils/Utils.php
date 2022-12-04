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
 * Ostatnia modyfikacja: 2022-12-04 02:01:09                   *
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
        $path = $_FILES[$value]['tmp_name'];
        $imgValue = $_FILES[$value]['name'];
        $ext = pathinfo($imgValue, PATHINFO_EXTENSION);
        $without_blanks = trim(htmlspecialchars($imgValue));
        
        if (empty($path))
            return array('value' => $without_blanks, 'invl' => true, 'bts_class' => 'is-invalid', 'path' => $path, 'ext' => $ext);;

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

}
