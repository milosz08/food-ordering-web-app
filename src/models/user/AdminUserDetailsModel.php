<?php

namespace App\Models\User;

class AdminUserDetailsModel
{
  public $id; // id użytkownika
  public $profile_url; // ścieżka do zdjęcia profilowego użytkownika
  public $login; // login użytkownika
  public $full_name; // imię i nazwisko
  public $email; // adres email użytkownika
  public $address; // adres użytkownika
  public $status; // status konta użytkownika
  public $role; // rola użytkownika
  public $activated; // czy konto zostało aktywowane
  public $phone_number; // numer telefonu do użytkownika

  public function __construct()
  {
    $this->status = array(
      'text' => empty($this->activated) ? 'dezaktywowane' : 'aktywne',
      'color_bts' => empty($this->activated) ? 'text-danger' : 'text-success',
      'tooltip_text' => empty($this->activated)
        ? 'Na założone konto użytkownika został wysłany link z aktywacją, po akceptacji konto zmieni status na "aktywne"'
        : 'Konto użytkownika zostało aktywowane',
    );
  }
}
