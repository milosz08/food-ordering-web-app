<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: RestaurantService.php                          *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-11-27, 20:00:52                       *
 * Autor: cptn3m012                                            *
 *                                                             *
 * Ostatnia modyfikacja: 2022-12-03 16:31:35                   *
 * Modyfikowany przez: BubbleWaffle                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services;

use PDO;
use Exception;


use App\Utils\Utils;
use App\Core\MvcService;

class RestaurantService extends MvcService
{
    private $_error;

    protected function __construct()
    {
        parent::__construct();
    }

    public function add_restaurant()
    {
        $this->_error = 'To jest przykładowy error z add restaurant';
        // tak samo jak w authservice (w sensie wartości zwrócone w postaci array('value' => '', 'invl' => false, 'bts_class' => 'is-valid'))
        return array(
            'v_name' => '',
            'v_price' => '',
            'v_street' => '',
            'v_building_no' => '',
            'v_post_code' => '',
            'v_city' => '',
            'error' => $this->_error,
        );
    }

    public function edit_restaurant()
    {
        if (isset($_POST['restaurant-button'])) {
            try {
                $v_name = Utils::validate_field_regex('restaurant-name', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{2,50}$/');
                $v_price = Utils::validate_field_regex('restaurant-delivery-price','/^([1-9][0-9]*|0)(\.[0-9]{2})?$/');
                $v_banner = Utils::validate_image_regex('restaurant-banner');
                $v_profile = Utils::validate_image_regex('restaurant-profile');
                $v_street = Utils::validate_field_regex('restaurant-street', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ ]{2,100}$/');
                $v_building_no = Utils::validate_field_regex('restaurant-building-no', '/^[0-9]{1,5}$/');
                $v_post_code = Utils::validate_field_regex('restaurant-post-code', '/^[0-9]{2}-[0-9]{3}$/');
                $v_city = Utils::validate_field_regex('restaurant-city', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{2,60}$/');

                if (!($v_name['invl'] || $v_price['invl'] || $v_banner['invl'] || $v_profile['invl'] || $v_street['invl'] ||
                    $v_building_no['invl'] || $v_post_code['invl'] || $v_city['invl'])) {
                    move_uploaded_file($v_banner['path'], 'publicUploads/restaurantsUploads/'.$v_banner['value']);
                    move_uploaded_file($v_profile['path'], 'publicUploads/restaurantsUploads/'.$v_profile['value']);
                }
                
            } catch (Exception $e) {
                $this->dbh->rollback();
                $this->_error = $e->getMessage();
            }
            $this->_error = 'Błąd!';
            // tak samo jak w authservice (w sensie wartości zwrócone w postaci array('value' => '', 'invl' => false, 'bts_class' => 'is-valid'))
            return array(
                'v_name' => $v_name,
                'v_price' => $v_price,
                'v_banner' => $v_banner,
                'v_profile' => $v_profile,
                'v_street' => $v_street,
                'v_building_no' => $v_building_no,
                'v_post_code' => $v_post_code,
                'v_city' => $v_city,
                'error' => $this->_error,
            );
        }
    }
}
