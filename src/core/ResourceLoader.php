<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: ResourceLoader.php                             *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-05, 01:03:58                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:46:17                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Core;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Klasa przechowująca metody statyczne odpowiadające za ładowanie plików przy użyciu dyrektyw require_once() i ich pochodnych.          *
 * NIE MODYFIKOWAĆ!                                                                                                                      *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class ResourceLoader
{
    /**
     * Metoda odpowiadająca za ładowanie klas serwisów w kontrolerach.
     */
    public static function load_service($service_class, $dir_name = '')
    {
        require_once __SRC_DIR__ . 'services' . __SEP__ . $dir_name . __SEP__ . $service_class . '.php';
    }

    /**
     * Metoda odpowiadająca za ładowanie klas pomocniczych serwisów w kontrolerach.
     */
    public static function load_service_helper($service_helper_class)
    {
        require_once __SRC_DIR__ . 'services' . __SEP__ . 'helpers' . __SEP__ . $service_helper_class . '.php';
    }

    public static function load_model($model_class, $dir_name = '')
    {
        require_once __SRC_DIR__ . 'models' . __SEP__ . $dir_name . __SEP__ . $model_class . '.php';
    }
}
