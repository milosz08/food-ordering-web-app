<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: ShowUserOrdersListModel.php                    *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-10, 16:45:24                       *
 * Autor: BubbleWaffle                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-16 08:38:15                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class ShowUserOrdersListModel
{
    public $name;
    public $price;
    public $id;
    public $order_status;
    public $order_status_color;
    public $estimate_time;
    public $profile_url;
}
