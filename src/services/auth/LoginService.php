<?php

namespace App\Services\Auth;

use App\Core\Config;
use App\Core\MvcProtector;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\ValidationHelper;
use Exception;
use PDO;

ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('ValidationHelper');

class LoginService extends MvcService
{
  protected function __construct()
  {
    parent::__construct();
  }

  /**
   * Metoda odpowiadający za pobieranie danych użytkownika i ich sprawdzanie z istniejącą bazą danych.
   * Jeśli użytkownik istnieje następuje przekierowanie do strony odpowiadającej rangą użytkownika.
   */
  public function login_user()
  {
    $login_email = '';
    $password = '';
    if (isset($_POST['form-login'])) {
      $login_email = ValidationHelper::validate_field_regex('login_email', Config::get('__REGEX_LOGIN_EMAIL__'));
      $password = ValidationHelper::validate_field_regex('pass', Config::get('__REGEX_PASSWORD__'));
      try {
        $query = "
          SELECT users.id AS id, is_activated, role_id, roles.name AS role_name, CONCAT(first_name,' ', last_name) AS full_name,
          password, photo_url, role_eng
          FROM users INNER JOIN roles ON users.role_id=roles.id
          WHERE login = :login OR email = :login
        ";
        $statement = $this->dbh->prepare($query);
        $statement->bindValue(':login', $login_email['value']);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
          throw new Exception('Konto z podanymi parametrami nie istnieje w systemie.');
        }
        if (!password_verify($password['value'], $result['password'])) {
          throw new Exception('Nieprawidłowy login i/lub hasło. Spróbuj ponownie.');
        }
        // sprawdzanie, czy użytkownik ma aktywowane konto, jeśli nie wyświetlenie linka do wysyłki wiadomości email z tokenem OTA
        if ($result['is_activated'] == 0) {
          $redirect_link = __URL_INIT_DIR__ . 'auth/account/activate/resend/code&userid=' . $result['id'];
          throw new Exception('
            Twoje konto nie zostało aktywowane. Aby aktywować konto, sprawdź swoją skrzynkę pocztową. W celu wysłania ponownie
            kodu aktywacyjnego <a class="alert-link" href="' . $redirect_link . '">kliknij tutaj</a>.
          ');
        }
        $statement->closeCursor();

        $_SESSION['logged_user'] = array(
          'user_id' => $result['id'],
          'is_normal_user' => $result['role_name'] == MvcProtector::USER,
          'user_role' => array(
            'role_id' => $result['role_id'],
            'role_name' => $result['role_name'],
            'role_eng' => $result['role_eng'],
          ),
          'user_full_name' => $result['full_name'],
          'user_profile_image' => $result['photo_url'] ?? 'static/images/default-profile-image.jpg',
        );
        if ($result['role_name'] == MvcProtector::ADMIN) {
          $redirect_link = 'admin/dashboard';
        } else if ($result['role_name'] == MvcProtector::OWNER) {
          $redirect_link = 'owner/dashboard';
        } else {
          $redirect_link = '';
        }
        header('Location:' . __URL_INIT_DIR__ . $redirect_link, true, 301); // jeśli wszystko się powiedzie, przejdź do strony
        die;
      } catch (Exception $e) {
        SessionHelper::create_session_banner(SessionHelper::LOGIN_PAGE_BANNER, $e->getMessage(), true);
      }
    }
    return array(
      'login_email' => $login_email,
      'password' => $password,
    );
  }
}
