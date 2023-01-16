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
 * Ostatnia modyfikacja: 2023-01-16 20:39:43                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Admin\Services;

use PDO;
use Exception;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\UserDetailsModel;
use App\Models\AdminUserDetailsModel;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\PaginationHelper;

ResourceLoader::load_model('UserDetailsModel', 'user');
ResourceLoader::load_model('AdminUserDetailsModel', 'user');
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
        $additional_comment = $_POST['delete-user-comment'] ?? 'brak komentarza';
        try
        {
            $this->dbh->beginTransaction();

            $query = "SELECT CONCAT(first_name, ' ', last_name) AS full_name, email FROM users WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));
            $result = $statement->fetch(PDO::FETCH_ASSOC);
            if (!$result) throw new Exception(
                'Podany użytkownik nie istnieje w systemie lub została wcześniej usunięty.
            ');

            $query = "DELETE FROM users WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));

            $email_request_vars = array(
                'user_full_name' => $$result['full_name'],
                'delete_reason' => $additional_comment,
            );
            $subject = 'Usunięcie użytkownika z ID #' . $_GET['id'];
            $this->smtp_client->send_message($result['email'], $subject, 'remove-account', $email_request_vars);

            rmdir('uploads/users/' . $_GET['id']);
            $this->_banner_message = 'Pomyślnie usunięto wybranego użytkownika z systemu.';
            $statement->closeCursor();
            if ($this->dbh->inTransaction()) $this->dbh->commit();
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
                SELECT ROW_NUMBER() OVER(ORDER BY id) AS it, u.id AS id, u.login AS login, u.email AS email,
                CONCAT(u.first_name, ' ', u.last_name) AS full_name, u.is_activated AS activated, r.name AS role,
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
            if ($this->dbh->inTransaction()) $this->dbh->commit();
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

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda zwracająca szczegóły wybranego użytkownika na podstawie parametrów GET. Jeśli nie znajdzie użytkownika przekierowanie do
     * strony ze wszystkimi użytkownikami.
     */
    public function get_users_details()
    {
        if (!isset($_GET['id'])) header('Location:' . __URL_INIT_DIR__ . 'admin/manage-users', true, 301);
        $user_details = new AdminUserDetailsModel;
        try
        {
            $this->dbh->beginTransaction();
            $query = "
                SELECT u.id, IFNULL(u.photo_url, 'static/images/default-profile.jpg') AS profile_url, u.login, u.email,
                CONCAT(u.first_name, ' ', u.last_name) AS full_name, CONCAT('ul. ', a.street, ' ', a.building_nr, IF(a.locale_nr IS 
                NOT NULL, (CONCAT('/', a.locale_nr)), ('')) , ', ', a.post_code, ' ', a.city) AS address, r.name AS role,
                u.is_activated AS activated, CONCAT('+48', phone_number) AS phone_number
                FROM ((users AS u
                INNER JOIN user_address AS a ON a.user_id = u.id AND a.is_prime = 1)
                INNER JOIN roles AS r ON u.role_id = r.id)
                WHERE u.id = ?
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));
            $user_details = $statement->fetchObject(AdminUserDetailsModel::class);
            if (!$user_details)
            {
                $this->dbh->rollback();
                $message = 'Użytkownik z podanym ID nie istnieje w systemie.';
                SessionHelper::create_session_banner(SessionHelper::ADMIN_MANAGED_USERS_PAGE_BANNER, $message, true);
                header('Location:' . __URL_INIT_DIR__ . 'admin/manage-users', true, 301);
                die;
            }
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::ADMIN_USER_DETAILS_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'user_details' => $user_details,
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda usuwająca zdjęcie profilowe użytkownika, jeśli takowe istnieje.
     */
    public function delete_user_profile_image()
    {
        if (!isset($_GET['id'])) return;
        $redir_path = 'admin/manage-users';
        $additional_comment = $_POST['delete-user-image-comment'] ?? 'brak komentarza';
        try
        {
            $this->dbh->beginTransaction();
            $query = "SELECT COUNT(*) FROM users WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));
            if ($statement->fetchColumn() == 0) throw new Exception('Wybrany użytkownik nie istnieje w systemie.');
            $redir_path .= '/user-details?id=' . $_GET['id'];

            $query = "SELECT email, photo_url, CONCAT(first_name, ' ', last_name) AS full_name FROM users WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));
            $data = $statement->fetch(PDO::FETCH_ASSOC);
            if (empty($data)) throw new Exception('Wybrany użytkownik nie posiada żadnego zdjęcia profilowego.');

            $query = "UPDATE users SET photo_url = NULL WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));

            $email_request_vars = array(
                'user_full_name' => $data['full_name'],
                'delete_reason' => $additional_comment,
            );
            $subject = 'Usunięcie potrawy z ID #' . $_GET['id'];
            $this->smtp_client->send_message($data['email'], $subject, 'remove-account-image', $email_request_vars);

            if (file_exists($data['photo_url'])) unlink($data['photo_url']);
            $this->_banner_message = 'Pomyślnie usunięto zdjęcie profilowe wybranego użytkownika z systemu.';
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $this->_banner_error = true;
            $this->_banner_message = $e->getMessage();
        }
        SessionHelper::create_session_banner(SessionHelper::ADMIN_USER_DETAILS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
        return $redir_path;
    }
}
