<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: HomeService.php                                *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-11-10, 19:43:43                       *
 * Autor: Milosz08                                             *
 *                                                             *
 * Ostatnia modyfikacja: 2022-12-04 03:28:26                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services;

use App\Core\MvcService;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Przykładowy serwis. Serwis jest to klasa dostarczająca metody zapewniające logikę biznesową akcji w kontrolerach (dodawanie do        *
 * bazy danych, usuwanie, sortowanie itp.). Logiki takiej nie powinno umieszczać się w kontrolerach. Każda klasa serwisu musi            *
 * dziedziczyć po klasie abstrakcyjnej MvcService z przestrzeni nazw App\Core\. Wraz z tą klasą abstrakcyjną dostarczany jest obiekt     *
 * PDO do komunikacji z bazą danych. Każdy serwis jest klasą wg wzorca singleton. Odwołanie do instancji tej klasy można uzystać poprzez *
 * statyczną metodę get_instance() zadeklarowanej w abstrakcyjnej klasie bazowej MvcService. Próba stworzenia tej klasy poprzez          *
 * konstruktor skończy się wyrzuceniem wyjątku.                                                                                          *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class HomeService extends MvcService
{
    protected function __construct()
    {
        parent::__construct();
    }
}
