<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: index.php                                      *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-11-10, 17:21:57                       *
 * Autor: Milosz08                                             *
 *                                                             *
 * Ostatnia modyfikacja: 2022-12-13 03:49:49                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

use App\Core\CoreLoader;
use App\Core\MvcRenderer;
use App\Core\MvcApplication;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Plik główny aplikacji inicjujący ładowanie zasobów oraz tworzenie głównej instancji klasy MvcApplication. Cały ruch serwera           *
 * przechodzi przez ten plik. Dodatkowe informacje odnośnie strukty katalogów znajdziesz w README.md.                                    *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

require_once '../vendor/autoload.php'; // import pliku do automatycznego ładowania paczek zasobów pobranych przez PHP Composer

session_start(); //uruchomienie sesji serwera php

$server_dir = explode('/', $_SERVER['PHP_SELF']); // główny folder aplikacji w formie tablicy
$normalized_dir = join('/', array_splice($server_dir, 0, array_search('public', $server_dir))); // normalizowana ścieżka aplikacji

//------------------------------------------------------------------------------------------------------------------------------------------

define('__SEP__', DIRECTORY_SEPARATOR); // zadeklarowanie domyślnego separatora plików w formie stałej globalnej
define('__ROOT__', realpath(dirname(__FILE__) . __SEP__ . '..')); // stała definiująca ścieżkę do głównego katalogu aplikacji
define('__SCAN_DIRS__', array('core', 'utils', 'models', 'services')); // katalogi, których pliki podlegają ładowaniu
define('__SRC_DIR__', __ROOT__ . __SEP__ . 'src' . __SEP__); // ścieżka do katalogu /src/
define('__PROTO__', isset($_SERVER['HTTPS']) ? 'https://' : 'http://'); // protokół serwera: HTTP/HTTPS
define('__URL_INIT_DIR__', count(explode('/', $_SERVER['PHP_SELF'])) < 4 ? '/' : $normalized_dir . '/' );

//------------------------------------------------------------------------------------------------------------------------------------------

require_once __SRC_DIR__ . 'core' . __SEP__ . 'CoreLoader.php'; // import loadera plików

CoreLoader::load(); // instantancja i ładowanie rdzenia aplikacji
MvcRenderer::load(); // ładowanie konfiguracji silnika szablonów
MvcApplication::run(); // instantacja i uruchomienie aplikacji
