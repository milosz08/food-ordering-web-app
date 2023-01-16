<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: ProfileService.php                             *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 22:31:52                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-16 19:45:57                   *
 * Modyfikowany przez: patrick012016                           *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Admin\Services;

use PDO;
use Exception;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\OwnerProfileModel;
use App\Services\Helpers\SessionHelper;


ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_model('OwnerProfileModel', 'restaurant');

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

    /**
     * Metoda odpowiadająca za wyświetlanie danych o użytkowniku w profilu.
     */
    public function profile()
    {
        $admin_profile = new OwnerProfileModel;
        try
        {
            $this->dbh->beginTransaction();

            // zapytanie do bazy danych, które zwróci informacje kontaktowe administratora
            $query = "
                SELECT u.first_name, u.last_name, u.login, u.email, u.phone_number, IFNULL(u.photo_url, 'static/images/default-profile-image.jpg') AS photo_url,
                CONCAT('ul. ', ua.street, ' ', ua.building_nr, IF(ua.locale_nr IS NOT NULL, (CONCAT('/',ua.locale_nr)), ('')) , ', ', 
                ua.post_code, ' ', ua.city) AS address FROM users u INNER JOIN user_address ua ON u.id = ua.user_id WHERE u.id = :id 
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('id', $_SESSION['logged_user']['user_id']);
            $statement->execute();

            $admin_profile = $statement->fetchObject(OwnerProfileModel::class);

            $statement->closeCursor();
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::ADMIN_PROFILE_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'profile' => $admin_profile,
        );
    }
}
