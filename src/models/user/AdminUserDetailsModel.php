<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: AdminUserDetailsModel.php                      *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-16, 02:39:10                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-16 03:30:53                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class AdminUserDetailsModel
{
    public $id; // id użytkownika
    public $profile_url; // ścieżka do zdjęcia profilowego użytkownika
    public $login; // login użytkownika
    public $email_address; // adres email użytkownika
    public $address; // adres użytkownika
    public $status; // status konta użytkownika
    public $role; // rola użytkownika
    public $activated; // czy konto zostało aktywowane
    public $phone_number; // numer telefonu do użytkownika

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    public function __construct()
    {
        $this->status = array(
            'text' => empty($this->activated) ? 'dezaktywowane' : 'aktywne',
            'color_bts' => empty($this->activated) ? 'text-danger' : 'text-success',
            'tooltip_text' => empty($this->activated)
                ? 'Na założone konto użytkownika został wysłany link z aktywacją, po akceptacji konto zmieni status na "aktywne"'
                : 'Konto użytkownika zostało aktywowane',
        );
    }
}
