<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Klasa przechowująca instancję klasy PDO. Można do niej dołączyć dodatkowe metody i zapytania wspólne dla wielu sekcji aplikacji aby
 * uniknąć redundancji w kodzie. Jest klasą typu singleton i instancje wstrzykiwane są w klasy MvcController oraz MvcService w celu
 * łatwiejszego używania PDO w klasach pochodnych.
 */
class PdoDbContext
{
  private static $_singleton_instance; // instancja klasy PdoDbContext jako obiektu singleton
  private $_db_handler; // instancja PDO

  private function __construct()
  {
    try {
      // stworzenie instancji klasy PDO i połączenie się z bazą danych
      $this->_db_handler = new PDO
      (
        Config::get('__DB_DSN__'),
        Config::get('__DB_USERNAME__'),
        Config::get('__DB_PASSWORD__'),
        Config::get('__DB_INIT_COMMANDS__')
      );
      $this->_db_handler->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // ustawienie trybu błędów na rzucanie wyjątków
    } catch (PDOException $e) {
      echo 'Nie udało połączyć się z bazą danych.';
      die;
    }
  }

  /**
   * Metoda statyczna umożliwiająca stworzenie klasy PdoDbContext. Uruchomić można ją tylko raz (tylko raz dojdzie do stworzenia
   * obiektu). Jeśli obiekt będzie już istniał, pobierze referencję do niego z pamięci.
   */
  public static function get_instance(): PdoDbContext
  {
    if (!isset(self::$_singleton_instance)) {
      self::$_singleton_instance = new PdoDbContext;
    }
    return self::$_singleton_instance;
  }

  /**
   * Metoda getter zwracająca uchwyt do bazy danych.
   */
  public function get_handler(): PDO
  {
    return $this->_db_handler;
  }
}
