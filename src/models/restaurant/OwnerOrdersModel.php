<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: OwnerOrdersModel.php                           *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-16, 00:10:06                       *
 * Autor: BubbleWaffle                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:48:35                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Models;

class OwnerOrdersModel
{
    public $it;
    public $id;
    public $user;
    public $discount;
    public $status;
    public $order_adress;
    public $delivery_type;
    public $restaurant;
    public $price;
    public $button_status;
}
