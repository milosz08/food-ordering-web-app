<?php

namespace App\Models\Restaurant;

class RestaurantModel
{
  public $it; // indeks wiersza
  public $id; //id z bazy danych
  public $name; // nazwa restauracji
  public $address; // adres lokalu
  public $accept; //czy zatwierdzona przez administratora
  public $status; // status
  public $full_name; // pełna nazwa (imię i nazwisko właściciela restauracji)

  public function __construct()
  {
    $this->status = array(
      'text' => empty($this->accept) ? 'oczekująca' : 'aktywna',
      'color_bts' => empty($this->accept) ? 'text-danger' : 'text-success',
      'tooltip_text' => empty($this->accept)
        ? 'Zostało wysłane zgłoszenie do administratora systemu, po akceptacji zmieni status na "aktywna"'
        : 'Restauracja widoczna jest dla wszystkich użytkowników',
    );
  }
}
