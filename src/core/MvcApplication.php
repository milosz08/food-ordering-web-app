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
 * Ostatnia modyfikacja: 2023-01-07 00:24:22                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Core;

use ReflectionException;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function __construct()
    {
        $this->_renderer_instance = MvcRenderer::get_instance(); // pobranie obiektu umożliwiającego renderowanie widoków
        $this->render_mvc(); // wywołanie metody prywatnej odwiadającej za parsowanie ścieżki i wywołanie metody kontrolera
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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
            $controller_file = Config::get('__MVC_CONTROLLER_DIR__') . $action_params['directory'] . $action_params['controller'] . '.php';
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
            $this->_renderer_instance->render('_not-found-view', array( // renderuj widok błędu 404
                'page_title' => '404',
            ));
            die; // zakończ działanie skryptu
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda odpowiadająca za parsowanie adresu URL z parametrami zapytania. Jeśli nie znajdzie parametrów zapytania, zwracane są domyślne
     * wartości zdefiniowane w pliku config.php (__DEF_METHOD__ oraz __DEF_CONTROLLER__). Przykładowo, jeśli użytkownik przejdzie pod adres:
     *      /index.php
     * zostanie zwrócona ścieżka z podstawionymi domyślnym konstruktorem oraz metodą, np:
     *      /index.php?action=__DEF_CONTROLLER__/__DEF_METHOD__
     * a po podstawieniu przykładowych stałych:
     *      /index.php?action=example/show
     * Po sparsowaniu przez silnik Apache, adres będzie wyglądał następująco:
     *      /nazwa-katalogu/nazwa-kontrolera/nazwa-metody/{argumenty}
     * lub w przypadku braku katalogu:
     *      /nazwa-kontrolera/nazwa-metody/{argumenty}
     */
    private function parse_url()
    {
        $action_type = Config::get('__MVC_DEF_METHOD__'); // pobranie domyślnej metody kontrolera, jeśli nie poda się parametru action
        $dir_name = ''; // nazwa katalogu, w którym znajduje się kontroler
        $controller_name = ''; // nazwa kontrolera (z suffixem, np. HomeController)
        
        // odseparowanie od siebie nazwy kontrolera oraz metody tego kontrolera, dla przykładu, jeśli zapytanie będzie równe:
        //      /auth/login/testing-method
        // wartość w zmiennej będzie tablicą i będzie to: array('auth', 'login', 'testing_method')
        // dodatkowo funkcja rtrim usuwa wszystkie białe znaki, filtr czyści URL a funkcja explode przetwarza ciąg znaków na tablicę
        // rozdzielając te znaki na podstawie delimitera (pierwszy argument funkcji)
        $separate_controller_and_method = explode('/', filter_var(rtrim($_GET['action']), FILTER_SANITIZE_URL));
        
        // sprawdź, czy pierwszy parametr nie jest katalogiem, jeśli nie, przypisz ten argument do nazwy kontrolera
        if (!file_exists(Config::get('__MVC_CONTROLLER_DIR__') . $separate_controller_and_method[0]))
        {
             // przypisz do nazwy kontrolera i usuń pierwszy element z tablicy
            $controller_name = array_shift($separate_controller_and_method);
        }
        else // w przeciwnym wypadku przypisz nazwę do nazwy katalogu, a kolejną nazwę przypisz do nazwy kontrolera
        {
            $dir_name = array_shift($separate_controller_and_method) . '/'; // przypisz nazwę katalogu i usuń element
            $controller_name = array_shift($separate_controller_and_method); // przypisz nazwę kontrolera i usuń element
            
        }
        // jeśli znajdują się jeszcze jakieś parametry (metoda), to przypisz do zmiennej
        if (count($separate_controller_and_method) > 0) $action_type = array_shift($separate_controller_and_method);
        // zamień nazwę kontrolera z home-controller na homeController
        $controller_camel_case = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $controller_name))));
        $action_type = str_replace('-', '_', $action_type); // zamień wszystkie znaki '-' w kontrolerze na '_'
        return array(
            'directory' =>  $dir_name,
            'controller' => ucfirst($controller_camel_case) . Config::get('__MVC_CONTROLLER_SUFFIX__'),
            'method' =>     $action_type,
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda statyczna umożliwiająca instantancję aplikacji. Uruchomić można ją tylko raz (tylko raz dojdzie do stworzenia obiektu).
     * Jeśli obiekt będzie już istniał, pobierze referencję do niego z pamięci.
     */
    public static function run()
    {
        if (!isset(self::$_singleton_instance)) self::$_singleton_instance = new MvcApplication;
    }
}
