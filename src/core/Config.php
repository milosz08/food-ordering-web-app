<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: Config.php                                     *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-11-10, 23:35:12                       *
 * Autor: Milosz08                                             *
 *                                                             *
 * Ostatnia modyfikacja: 2022-11-11 20:29:39                   *
 * Modyfikowany przez: Patryk Górniak                          *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Core;

use Exception;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Klasa konfiguracyjna przechowująca statyczną tablicę asocjacyjną z właściwościami KLUCZ->WARTOŚĆ dostępnymi z poziomu całej aplikacji *
 * oraz dwie publiczne metody: set do ustawiania parametru oraz get do pobierania parametru. Po więcej informacji jak definiować         *
 * wartości konfiguracyjne i jak się później do nich odwoływać w programie, patrz plik config.php. NIE MODYFIKOWAĆ!                      *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class Config
{
    /**
     * Zadeklarowanie pustej tablicy asocjacyjnej przechowującej globalne wartości konfiguracyjne aplikacji. Właściwości mają postać:
     *      $config_data = array(
     *          ['key'] => value,
     *          ['key'] => value,
     *          ...
     *      );
     * gdzie odpowiednio key odpowiada pierwszemu parametrowi metody set oraz get, a value wartości korelowanej z tym kluczem (parametr
     * drugi metody set). Wartości konfiguracyjne z tej tablicy możliwe są do uzyskania jedynie poprzez metode publiczą get.
     */
    private static $config_data = array();

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda dodająca nową wartość konfiguracyjną do globalnej tablicy $config_data składającej się z klucza i wartości, gdzie odpowiednio
     * pierwszy parametr metody to klucz a drugi to wartość. Klucz musi być w formacie string, natomiast wartość może być w dowolnym innym
     * formacie (nawet może być obiektem, choć nie jest to zalecane podejście, wartości konfiguracyjne najlepiej pozostawić atomowe).
     */
    public static function set($key, $value)
    {
        if (array_key_exists($key, self::$config_data)) // sprawdź, czy w tablicy nie znajduje się już podany klucz, jeśli tak wyjątek
        {
            throw new Exception('Klucz ' . $key . ' znajduje się już w tablicy konfiguracyjnej.');
        }
        self::$config_data[$key] = $value; // dodawanie wartości pod wskazany klucz (jako indeks tablicy asocjacyjnej)
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda pobierająca wartość konfiguracyjną z globalnej tablicy $config_data na podstawie przekazywanego klucza w parametrze metody.
     * Jeśli podany klucz nie znajduje się w tablicy konfiguracyjnej, metoda wyrzuci wyjątek.
     */
    public static function get($key)
    {
        if (!array_key_exists($key, self::$config_data)) // sprawdź, czy w tablicy znajduje się podany klucz
        {
            throw new Exception('Podany klucz: "' . $key . '" nie istnieje w tablicy wartości konfiguracyjnych.');
        }
        return self::$config_data[$key]; // zwróć wartość na podstawie podanego klucza
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda tworząca ścieżkę poprzez dodawanie między kolejnymi segmentami znaków separacji (dopasowywanych dynamiczne na podstawie
     * systemu operacyjnego). Parametr $segments może przyjmować wiele wartości. Przykładowe użycie metody:
     *      $path = build_path('src', 'utils', 'example');
     * Po wywołaniu metody w zmiennej $path będzie następująca ścieżka (ścieżka ta jest zależna od typu zapisywnia struktury katalogów SO):
     *      src/utls/example/ lub src\\utils\\example\\
     */
    public static function build_path(...$segments)
    {
        return join(__SEP__, $segments) . __SEP__;
    }
}
