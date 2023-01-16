<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: ProfileService.php                             *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 22:32:11                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-16 14:38:49                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Owner\Services;

use App\Core\MvcService;
use App\Core\ResourceLoader;

ResourceLoader::load_service_helper('SessionHelper');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class ProfileService extends MvcService
{
    private $_banner_message = '';
    private $_banner_error = false;

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    protected function __construct()
    {
        parent::__construct();
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
