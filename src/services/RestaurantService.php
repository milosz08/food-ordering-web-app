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
 * Ostatnia modyfikacja: 2022-11-30 14:38:55                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services;

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
        $this->_error = 'To jest przykładowy error z edit restaurant';
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
}
