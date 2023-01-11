<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: CookieHelper.php                               *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-11, 01:26:36                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-11 02:19:27                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services\Helpers;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class CookieHelper
{
    const RESTAURANT_FILTERS = 'restaurant_filters';

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function set_non_expired_cookie($key, $data, $global_path = true)
    {
        unset($_COOKIE[$key]);
        if ($global_path) setcookie($key, $data, time() + (10 * 365 * 24 * 60 * 60), '/');
        else setcookie($key, $data, time() + (10 * 365 * 24 * 60 * 60));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function delete_cookie($key, $global_path = true)
    {
        if (isset($_COOKIE[$key]))
        {
            unset($_COOKIE[$key]);
            if ($global_path) setcookie($key, null, -1, '/');
            else setcookie($key, null, -1);
        }
    }
}
