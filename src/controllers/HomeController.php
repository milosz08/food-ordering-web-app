<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: HomeController.php                             *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-11-10, 19:43:27                       *
 * Autor: Milosz08                                             *
 *                                                             *
 * Ostatnia modyfikacja: 2022-12-04 03:40:49                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Controllers;

use App\Core\MvcController;
use App\Services\HomeService;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Przykładowy kontroler aplikacji. Kontroler jest klasą rozszerzającą klasę abstrakcyjną MvcController. Ponadto każdy kontroler         *
 * musi zaczynać się od nazwy ControllerNazwa, gdzie Nazwa to pierwszy parametr ścieżki zapytania. Metody zadeklarowane w kontrolerze    *
 * odpowiadają drugim parametrom zapytania. Przykładowo, jeśli mamy kontroler:                                                           *
 *                                                                                                                                       *
 * class ExampleController                                                                                                               *
 * {                                                                                                                                     *
 *      public function show() { }                                                                                                       *
 * }                                                                                                                                     *
 *                                                                                                                                       *
 * (gdzie z jasnych powodów ograniczono implementację do minimum) klasa taka będzie reprezentowa na przez adres:                         *
 *      index.php?action=example/show                                                                                                    *
 *                                                                                                                                       *
 * gdzie example to część nazwy klasy kontrolera (bez nazwy Controller) a show to nazwa wywoływanej metody. Metoda taka musi kończyć się *
 * wywołaniem renderowania szablonu (patrz przykład niżej). W przypadku metody nazywającej się show_data(), np:                          *
 *                                                                                                                                       *
 * class ExampleController                                                                                                               *
 * {                                                                                                                                     *
 *      public function show_data() { }                                                                                                  *
 * }                                                                                                                                     *
 *                                                                                                                                       *
 * adres zostanie zmapowany na url oddzielony znakiem "/":                                                                               *
 *      index.php?action=example/show/data                                                                                               *
 *                                                                                                                                       *
 * Metod takich w kontrolerze może być więcej, ważne tylko aby nie było dwóch z taką samą nazwą. Rzecz jasna parametr mapowania adresu   *
 * przekazywany jest przez zapytanie GET, toteż można przesłać dodatkowe parametry GET w zapytaniu:                                      *
 *      index.php?action=example/show/data&id=123                                                                                        *
 *                                                                                                                                       *
 * Odwoływać się można do niego później jak do zwykłego parametru GET poprzez tablicę $_GET. Każdy kontroler rozszerzający klasę         *
 * abstrakcyjną MvcKontroler musi mieć zaimplementowaną metodę index(). Jest to metoda domyślna i wywoływana jeśli nie podano w ścieżce  *
 * drugiego parametru, czyli jeśli ścieżka jest np:                                                                                      *
 *      index.php?action=example                                                                                                         *
 *                                                                                                                                       *
 * Standardowo w takiej metodzie najlepiej zrobić przekierowanie 403 poprzez header(), lecz można również wywoływać metodę do            *
 * renderowania szablonu. Jeśli skrypt PHP nie znajdzie metody o nazwie zgodnej ze ścieżką zapytania wyświetli widok błędu               *
 * (strony 404) z informacją że nie znaleziono takiego zasobu.                                                                           *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class HomeController extends MvcController
{
    private $_service; // instancja serwisu

    //--------------------------------------------------------------------------------------------------------------------------------------

    public function __construct()
    {
        // Wywołanie konstruktora z klasy MvcController. Każda klasa kontrolera musi wywoływać konstruktor klasy nadrzędniej!
        parent::__construct();
        $this->_service = HomeService::get_instance(HomeService::class); // pobranie instancji klasy HomeService
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiająca się w przypadku przejścia na adres index.php?action=home/welcome. Metoda na końcu wyświetla szablon z
     * przekazanymi danymi przy pomocy instancji klasy MvcRenderer otrzymywanej z klasy nadrzędnej MvcController.
     */
    public function welcome()
    {
        $this->renderer->render('home/home-view', array(
            'page_title' => 'Start',
        ));
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda uruchamiana, kiedy użytkownik w ścieżce zapytania poda jedynie nazwę kontrolera, czyli jak ścieżka jest mniej więcej taka:
     *      index.php?action=home
     * Metoda przekierowuje użytkownika na adres:
     *      index.php?action=home/welcome
     * renderując widok z metody welcode() powyższej klasy.
     */
    public function index()
    {
        header('Location:index.php?action=home/welcome');
    }
}
