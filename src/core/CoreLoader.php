<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: CoreLoader.php                                 *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-11-10, 23:34:33                       *
 * Autor: Milosz08                                             *
 *                                                             *
 * Ostatnia modyfikacja: 2022-11-11 04:19:55                   *
 * Modyfikowany przez: Milosz08                                *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Core;

use Dotenv\Dotenv;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Klasa umożliwiająca dodawanie plików z folderu plików źródłowych aplikacji /src/. Pliki te są dodawane z folderów, których nazwy      *
 * zdefiniowane są w stałej globalnej __SCAN_DIRS__ (patrz index.php). Dzięki wywołowaniu metody load() nie ma potrzeby manualnego       *
 * ładowania plików źródłowych z folderu /src/. NIE MODYFIKOWAĆ!                                                                         *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class CoreLoader
{
    private static $_singleton_instance; // instancja klasy CoreLoader jako obiektu singleton
    
    //--------------------------------------------------------------------------------------------------------------------------------------

    private function __construct()
    {
        $dotenv = Dotenv::createImmutable(__ROOT__); // wskazanie ścieżki pliku .env
        $dotenv->load(); // ładowanie pliku .env i znajdujących się w nim zmiennych
        $this->scanning_dirs_and_load_files(); // uruchomienie funkcji do ładowania klas
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda statyczna umożliwiająca ładowanie plików ze wskazanych katalogów (ładowanie z użyciem instrukcji require_once). Lista
     * ładowanych katalogów dostępna jest pod stałą globalną __SCAN_DIRS__ (patrz index.php). Metoda musi być uruchomiona przed
     * instantancją głównej aplikacji metodą run z klasy MvcApplication.
     */
    private function scanning_dirs_and_load_files()
    {
        foreach (__SCAN_DIRS__ as $dir_name) // pętla przechodząca przez wszystkie katalogi
        {
            // znajdź wszystkie pliki w wybranym katalogu z rozszerzeniem php
            $files_array = glob(__SRC_DIR__ . $dir_name . __SEP__ . "*.php", GLOB_BRACE);
            foreach ($files_array as $file) // przejście przez wszystkie pliki katalogu
            {
                if ($file !== __FILE__) require_once $file; // jeśli plik nie odnosi się do pliku CoreLoader.php, załaduj
            }
        }
        require_once  __SRC_DIR__ . 'config.php'; // ładowanie dodatkowego pliku konfiguracyjnego
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda statyczna umożliwiająca załadowanie klas rdzenia. Uruchomić można ją tylko raz (tylko raz dojdzie do stworzenia obiektu).
     * Jeśli obiekt będzie już istniał, pobierze referencję do niego z pamięci.
     */
    public static function load()
    {
        if (!isset(self::$_singleton_instance)) self::$_singleton_instance = new CoreLoader;
    }
}
