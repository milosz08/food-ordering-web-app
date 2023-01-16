<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: PendingRestaurantsService.php                  *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 22:51:02                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-16 04:33:44                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Admin\Services;

use PDO;
use Exception;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\PendingRestaurantModel;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\PaginationHelper;

ResourceLoader::load_model('PendingRestaurantModel', 'restaurant');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('PaginationHelper');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class PendingRestaurantsService extends MvcService
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
     * Metoda odpowiadająca za wyświetlanie panelu wraz z listą restauracji do zaakceptowania.
     */
    public function get_pending_restaurants()
    {
        $pending_restaurants = array();
        $pagination = array();
        $pages_nav = array();
        try
        {
            $this->dbh->beginTransaction();

            $curr_page = $_GET['page'] ?? 1; // pobranie indeksu paginacji
            $page = ($curr_page - 1) * 10;
            $total_per_page = $_GET['total'] ?? 10;
            $search_text = SessionHelper::persist_search_text('search-res-name', SessionHelper::ADMIN_PENDING_RES_SEARCH);

            $redirect_url = 'admin/pending-restaurants';
            PaginationHelper::check_parameters($redirect_url);

            // zapytanie do bazy danych, które zwróci poszczególne wartości wszystkich restauracji dla obecnie zalogowanego użytkownika
            $query = "
                SELECT ROW_NUMBER() OVER(ORDER BY id) as it, r.id, CONCAT(first_name, ' ', last_name) AS full_name, name, 
                CONCAT(street,' ', building_locale_nr, ' ',  post_code, ' ', city) AS address
                FROM restaurants r
                INNER JOIN users u ON r.user_id = u.id
                WHERE name LIKE :search AND accept = 0 LIMIT :total OFFSET :page
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('search', '%' . $search_text . '%');
            $statement->bindValue('total', $total_per_page, PDO::PARAM_INT);
            $statement->bindValue('page', $page, PDO::PARAM_INT);
            $statement->execute();

            while ($row = $statement->fetchObject(PendingRestaurantModel::class)) array_push($pending_restaurants, $row);

            // zapytanie zliczające wszystkie restauracje posiadające status niezaakceptowany do użytkownika
            $query = "SELECT count(*) FROM restaurants WHERE accept = 0 AND name LIKE :search";
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

            $statement->closeCursor();
            PaginationHelper::check_if_page_is_greaten_than($redirect_url, $total_pages);
            $pages_nav = PaginationHelper::get_pagination_nav($curr_page, $total_per_page, $total_pages, $total_records, $redirect_url);
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::ADMIN_PENDING_RESTAURANTS_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'total_per_page' => $total_per_page,
            'pagination_url' => $redirect_url . '?',
            'pagination' => $pagination,
            'pages_nav' => $pages_nav,
            'pending_restaurants' => $pending_restaurants,
            'search_text' => $search_text,
            'not_empty' => count($pending_restaurants),
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda odpowiadająca za akceptację wybranej restauracji z tabeli.
     */
    public function accept_restaurant()
    {
        if (!isset($_GET['id'])) header('Location:' . __URL_INIT_DIR__ . 'admin/pending-restaurants', true, 301);
        try
        {
            $this->dbh->beginTransaction();

            $query = "UPDATE restaurants SET accept = 1 WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));

            // wysłanie wiadomości email do właściciela restauracji

            $this->_banner_message = 'Pomyślnie zaakceptowano wybraną restaurację oraz wysłano wiadomość email do właściciela.';
            $statement->closeCursor();
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $this->_banner_message = $e->getMessage();
            $this->_banner_error = true;
        }
        SessionHelper::create_session_banner(SessionHelper::ADMIN_PENDING_RESTAURANTS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda odpowiadająca za odrzucanie a tym samym usuwanie danej restauracji.
     */
    public function reject_restaurant()
    {
        if (!isset($_GET['id'])) header('Location:' . __URL_INIT_DIR__ . 'admin/panel/restaurant/accept', true, 301);
        $additional_comment = $_POST['reject-restaurant-comment'] ?? 'brak komentarza';
        try
        {
            $this->dbh->beginTransaction();

            $query = "SELECT COUNT(*) FROM restaurants WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));

            if ($statement->fetchColumn() == 0) throw new Exception('Podana resturacja nie istnieje w systemie lub została już odrzucona.');

            $query = "DELETE FROM restaurants WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));

            // wysłanie wiadomości email do właściciela restauracji

            $this->_banner_message = 'Pomyślnie odrzucono wybraną restaurację z systemu oraz wysłano wiadomość email do właściciela.';
            $statement->closeCursor();
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $this->_banner_message = $e->getMessage();
            $this->_banner_error = true;
        }
        SessionHelper::create_session_banner(SessionHelper::ADMIN_PENDING_RESTAURANTS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
    }
}
