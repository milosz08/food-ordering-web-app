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
 * Ostatnia modyfikacja: 2023-01-11 23:20:20                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services\Helpers;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class CookieHelper
{
    const RESTAURANT_FILTERS        = 'restaurant_filters';
    const SHOPPING_CART             = 'shopping_cart';

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function set_non_expired_cookie($key, $data, $global_path = true)
    {
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

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function get_shopping_cart_name($res_id)
    {
        if (isset($_SESSION['logged_user'])) return 'shopping_cart' . '_usr' . $_SESSION['logged_user']['user_id'] . '_res' . $res_id;
        else '';
    }
}
