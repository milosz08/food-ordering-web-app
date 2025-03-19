<?php

use App\Core\CoreLoader;
use App\Core\MvcApplication;
use App\Core\MvcRenderer;

/**
 * Plik główny aplikacji inicjujący ładowanie zasobów oraz tworzenie głównej instancji klasy MvcApplication. Cały ruch serwera przechodzi
 * przez ten plik. Dodatkowe informacje odnośnie struktury katalogów znajdziesz w README.md.
 */

session_start(); //uruchomienie sesji serwera php

$server_dir = explode('/', $_SERVER['PHP_SELF']); // główny folder aplikacji w formie tablicy
$normalized_dir = join('/', array_splice($server_dir, 0, array_search('public', $server_dir))); // normalizowana ścieżka aplikacji

const __SEP__ = DIRECTORY_SEPARATOR; // zadeklarowanie domyślnego separatora plików w formie stałej globalnej
define('__ROOT__', realpath(dirname(__FILE__) . __SEP__ . '..')); // stała definiująca ścieżkę do głównego katalogu aplikacji
const __SRC_DIR__ = __ROOT__ . __SEP__ . 'src' . __SEP__; // ścieżka do katalogu /src/
define('__PROTO__', 'http' . (isset($_SERVER['HTTPS']) ? 's://' : '://')); // protokół serwera: HTTP/HTTPS
define('__URL_INIT_DIR__', count(explode('/', $_SERVER['PHP_SELF'])) < 4 ? '/' : $normalized_dir . '/');

// import pliku do automatycznego ładowania paczek zasobów pobranych przez PHP Composer
require_once '..' . __SEP__ . 'vendor' . __SEP__ . 'autoload.php';
require_once __SRC_DIR__ . 'core' . __SEP__ . 'CoreLoader.php'; // import ładowacza plików

CoreLoader::load(); // stworzenie i ładowanie rdzenia aplikacji
MvcRenderer::load(); // ładowanie konfiguracji silnika szablonów
MvcApplication::run(); // stworzenie i uruchomienie aplikacji
