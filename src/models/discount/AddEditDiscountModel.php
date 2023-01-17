<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: AddEditDiscountModel.php                       *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-12, 10:19:41                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-12 10:24:53                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class AddEditDiscountModel
{
    public $code;
    public $description;
    public $percentage_discount;
    public $max_usages;
    public $expired_date;

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct()
    {
        $this->code = array('value' => $this->code, 'invl' => false, 'bts_class' => '');
        $this->description = array('value' => $this->description, 'invl' => false, 'bts_class' => '');
        $this->percentage_discount = array('value' => $this->percentage_discount, 'invl' => false, 'bts_class' => '');
        $this->max_usages = array('value' => $this->max_usages, 'invl' => false, 'bts_class' => '');
        $this->expired_date = array('value' => $this->expired_date, 'invl' => false, 'bts_class' => '');
    }
    
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function all_is_valid()
    {
        return !($this->code['invl'] || $this->description['invl'] || $this->percentage_discount['invl'] || $this->max_usages['invl'] ||
            $this->expired_date['invl']);
    }
}
