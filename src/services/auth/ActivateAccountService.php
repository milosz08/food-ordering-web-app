<?php

namespace App\Services\Auth;

use App\Core\Config;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Helpers\AuthHelper;
use App\Services\Helpers\SessionHelper;
use Exception;
use PDO;

ResourceLoader::load_service_helper('AuthHelper');
ResourceLoader::load_service_helper('SessionHelper');

class ActivateAccountService extends MvcService
{
  private $_banner_message = '';
  private $_banner_error = false;

  protected function __construct()
  {
    parent::__construct();
  }

  /**
   * Metoda odpowiedzialna za wysłanie żądania aktywowania konta na podstawie tokenu OTA w parametrze GET. Jeśli token istnieje, nie
   * został wykorzystany i nie został przedawniony, zmieniany jest jego status na wykorzystany oraz konto użytkownika zostaje aktywowane.
   * Od tej pory użytkownik może logować się do systemu.
   */
  public function attempt_activate_account()
  {
    try {
      $this->dbh->beginTransaction();

      if (!isset($_GET['code'])) {
        throw new Exception('W celu ukończenia aktywacji konta należy podać kod autoryzacyjny.');
      }
      if (!preg_match(Config::get('__REGEX_OTA__'), $_GET['code'])) {
        throw new Exception('Podany kod nie jest prawidłowym kodem autoryzacyjnym.');
      }
      // zapytanie złożone sprawdzające token czy istnieje, czy nie jest przedawniony, czy nie został już użyty oraz czy konto
      // użytkownika powiązane z tym token nie zostało już zaktywowane
      $query = "
        SELECT user_id FROM ota_user_tokens AS ota
        INNER JOIN users AS us ON ota.user_id = us.id
        WHERE ota_token = ? AND expiration_date >= NOW() AND is_used = false AND is_activated = 0
        AND type_id = (SELECT id FROM ota_token_types WHERE type='activate account' LIMIT 1)
      ";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['code']));

      $user_id = $statement->fetchColumn();
      if (empty($user_id)) {
        throw new Exception('Podany kod autoryzacyjny wygasł lub Twoje konto zostało już aktywowane.');
      }
      $query = "UPDATE ota_user_tokens SET is_used = true WHERE ota_token = ?";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['code']));

      $query = "UPDATE users SET is_activated = 1 WHERE id = ?";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($user_id));
      $this->_banner_message = '
        Twoje konto zostało aktywowane. Wpisz poniżej dane w formularzu, aby zalogować się na nowo utworzone konto.
      ';
      $this->_banner_error = false;

      $statement->closeCursor();
      if ($this->dbh->inTransaction()) {
        $this->dbh->commit();
      }
    } catch (Exception $e) {
      $this->_banner_message = $e->getMessage();
      $this->_banner_error = true;
      $this->dbh->rollback();
    }
    SessionHelper::create_session_banner(SessionHelper::LOGIN_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
  }

  /**
   * Metoda odpowiedzialna za ponowne wysłanie wiadomości email do użytkownika z tokenem. Jeśli token nie wygasł i nie został użyty, jest
   * on wykorzystywany. W przeciwnym wypadku generowany jest nowy token, zapisywany w bazie i wysyłany w wiadomości email w postaci linku.
   * Użytkownik klikając w link zostanie przeniesiony do strony weryfikującej poprawność tokenu i aktywującej konto.
   */
  public function resend_account_activation_link()
  {
    try {
      $this->dbh->beginTransaction();
      if (!isset($_GET['userid'])) {
        throw new Exception('W celu ponownego wysłania tokenu należy podać identyfikator użytkownika.');
      }
      $query = "
        SELECT user_id AS id, ota_token, email, CONCAT(first_name, ' ', last_name) AS full_name FROM ota_user_tokens AS ota
        INNER JOIN users AS us ON ota.user_id = us.id
        WHERE user_id = ? AND expiration_date >= NOW() AND is_used = false
        AND type_id = (SELECT id FROM ota_token_types WHERE type = 'activate account' LIMIT 1)
      ";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['userid']));

      $user_data = $statement->fetchAll(PDO::FETCH_ASSOC);
      if (count($user_data) == 0) {
        $query = "
          INSERT INTO ota_user_tokens (ota_token, expiration_date, user_id, type_id)
          VALUES (?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), ?,
          (SELECT id FROM ota_token_types WHERE type = 'activate account' LIMIT 1))
        ";
        $statement = $this->dbh->prepare($query);
        $rnd_ota_token = AuthHelper::generate_random_seq();
        $statement->execute(array($rnd_ota_token, $_GET['userid']));
        $ota_token = $rnd_ota_token;
      } else {
        $user_data = $user_data[0];
        $ota_token = $user_data['ota_token'];
      }
      $email_request_vars = array(
        'user_full_name' => $user_data['full_name'],
        'basic_server_path' => Config::get('__DEF_APP_HOST__'),
        'ota_token' => $ota_token,
        'regenerate_link' => 'auth/activate-account/resend?userid=' . $user_data['id'],
      );
      $subject = 'Aktywacja konta dla użytkownika ' . $user_data['full_name'];
      $this->smtp_client->send_message($user_data['email'], $subject, 'activate-account', $email_request_vars);

      $this->_banner_message = 'Na adres email skojarzony z kontem ' . $user_data['full_name'] . ' został wysłany kod autoryzacyjny.';
      $statement->closeCursor();
      if ($this->dbh->inTransaction()) {
        $this->dbh->commit();
      }
    } catch (Exception $e) {
      $this->_banner_message = $e->getMessage();
      $this->_banner_error = true;
      $this->dbh->rollback();
    }
    SessionHelper::create_session_banner(SessionHelper::LOGIN_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
  }
}
