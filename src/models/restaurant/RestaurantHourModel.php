<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: RestaurantHourModel.php                        *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-05, 23:03:31                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:49:19                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

class RestaurantHourModel
{
    public $name; // nazwa dnia tygodnia
    public $alias; // alias dnia tygodnia
    public $identifier; // nazwa dnia tygodnia po angielsku (do nazw atrybutów)
    public $active_default; // domyślnie otwarty akordeon (pierwsza wartość, poniedziałek)
    public $open_hour; // godzina otwarcia (wartość i błąd)
    public $close_hour; // godzina zamknięcia (wartość i błąd)
    public $is_closed; // brak godzin otwarcia i zamknięcia, domyślnie zamknięte w wybranym dniu tygodnia

    public function __construct()
    {
        if ($this->alias == 'pn') $this->active_default = array('bts_class' => 'show', 'aria' => 'true', 'collapse' => '');
        else $this->active_default = array('bts_class' => '', 'aria' => 'false', 'collapse' => 'collapsed');
        $this->is_closed = $this->is_closed ? 'checked' : '';
        $this->open_hour = array('value' => $this->open_hour, 'invl' => false, 'bts_class' => '');
        $this->close_hour = array('value' => $this->close_hour, 'invl' => false, 'bts_class' => '');
    }

    public function all_hours_is_valid()
    {
        return !($this->open_hour['invl'] || $this->close_hour['invl']);
    }

    public function format_to_details_view()
    {
        $hour_format = $this->open_hour['value'] . ' - ' . $this->close_hour['value'];
        return array('day_of_week' => $this->alias, 'status' => $this->is_closed ? 'nieczynne' : $hour_format);
    }
}
