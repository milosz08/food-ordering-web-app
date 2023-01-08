<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: AuthHelper.php                                 *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-05, 01:00:18                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-05 01:19:16                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services\Helpers;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class AuthHelper
{
    private const SEQ_CHARS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function generate_random_seq($seq_count = 10)
    {
        $random_seq = '';
        for ($i = 0; $i < $seq_count; $i++) $random_seq .= self::SEQ_CHARS[rand(0, strlen(self::SEQ_CHARS) - 1)];
        return $random_seq;
    }

}
