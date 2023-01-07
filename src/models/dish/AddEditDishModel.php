<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: AddEditDishModel.php                           *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-04, 17:06:14                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-05 04:48:08                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class AddEditDishModel
{
    public $name; // nazwa potrawy
    public $description; // opis potrawy
    public $price; // cena za potrawę
    public $prepared_time; // średni czas przygotowania
    public $photo_url; // zdjęcie potrawy
    public $type; // typ potrawy (select box)
    public $custom_type; // nowy typ potrawy (select box)

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct()
    {
        $this->name = array('value' => $this->name, 'invl' => false, 'bts_class' => '');
        $this->description = array('value' => $this->description, 'invl' => false, 'bts_class' => '');
        $this->price = array('value' => $this->price, 'invl' => false, 'bts_class' => '');
        $this->photo_url = array('value' => $this->photo_url, 'invl' => false, 'bts_class' => '');
        $this->prepared_time = array('value' => $this->prepared_time, 'invl' => false, 'bts_class' => '');
        $this->type = array('value' => $this->type, 'invl' => false, 'bts_class' => '');
        $this->custom_type = array('value' => '', 'invl' => false, 'bts_class' => '');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function all_is_valid()
    {
        return !($this->name['invl'] || $this->description['invl'] || $this->price['invl'] || $this->photo_url['invl'] ||
            $this->prepared_time['invl'] || $this->custom_type['invl']);
    }
}
