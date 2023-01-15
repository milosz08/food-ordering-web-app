<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
* Copyright (c) 2023 by multiple authors                      *
* Politechnika Śląska | Silesian University of Technology     *
*                                                             *
* Nazwa pliku: DeleteRestaurantsService.php                   *
* Projekt: restaurant-project-php-si                          *
* Data utworzenia: 2023-01-12, 17:39:46                       *
* Autor: BubbleWaffle                                         *
*                                                             *
* Ostatnia modyfikacja: 2023-01-12 18:53:28                   *
* Modyfikowany przez: BubbleWaffle                            *
* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Admin\Services;

use PDO;
use Exception;
use App\Core\Config;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\RestaurantModel;
use App\Models\RestaurantHourModel;
use App\Models\RestaurantAdminModel;
use App\Services\Helpers\ImagesHelper;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\PaginationHelper;
use App\Services\Helpers\ValidationHelper;
use App\Services\Helpers\RestaurantsHelper;

ResourceLoader::load_model('RestaurantModel', 'restaurant');
ResourceLoader::load_model('RestaurantHourModel', 'restaurant');
ResourceLoader::load_model('RestaurantAdminModel', 'restaurant');

ResourceLoader::load_service_helper('ImagesHelper');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('PaginationHelper');
ResourceLoader::load_service_helper('ValidationHelper');
ResourceLoader::load_service_helper('RestaurantsHelper');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class DeleteRestaurantsService extends MvcService
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
     * Metoda odpowiadająca za tworzenie tabeli w zakładce 'Restauracje do usunięcia'.
     * Tabela przechowuje kolejno wszystkie restauracje z bazy danych.
     * Tabela została wzbogacona o funkcję paginacji, wyświetlającej tylko 5 elementów na jednej ze stron.
     */
    public function get_restaurants()
    {
        $pagination = array(); // tablica przechowująca liczby przekazywane do dynamicznego tworzenia elementów paginacji
        $restaurants = array();
        $pages_nav = array();
        $pagination_visible = true; // widoczność paginacji
        try {
            $this->dbh->beginTransaction();

            $curr_page = $_GET['page'] ?? 1; // pobranie indeksu paginacji
            $page = ($curr_page - 1) * 5;
            $total_per_page = $_GET['total'] ?? 5;
            $search_text = SessionHelper::persist_search_text('search-res-name', SessionHelper::ADMIN_RESTAURANTS_SEARCH);

            $redirect_url = 'admin/delete-restaurants';
            PaginationHelper::check_parameters('admin/delete-restaurants');

            // zapytanie do bazy danych, które zwróci poszczególne wartości wszystkich restauracji z bazy
            $query = "
                SELECT ROW_NUMBER() OVER(ORDER BY id) as it, name, accept, id,
                CONCAT('ul. ', street, ' ', building_locale_nr, ', ', post_code, ' ', city) AS address
                FROM restaurants WHERE name LIKE :search LIMIT :total OFFSET :page
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('search', '%' . $search_text . '%');
            $statement->bindValue('total', $total_per_page, PDO::PARAM_INT);
            $statement->bindValue('page', $page, PDO::PARAM_INT);
            $statement->execute();

            while ($row = $statement->fetchObject(RestaurantModel::class))
                array_push($restaurants, $row);

            // zapytanie zliczające wszystkie restauracje z bazy
            $query = "SELECT count(*) FROM restaurants WHERE name LIKE :search";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('search', '%' . $search_text . '%');
            $statement->execute();
            $total_records = $statement->fetchColumn();

            $total_pages = ceil($total_records / $total_per_page);
            for ($i = 1; $i <= $total_pages; $i++)
                array_push($pagination, array(
                    'it' => $i,
                    'url' => $redirect_url . '?page=' . $i . '&total=' . $total_per_page,
                    'selected' => $curr_page == $i ? 'active' : '',
                )
                );

            $statement->closeCursor();
            PaginationHelper::check_if_page_is_greaten_than($redirect_url, $total_pages);
            $pages_nav = PaginationHelper::get_pagination_nav($curr_page, $total_per_page, $total_pages, $total_records, $redirect_url);
            $this->dbh->commit();
        } catch (Exception $e) {
            $this->dbh->rollback();
            $pagination_visible = false;
            SessionHelper::create_session_banner(SessionHelper::ADMIN_DELETE_RESTAURANT_BANNER, $e->getMessage(), true);
        }
        return array(
            'total_per_page' => $total_per_page,
            'pagination_url' => 'admin/delete-restaurants?',
            'pagination' => $pagination,
            'pagination_visible' => $pagination_visible,
            'pages_nav' => $pages_nav,
            'user_restaurants' => $restaurants,
            'search_text' => $search_text,
            'not_empty' => count($restaurants),
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda odpowiadająca za pobranie szczegółów dań wybranej restauracji z bazy danych i zwrócenie ich do widoku.
     */
    public function get_details()
    {
        if (!isset($_GET['id']))
            header('Location:' . __URL_INIT_DIR__ . 'admin/delete-restaurants', true, 301);

        $restaurant_details = new RestaurantAdminModel;
        $res_hours = array();

        try {
            $this->dbh->beginTransaction();

            //$redirect_url = 'admin/delete-restaurants/details?id=' . $_GET['id'];

            $restaurant_query = "
                SELECT r.id, name, accept, description, building_locale_nr, street, post_code, city, r.profile_url, r.banner_url,
                CONCAT(first_name, ' ', last_name) AS full_name,
                IF(delivery_price, CONCAT(REPLACE(CAST(delivery_price AS DECIMAL(10,2)), '.', ','), ' zł'), 'za darmo') AS delivery_price, 
                CONCAT('ul. ', street, ' ', building_locale_nr, ', ', post_code, ' ', city) AS address,
                (SELECT COUNT(*) FROM dishes WHERE restaurant_id = r.id) AS count_of_dishes,
                CONCAT(SUBSTRING(phone_number, 1, 3), ' ', SUBSTRING(phone_number, 3, 3), ' ', SUBSTRING(phone_number, 6, 3)) AS phone_number,
                IF(min_price, CONCAT(REPLACE(CAST(min_price AS DECIMAL(10,2)), '.', ','), ' zł'), 'brak najniższej ceny') AS min_price,
                IFNULL(NULLIF((SELECT COUNT(*) FROM discounts WHERE restaurant_id = r.id), 0), 'brak rabatów') AS discounts_count
                FROM restaurants AS r
                INNER JOIN users AS u ON r.user_id = u.id
                WHERE r.id = :id
            ";
            $statement = $this->dbh->prepare($restaurant_query);
            $statement->bindValue('id', $_GET['id'], PDO::PARAM_INT);
            $statement->execute();
            $restaurant_details = $statement->fetchObject(RestaurantAdminModel::class);
            if (!$restaurant_details)
            {
                $this->_banner_message = 'Wybrana restauracja nie istnieje.';
                SessionHelper::create_session_banner(SessionHelper::ADMIN_DELETE_RESTAURANT_BANNER, $this->_banner_message, true);
                $statement->closeCursor();
                $this->dbh->commit();
                header('Location:' . __URL_INIT_DIR__ . 'admin/delete-restaurants');
            }

            $raw_res_hours = $this->get_restaurant_weekdays_and_hours();
            foreach ($raw_res_hours as $raw_res_hour)
                array_push($res_hours, $raw_res_hour->format_to_details_view());

            $this->dbh->commit();
        } catch (Exception $e) {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::RESTAURANT_DETAILS_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'details' => $restaurant_details,
            'res_hours' => $res_hours,
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda pobierająca godziny i dni tygodnia w jakich pracuje restauracja i zwraca tablicę obiektów.
     */
    private function get_restaurant_weekdays_and_hours()
    {
        $ret_hours = array();
        // pobieranie danych na podstawie wszystkich dni tygodnia, kiedy restauracja jest czynna (zapytania złożone i podzapytania)
        $hours_query = "
            SELECT w.alias AS alias, w.name AS name, w.name_eng AS identifier,
            IFNULL((SELECT DATE_FORMAT(open_hour, '%H:%i') FROM restaurant_hours WHERE restaurant_id = :resid AND weekday_id = w.id),
            'nieczynne') AS open_hour,
            IFNULL((SELECT DATE_FORMAT(close_hour, '%H:%i') FROM restaurant_hours WHERE restaurant_id = :resid AND weekday_id = w.id),
            'nieczynne') AS close_hour,
            (SELECT NOT COUNT(*) > 0 FROM restaurant_hours WHERE restaurant_id = :resid AND weekday_id = w.id) AS is_closed
            FROM weekdays AS w
            ORDER BY w.id
        ";
        $statement = $this->dbh->prepare($hours_query);
        $statement->bindValue('resid', $_GET['id']);
        $statement->execute();
        while ($row = $statement->fetchObject(RestaurantHourModel::class)) array_push($ret_hours, $row);
        return $ret_hours;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda odpowiadająca za usunięcie wybranej przez admnistratora restauracji.
     */
    public function delete_restaurant()
    {
        if (!isset($_GET['id']))
            return;
        try {
            $this->dbh->beginTransaction();

            $query = "SELECT COUNT(*) FROM restaurants WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));
            $result = $statement->fetchColumn();
            if (empty($result))
                throw new Exception(
                    'Podana resturacja nie istnieje w systemie lub została wcześniej usunięta.'
                );

            $query = "DELETE FROM restaurants WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));

            // wysyłanie wiadomości email do restauratora usuniętej restauracji

            rmdir('uploads/restaurants/' . $_GET['id']);
            $this->_banner_message = 'Pomyślnie usunięto wybraną restaurację z systemu.';
            $statement->closeCursor();
            $this->dbh->commit();
        } catch (Exception $e) {
            $this->dbh->rollback();
            $this->_banner_message = $e->getMessage();
            $this->_banner_error = true;
        }
        SessionHelper::create_session_banner(SessionHelper::ADMIN_DELETE_RESTAURANT_BANNER, $this->_banner_message, $this->_banner_error);
    }
}
