<?php

namespace App\Core;

use Dotenv\Dotenv;

/**
 * Klasa umożliwiająca dodawanie plików z folderu plików źródłowych aplikacji /src/. Pliki te są dodawane z folderów, których nazwy
 * zdefiniowane są w stałej globalnej __SCAN_DIRS__ (patrz index.php). Dzięki wywołaniu metody load() nie ma potrzeby manualnego
 * ładowania plików źródłowych z folderu /src/. NIE MODYFIKOWAĆ!
 */
class CoreLoader
{
  private static $_singleton_instance; // instancja klasy CoreLoader jako obiektu singleton

  private function __construct()
  {
    $dotenv = Dotenv::createImmutable(__ROOT__); // wskazanie ścieżki pliku .env
    $dotenv->safeLoad(); // ładowanie pliku .env i znajdujących się w nim zmiennych
    $this->load_application_core(); // uruchomienie funkcji do ładowania klas
  }

  /**
   * Metoda statyczna umożliwiająca ładowanie plików ze wskazanych katalogów (ładowanie z użyciem instrukcji require_once). Lista
   * ładowanych katalogów dostępna jest pod stałą globalną __SCAN_DIRS__ (patrz index.php). Metoda musi być uruchomiona przed
   * stworzeniem głównej aplikacji metodą run z klasy MvcApplication.
   */
  private function load_application_core()
  {
    // znajdź wszystkie pliki w wybranym katalogu z rozszerzeniem php
    $files_array = glob(__SRC_DIR__ . 'core' . __SEP__ . "*.php", GLOB_BRACE);
    // przejście przez wszystkie pliki katalogu
    foreach ($files_array as $file) {
      if ($file !== __FILE__) {
        require_once $file; // jeśli plik nie odnosi się do pliku CoreLoader.php, załaduj
      }
    }
    require_once __SRC_DIR__ . 'config.php'; // ładowanie dodatkowego pliku konfiguracyjnego
  }

  /**
   * Metoda statyczna umożliwiająca załadowanie klas rdzenia. Uruchomić można ją tylko raz (tylko raz dojdzie do stworzenia obiektu).
   * Jeśli obiekt będzie już istniał, pobierze referencję do niego z pamięci.
   */
  public static function load()
  {
    if (!isset(self::$_singleton_instance)) {
      self::$_singleton_instance = new CoreLoader;
    }
  }
}
