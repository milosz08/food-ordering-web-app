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
 * Ostatnia modyfikacja: 2022-11-30 14:47:06                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Utils;

class Utils
{
    public static function validate_field_regex($value, $pattern)
    {
        $without_blanks = trim(htmlspecialchars($_POST[$value]));
        if (empty($without_blanks) || !preg_match($pattern, $without_blanks))
        {
            return array('value' => $without_blanks, 'invl' => true, 'bts_class' => 'is-invalid');
        }
        return array('value' => $without_blanks, 'invl' => false, 'bts_class' => '');
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    public static function validate_email_field($value)
    {
        $without_blanks = trim(htmlspecialchars($_POST[$value]));
        if (empty($without_blanks) || !filter_var($without_blanks, FILTER_VALIDATE_EMAIL))
        {
            return array('value' => $without_blanks, 'invl' => true, 'bts_class' => 'is-invalid');
        }
        return array('value' => $without_blanks, 'invl' => false, 'bts_class' => '');
    }
}
