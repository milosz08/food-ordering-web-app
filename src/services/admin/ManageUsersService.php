<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: ManageUsersService.php                         *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-14, 22:06:12                       *
 * Autor: patrick012016                                        *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-15 13:04:54                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Admin\Services;

use PDO;
use Exception;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\UserDetailsModel;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\PaginationHelper;

ResourceLoader::load_model('UserDetailsModel', 'user');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('PaginationHelper');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class ManageUsersService extends MvcService
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
     * Metoda odpowiadająca za usunięcie wybranego przez admnistratora użytkownika.
     */
    public function delete_user()
    {
        if (!isset($_GET['id'])) return;
        try
        {
            $this->dbh->beginTransaction();

            $query = "DELETE FROM users WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));

            // wysyłanie wiadomości email do użytkownika z informacją o usunięciu jego konta z serwisu

            $this->_banner_message = 'Pomyślnie usunięto wybranego użytkownika z systemu.';
            $statement->closeCursor();
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $this->_banner_message = $e->getMessage();
            $this->_banner_error = true;
        }
        SessionHelper::create_session_banner(SessionHelper::ADMIN_MANAGED_USERS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda odpowiadająca za usunięcie wybranego przez admnistratora użytkownika.
     */
    public function get_users()
    {
        $pagination = array(); // tablica przechowująca liczby przekazywane do dynamicznego tworzenia elementów paginacji
        $users_list = array(); 
        $pages_nav = array();
        try
        {
            $this->dbh->beginTransaction();

            $curr_page = $_GET['page'] ?? 1; // pobranie indeksu paginacji
            $page = ($curr_page - 1) * 10;
            $total_per_page = $_GET['total'] ?? 10;
            $search_text = SessionHelper::persist_search_text('search-user-name', SessionHelper::ADMIN_MANAGED_USERS_SEARCH);
            
            $redirect_url = 'admin/manage-users';
            PaginationHelper::check_parameters($redirect_url);

            // zapytanie do bazy danych, które zwróci poszczególne wartości wszystkich restauracji dla obecnie zalogowanego użytkownika
            $query = "
                SELECT ROW_NUMBER() OVER(ORDER BY id) AS it, u.id AS id, u.first_name AS first_name, u.last_name AS last_name, 
                u.login AS login, u.email AS email, u.is_activated AS activated, r.name AS role,
                CONCAT('ul. ', ua.street, ' ', ua.building_nr, IF(ua.locale_nr IS NOT NULL, (CONCAT('/',ua.locale_nr)), ('')) , ', ', 
                ua.post_code, ' ', ua.city) AS address FROM ((users AS u
                INNER JOIN user_address AS ua ON u.id = ua.user_id) 
                INNER JOIN roles AS r ON u.role_id = r.id)
                WHERE ua.is_prime = 1 AND u.role_id <> 3 AND (u.login LIKE :search OR u.email LIKE :search) LIMIT :total OFFSET :page
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('search', '%' . $search_text . '%');
            $statement->bindValue('total', $total_per_page, PDO::PARAM_INT);
            $statement->bindValue('page', $page, PDO::PARAM_INT);
            $statement->execute();
            while ($row = $statement->fetchObject(UserDetailsModel::class)) array_push($users_list, $row);
            
            // zapytanie zliczające wszystkich użytkowników widniejących w bazie danych
            $query = "SELECT count(*) FROM users WHERE role_id <> 3 AND (login LIKE :search OR email LIKE :search)";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('search', '%' . $search_text . '%');
            $statement->execute();
            $total_records = $statement->fetchColumn();

            $total_pages = ceil($total_records / $total_per_page);
            for ($i = 1; $i <= $total_pages; $i++) array_push($pagination, array(
                'it' => $i,
                'url' => $redirect_url . '?page=' . $i . '&total=' . $total_per_page, 
                'selected' => $curr_page ==  $i ? 'active' : '',
            ));

            PaginationHelper::check_if_page_is_greaten_than($redirect_url, $total_pages);
            $pages_nav = PaginationHelper::get_pagination_nav($curr_page, $total_per_page, $total_pages, $total_records, $redirect_url);
            $statement->closeCursor();
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::ADMIN_MANAGED_USERS_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'total_per_page' => $total_per_page,
            'pagination_url' => $redirect_url . '?',
            'pagination' => $pagination,
            'pages_nav' => $pages_nav,
            'users_list' => $users_list,
            'search_text' => $search_text,
            'not_empty' => count($users_list),
        );
    }
}
