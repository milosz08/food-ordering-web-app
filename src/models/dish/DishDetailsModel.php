<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: DishDetailsModel.php                           *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-03, 19:18:04                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-04 19:59:14                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class DishDetailsModel
{
    public $name; // nazwa potrawy
    public $type; // typ potrawy
    public $description; // opis potrawy
    public $price; // cena za potrawę
    public $r_name; // nazwa restauracji do której przypisana jest potrawa
    public $r_address; // adres restauracji do której przypisana jest potrawa
    public $r_full_name; // imię i nazwisko właściciela restauracji
    public $r_delivery_price; // cena za dostawę
    public $total_price; // całkowita cena za potrawę (dostawa + cena potrawy)
    public $photo_url; // ścieżka do grafiki ilustrującej potrawę
    public $is_custom_type; // czy typ potrawy jest dodany przez użytkownika, czy jest z systemu
    public $prepared_time; // średni czas przygotowania potrawy

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct()
    {
        if (!$this->photo_url) $this->photo_url = false;
        $this->is_custom_type = $this->is_custom_type ? 'Stworzony przez użytkownika' : 'Domyślna wartość systemu';
    }
}
