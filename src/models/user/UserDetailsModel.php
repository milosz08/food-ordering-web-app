<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: UserDetailsModel.php                           *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-14, 23:42:42                       *
 * Autor: patrick012016                                        *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-16 03:14:07                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

 namespace App\Models;
 
 ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class UserDetailsModel
{
    public $it;
    public $id;
    public $first_name;
    public $last_name;
    public $login;
    public $email;
    public $role;
    public $activated;
    public $address;
    public $status;
    public $full_name;
    
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
