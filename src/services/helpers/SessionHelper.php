<?php

namespace App\Services\Helpers;

class SessionHelper
{
  const LOGIN_PAGE_BANNER = 'login_page_banner';
  const FORGOT_PASSWORD_PAGE_BANNER = 'forgot_password_page_banner';
  const FORGOT_PASSWORD_CHANGE_PAGE_BANNER = 'forgot_password_change_page_banner';
  const REGISTER_PAGE_BANNER = 'register_page_banner';
  const DISHES_WITH_RES_PAGE_BANNER = 'dishes_with_res_page_banner';
  const ADD_EDIT_DISH_PAGE_BANNER = 'edit_dish_page_banner';
  const RESTAURANT_DETAILS_PAGE_BANNER = 'restaurant_details_page_banner';
  const RESTAURANTS_PAGE_BANNER = 'restaurants_page_banner';
  const ADD_EDIT_RESTAURANT_PAGE_BANNER = 'add_edit_restaurant_page_banner';
  const DISCOUNTS_PAGE_BANNER = 'discounts_page_banner';
  const DISCOUNTS_RES_PAGE_BANNER = 'discounts_res_page_banner';
  const LOGOUT_PAGE_BANNER = 'logout_page_banner';
  const ADD_USER_NEW_ADDRESS_PAGE_BANNER = 'edit_user_profile_page';
  const USER_SETTINGS_PAGE_BANNER = 'user_profile_page';
  const HOME_RESTAURANTS_LIST_PAGE_BANNER = 'home_restaurants_list_page';
  const ORDER_SUMMARY_PAGE_BANNER = 'order_finish_page';
  const OWNER_RES_SEARCH = 'owner_restaurant_search';
  const OWNER_ORDER_SEARCH = 'owner_order_search';
  const OWNER_RES_DETAILS_SEARCH = 'owner_restaurant_details_search';
  const OWNER_DISHES_SEARCH = 'owner_dishes_search';
  const OWNER_DISHES_RES_SEARCH = 'owner_dishes_res_search';
  const ADMIN_RESTAURANTS_SEARCH = 'admin_restaurants_search';
  const ADMIN_RESTAURANTS_PAGE_BANNER = 'admin_restaurants_banner';
  const ADMIN_RESTAURANT_DETAILS_PAGE_BANNER = 'admin_restaurant_details_banner';
  const ADMIN_PENDING_RES_SEARCH = 'admin_pending_res_search';
  const DISCOUNT_SEARCH = 'discount_search';
  const DISCOUNT_RES_SEARCH = 'discount_res_search';
  const DISH_DETAILS_PAGE_BANNER = 'dish_details_page_banner';
  const DISHES_PAGE_BANNER = 'dishes_page_banner';
  const DISCOUNT_ADD_EDIT_PAGE_BANNER = 'discount_add_edit_page_banner';
  const RESTAURANT_DISHES_PAGE_BANNER = 'restaurant_dishes_page_banner';
  const NEW_ORDER_DETAILS_PAGE_BANNER = 'new_order_details_page_banner';
  const USER_ORDERS_PAGE_BANNER = 'user_orders_page_banner';
  const USER_ORDER_DETAILS_PAGE_BANNER = 'user_order_details_page_banner';
  const OWNER_RATINGS_PAGE_BANNER = 'owner_ratings_page_banner';
  const OWNER_RATINGS_PENDING_TO_DELETE = 'owner_ratings_pending_to_delete';
  const OWNER_ORDERS_PAGE_BANNER = 'owner_orders_page_banner';
  const OWNER_ORDER_DETAILS_PAGE_BANNER = 'owner_order_details_page_banner';
  const ADMIN_RATINGS_PAGE_BANNER = 'admin_ratings_page_banner';
  const ADMIN_RATINGS_PENDING_TO_DELETE = 'admin_ratings_pending_to_delete';
  const ADMIN_PENDING_RESTAURANTS_PAGE_BANNER = 'admin_pending_restaurants_page_banner';
  const ADMIN_DISH_TYPES_PAGE_BANNER = 'admin_dish_types_page_banner';
  const ADMIN_DISH_TYPES_SEARCH = 'admin_dish_types_search';
  const OWNER_DISH_TYPES_SEARCH = 'admin_dish_types_search';
  const OWNER_DISH_TYPES_PAGE_BANNER = 'owner_dish_types_page_banner';
  const ADMIN_MANAGED_USERS_PAGE_BANNER = 'admin_managed_users_banner';
  const ADMIN_MANAGED_USERS_SEARCH = 'admin_managed_users_search';
  const ADMIN_RES_DISHES_SEARCH = 'admin_res_dishes_search';
  const ADMIN_DISH_DETAILS_PAGE_BANNER = 'admin_dish_details_page_banner';
  const ADMIN_USER_DETAILS_PAGE_BANNER = 'admin_user_details_page_banner';
  const FEEDBACK_GIVE_FEEDBACK_PAGE_BANNER = 'feedback_give_feedback_page_banner';
  const FEEDBACK_EDIT_FEEDBACK_PAGE_BANNER = 'feedback_edit_feedback_page_banner';
  const OWNER_SETTINGS_PAGE_BANNER = 'owner_settings_page_banner';
  const ADMIN_SETTINGS_PAGE_BANNER = 'admin_settings_page_banner';
  const OWNER_PROFILE_PAGE_BANNER = 'owner_profile_page_banner';
  const ADMIN_PROFILE_PAGE_BANNER = 'admin_profile_page_banner';

  public static function check_session_and_unset($session_value_key)
  {
    $session_value = null;
    if (isset($_SESSION[$session_value_key])) {
      $session_value = $_SESSION[$session_value_key];
      unset($_SESSION[$session_value_key]);
    }
    return $session_value;
  }

  public static function create_session_banner($key, $message, $banner_error, $custom_baner_style = '')
  {
    if (empty($custom_baner_style)) {
      $banner_class = $banner_error ? 'alert-danger' : 'alert-success';
    } else {
      $banner_class = $custom_baner_style;
    }
    $_SESSION[$key] = array(
      'banner_message' => $message,
      'show_banner' => !empty($message),
      'banner_class' => $banner_class,
    );
  }

  public static function persist_search_text($name, $key)
  {
    $search_text = '';
    if (isset($_POST[$name])) {
      $search_text = $_POST[$name];
      if (empty($search_text)) {
        unset($_SESSION[$key]);
      } else {
        $_SESSION[$key] = $search_text;
      }
    } else if (isset($_SESSION[$key])) {
      $search_text = $_SESSION[$key];
    }
    return $search_text;
  }
}
