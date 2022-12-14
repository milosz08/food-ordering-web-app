<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: AdminService.php                               *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-12-06, 15:20:33                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2022-12-14 20:22:49                   *
 * Modyfikowany przez: BubbleWaffle                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services;

use App\Core\MvcService;

class AdminService extends MvcService
{
    private $_banner_message = '';
    private $_show_banner = false;
    private $_banner_error = false;

    //--------------------------------------------------------------------------------------------------------------------------------------

    protected function __construct()
    {
        parent::__construct();
    }

}
