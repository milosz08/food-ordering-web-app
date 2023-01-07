<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: SessionHelper.php                              *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-05, 01:52:24                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-07 19:21:02                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services\Helpers;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class SessionHelper
{
    const LOGIN_PAGE_BANNER = 'login_page_banner';
    const FORGOT_PASSWORD_PAGE_BANNER = 'forgot_password_page_banner';
    const REGISTER_PAGE_BANNER = 'register_page_banner';
    const PENDING_RESTAURANT_PAGE_BANNER = 'pending_restaurant_page_banner';
    const ADD_EDIT_DISH_PAGE_BANNER = 'edit_dish_page_banner';
    const RESTAURANT_DETAILS_PAGE_BANNER = 'restaurant_details_page_banner';
    const RESTAURANTS_PAGE_BANNER = 'restaurants_page_banner';
    const ADD_EDIT_RESTAURANT_PAGE_BANNER = 'add_edit_restaurant_page_banner';
    const LOGOUT_PAGE_BANNER = 'logout_page_banner';
    const EDIT_USER_PROFILE_PAGE_BANNER = 'edit_user_profile_page';
    const USER_PROFILE_PAGE_BANNER = 'user_profile_page';
    const HOME_RESTAURANTS_LIST_PAGE_BANNER = 'home_restaurants_list_page';

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function create_session_banner($key, $message, $banner_error, $custom_baner_style = '')
    {
        if (empty($custom_baner_style)) $banner_class = $banner_error ? 'alert-danger' : 'alert-success';
        else $banner_class = $custom_baner_style;
        $_SESSION[$key] = array(
            'banner_message' => $message,
            'show_banner' => !empty($message),
            'banner_class' => $banner_class,
        );
    }
}
