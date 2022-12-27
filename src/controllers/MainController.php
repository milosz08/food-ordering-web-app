<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: MainController.php                             *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-12-27, 18:07:47                       *
 * Autor: BubbleWaffle                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2022-12-27 18:15:13                   *
 * Modyfikowany przez: BubbleWaffle                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

 namespace App\Controllers;

 use App\Utils\Utils;
 use App\Core\MvcController;
 use App\Services\MainService;
 
 class MainController extends MvcController
 {
     private $_service;
 
     //--------------------------------------------------------------------------------------------------------------------------------------
 
     public function __construct()
     {
         // Wywołanie konstruktora z klasy MvcController. Każda klasa kontrolera musi wywoływać konstruktor klasy nadrzędniej!
         parent::__construct();
         $this->_service = MainService::get_instance(MainService::class); // pobranie instancji klasy AdminService
     }
     
     //--------------------------------------------------------------------------------------------------------------------------------------
 
     /**
      * Metoda uruchamiana, kiedy użytkownik w ścieżce zapytania poda jedynie nazwę kontrolera, czyli jak ścieżka jest mniej więcej taka:
      *      admin
      * Metoda przekierowuje użytkownika na adres:
      *      admin/panel/dashbaord
      * renderując widok z metody panel_dashboard() powyższej klasy.
      */
     public function index()
     {
         header('Location:' . __URL_INIT_DIR__ . 'main/panel/restaurants/list');
     }
 }
