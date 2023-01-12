<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: AdminHelper.php                                *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-12, 18:43:15                       *
 * Autor: BubbleWaffle                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-12 18:57:47                   *
 * Modyfikowany przez: BubbleWaffle                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace App\Services\Helpers;

use Exception;

 class AdminHelper{
    /**
    * Metoda sprawdzająca, czy restauracja istnieje w systemie. Jeśli istnieje,
    * zwraca wartość z kolumny przekazywanej w parametrze. Domyślnie zwraca liczbę wierszy.
    */
    public static function check_if_restaurant_exist_admin($dbh, $res_attr = 'id', $is_accept = ' AND accept = 1', $result_column = 'COUNT(*)')
    {
        $query = "SELECT $result_column FROM restaurants WHERE id = ? $is_accept";
        $statement = $dbh->prepare($query);
        $statement->execute(array($_GET[$res_attr]));
        $result = $statement->fetchColumn();
        if (empty($result))
        {
            if (!empty($is_accept)) throw new Exception(
                'Podana resturacja nie istnieje w systemie lub została wcześniej usunięta.'
            );
            else throw new Exception(
                'Podana resturacja nie istnieje w systemie lub została wcześniej usunięta.'
            );
        }
        $statement->closeCursor();
        return $result;
    }
 }
