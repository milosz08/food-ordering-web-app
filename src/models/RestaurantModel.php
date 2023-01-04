<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: RestaurantModel.php                            *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-12-09, 21:18:35                       *
 * Autor: Lukasz Krawczyk                                      *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-02 20:57:46                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace App\Models;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class RestaurantModel
{
    public $it; // indeks wiersza
    public $id; //id z bazy danych
    public $name; // nazwa restauracji
    public $address; // adres lokalu 
    public $accept; //czy zatwierdzona przez administratora
    public $status; // status

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    public function __construct()
    {
        $this->status = array(
            'text' => empty($this->accept) ? 'oczekująca' : 'aktywna',
            'color_bts' => empty($this->accept) ? 'text-danger' : 'text-success',
            'tooltip_text' => empty($this->accept)
                ? 'Zostało wysłane zgłoszenie do administratora systemu, po akceptacji zmieni status na "aktywna"'
                : 'Restauracja widoczna jest dla wszystkich użytkowników',
        );
    }
}
