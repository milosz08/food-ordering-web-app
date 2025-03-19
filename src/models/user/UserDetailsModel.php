<?php

namespace App\Models\User;

class UserDetailsModel
{
  public $it;
  public $id;
  public $first_name;
  public $last_name;
  public $login;
  public $email;
  public $role;
  public $activated;
  public $address;
  public $status;
  public $full_name;

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
