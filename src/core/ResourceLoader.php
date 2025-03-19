<?php

namespace App\Core;

/**
 * Klasa przechowująca metody statyczne odpowiadające za ładowanie plików przy użyciu dyrektyw require_once() i ich pochodnych.
 * NIE MODYFIKOWAĆ!
 */
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
