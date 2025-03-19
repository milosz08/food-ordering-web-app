<?php

namespace App\Services;

use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\User\ProfileModel;
use App\Services\Helpers\SessionHelper;
use Exception;

ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_model('ProfileModel', 'User');

class ProfileService extends MvcService
{
  protected function __construct()
  {
    parent::__construct();
  }

  /**
   * Metoda odpowiadająca za wyświetlanie danych o użytkowniku w profilu.
   */
  public function profile(): array
  {
    $profile = new ProfileModel;
    try {
      $this->dbh->beginTransaction();

      // zapytanie do bazy danych, które zwróci informacje kontaktowe
      $query = "
        SELECT u.first_name, u.last_name, u.login, u.email,
        CONCAT(SUBSTRING(phone_number, 1, 3), ' ', SUBSTRING(phone_number, 3, 3), ' ', SUBSTRING(phone_number, 6, 3)) AS phone_number,
        IFNULL(u.photo_url, 'static/images/default-profile-image.jpg') AS photo_url,
        CONCAT('ul. ', ua.street, ' ', ua.building_nr, IF(ua.locale_nr IS NOT NULL, (CONCAT('/',ua.locale_nr)), ('')) , ', ',
        ua.post_code, ' ', ua.city) AS address FROM users u INNER JOIN user_addresses ua ON u.id = ua.user_id WHERE u.id = :id
      ";
      $statement = $this->dbh->prepare($query);
      $statement->bindValue('id', $_SESSION['logged_user']['user_id']);
      $statement->execute();

      $profile = $statement->fetchObject(ProfileModel::class);

      $statement->closeCursor();
      if ($this->dbh->inTransaction()) {
        $this->dbh->commit();
      }
    } catch (Exception $e) {
      $this->dbh->rollback();
      SessionHelper::create_session_banner(SessionHelper::OWNER_PROFILE_PAGE_BANNER, $e->getMessage(), true);
    }
    return array(
      'profile' => $profile,
    );
  }
}
