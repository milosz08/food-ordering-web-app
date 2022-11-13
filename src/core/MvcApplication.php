<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: MvcApplication.php                             *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-11-10, 23:32:11                       *
 * Autor: Milosz08                                             *
 *                                                             *
 * Ostatnia modyfikacja: 2022-11-11 03:52:09                   *
 * Modyfikowany przez: Milosz08                                *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Core;

use ReflectionException;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Główna klasa uruchamiana przy starcie aplikacji. To ona odpowiada za dynamiczne tworzenie przetwarzanie parametru action zapytania    *
 * do serwera oraz dynamiczne tworzenie (na podstawie wartości tego parametru) instancji kontrolera i wywoływanie jego odpowiedniej      *
 * metody (która z kolei zwraca widok). NIE MODYFIKOWAĆ!                                                                                 *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class MvcApplication
{
    private static $_singleton_instance; // instancja klasy MvcApplication jako obiektu singleton
    private $_selected_controller; // mapowany obiekt klasy kontrolera na podstawie zapytania
    private $_renderer_instance; // instancja klasy Renderer obsługującej renderowanie widoków oraz szablonów mustache

    //--------------------------------------------------------------------------------------------------------------------------------------

    private function __construct()
    {
        $this->_renderer_instance = MvcRenderer::get_instance(); // pobranie obiektu umożliwiającego renderowanie widoków
        $this->render_mvc(); // wywołanie metody prywatnej odwiadającej za parsowanie ścieżki i wywołanie metody kontrolera
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda odpowiadająca za tworzenie klasy kontrolera i wywoływanie metody tego kontrolera na podstawie parametrów zapytania. Jeśli
     * podano parametry zapytania nieodpowiadające obecnym kontrolerom lub metodom w wybranym kontrolerze, zostanie rzucony wyjątek, który
     * po przechwyceniu wyświetli stronę 404 (nie znaleziono zasobu).
     */
    private function render_mvc()
    {
        try
        {
            $action_params = $this->parse_url(); // wynik działania metody zwracający nazwę kontrolera i nazwę metody
            // nazwa kontrolera wraz z rozszerzeniem php
            $controller_file = Config::get('__MVC_CONTROLLER_DIR__') . $action_params['controller'] . '.php';

            // sprawdź, czy kontroler o wybranej nazwie istnieje w domyślnym katalogu (/src/controllers)
            if (!file_exists($controller_file)) throw new ReflectionException();
            require_once $controller_file; // zaimportuj plik kontrolera

            // nazwa kontrolera razem z przestrzenią nazw, np. App\Controllers\HomeController bez rozszerzenia .php
            $controller_class_name = Config::get('__MVC_CONTROLLER_NAMESPACE__') . $action_params['controller'];
            $this->_selected_controller = new $controller_class_name; // stworzenie instancji klasy wybranego kontrolera

            // sprawdź, czy metoda z parametru url istnieje w kontrolerze, jeśli nie rzuć wyjątek
            if (!method_exists($this->_selected_controller, $action_params['method'])) throw new ReflectionException();
            // wywołaj programowo metodę z wcześniej stworzonej instancji kontrolera
            call_user_func([ $this->_selected_controller, $action_params['method'] ]);
        }
        catch (ReflectionException $e) // jeśli złapie wyjątek, wyświetl stronę błędu 404
        {
            $this->_renderer_instance->render('_not-found-view'); // renderuj widok błędu 404
            die; // zakończ działanie skryptu
        }
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda odpowiadająca za parsowanie adresu URL z parametrami zapytania. Jeśli nie znajdzie parametrów zapytania, zwracane są domyślne
     * wartości zdefiniowane w pliku config.php (__DEF_METHOD__ oraz __DEF_CONTROLLER__). Przykładowo, jeśli użytkownik przejdzie pod adres:
     *      index.php
     * zostanie zwrócona ścieżka z podstawionymi domyślnym konstruktorem oraz metodą, np:
     *      index.php?action=__DEF_CONTROLLER__/__DEF_METHOD__
     * a po podstawieniu przykładowych stałych:
     *      index.php?action=example/show
     */
    private function parse_url()
    {
        $action_type = Config::get('__MVC_DEF_METHOD__'); // pobranie domyślnej metody kontrolera, jeśli nie poda się parametru action
        if (!isset($_GET['action'])) // jeśli parametr action nie istnieje, tj. jeśli adres to po prostu index.php?
        {
            return array(
                // klucz w tablicy o nazwie 'controller' przechowujący nazwę HomeController (domyślny to home, a suffix to Controller)
                'controller' => ucfirst(Config::get('__MVC_DEF_CONTROLLER__')) . Config::get('__MVC_CONTROLLER_SUFFIX__'),
                'method' =>     $action_type,
            );
        }
        // odseparowanie od siebie nazwy kontrolera oraz metody tego kontrolera, dla przykładu, jeśli zapytanie będzie równe:
        //      index.php?action=home/hello
        // wartość w zmiennej będzie tablicą i będzie to: array('home', 'hello')
        // dodatkowo funkcja rtrim usuwa wszystkie białe znaki, filtr czyści URL a funkcja explode przetwarza ciąg znaków na tablicę
        // rozdzielając te znaki na podstawie delimitera (pierwszy argument funkcji)
        $separate_controller_and_method = explode('/', filter_var(rtrim($_GET['action']), FILTER_SANITIZE_URL));
        // jeśli podano więcej niż jeden parametr przekaż je wszystkie oprócz nazwy kontrolera (parametru pierwszego)
        if (count($separate_controller_and_method) > 1)
        {
            // przypisz wszystkie parametry oprócz pierwszego i złącz je razem poprzez delimiter '_', utworzy to nazwę metody kontrolera
            $action_type = join('_', array_slice($separate_controller_and_method, 1));
        }
        // zwróć tablicę składająca się z nazwy kontrolera (np. HomeController) i metod w postaci tablicy
        return array(
            'controller' => ucfirst($separate_controller_and_method[0]) . Config::get('__MVC_CONTROLLER_SUFFIX__'),
            'method' =>     $action_type,
        );
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda statyczna umożliwiająca instantancję aplikacji. Uruchomić można ją tylko raz (tylko raz dojdzie do stworzenia obiektu).
     * Jeśli obiekt będzie już istniał, pobierze referencję do niego z pamięci.
     */
    public static function run()
    {
        if (!isset(self::$_singleton_instance)) self::$_singleton_instance = new MvcApplication;
    }
}
