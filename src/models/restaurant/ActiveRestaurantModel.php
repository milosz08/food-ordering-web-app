<?php

namespace App\Models\Restaurant;

class ActiveRestaurantModel
{
  public $it; // liczba porządkowa restauracji
  public $id; // id restauracji z bazy danych
  public $name; // nazwa restauracji
  public $address; // adres restauracji
  public $count_of_dishes; // liczba potraw przypisanych do restauracji
}
