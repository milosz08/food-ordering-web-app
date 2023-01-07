<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: RestaurantsService.php                         *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 21:42:48                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-07 02:22:20                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services;

use PDO;
use Exception;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\ListRestaurantModel;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\PaginationHelper;

ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('PaginationHelper');
ResourceLoader::load_model('ListRestaurantModel', 'restaurant');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class RestaurantsService extends MvcService
{
    private $_banner_message = '';
    private $_banner_error = false;
    
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    protected function __construct()
    {
        parent::__construct();
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function get_all_accepted_restaurants()
    {
        $pagination = array();
        $res_list = array();
        $pages_nav = array();
        $with_search = '?';
        $total_records = 0;
        $pagination_visible = true;
        try
        {
            $this->dbh->beginTransaction();
            $curr_page = $_GET['page'] ?? 1; // pobranie indeksu paginacji
            $page = ($curr_page - 1) * 5;
            $total_per_page = $_GET['total'] ?? 5;
            $search_text = $_GET['search'] ?? '';    // wyszukiwanie po frazie nazwy restauracji
            
            $with_search = empty($search_text) ? '?' : '?search=' . $search_text . '&';
            $redirect_url = $with_search == '?' ? 'restaurants' : 'restaurants' . $with_search;
            PaginationHelper::check_parameters($redirect_url);

            // dorobienie filtrowania wyników po pozostałych atrybutach w formularzu w widoku

            // zapytanie do bazy danych, które zwróci poszczególne wartości wszystkich zaakceptowanych restauracji
            $query = "
                SELECT r.id, r.name, delivery_price, description, banner_url, profile_url,
                (SELECT GROUP_CONCAT(DISTINCT(t.name) SEPARATOR ', ') FROM dishes AS d INNER JOIN dish_types AS t ON d.dish_type_id = t.id 
                WHERE restaurant_id = r.id ORDER BY t.name) AS dish_types
                FROM restaurants AS r WHERE accept = 1 AND name LIKE :search LIMIT :total OFFSET :page
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('search', '%' . $search_text . '%');
            $statement->bindValue('total', $total_per_page, PDO::PARAM_INT);
            $statement->bindValue('page', $page, PDO::PARAM_INT);
            $statement->execute();
            while ($row = $statement->fetchObject(ListRestaurantModel::class)) array_push($res_list, $row);

            // zapytanie zliczające wszystkie aktywne restauracje
            $query = "SELECT count(*) FROM restaurants WHERE accept = 1 AND name LIKE :search";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('search', '%' . $search_text . '%');
            $statement->execute();
            $total_records = $statement->fetchColumn();

            $total_pages = ceil($total_records / $total_per_page);
            for ($i = 1; $i <= $total_pages; $i++) array_push($pagination, array(
                'it' => $i,
                'url' => 'restaurants' . $with_search . 'page=' . $i . '&total=' . $total_per_page, 
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
            $pagination_visible = false;
            SessionHelper::create_session_banner(SessionHelper::HOME_RESTAURANTS_LIST_PAGE, $e->getMessage(), true);
        }
        return array(
            'total_per_page' => $total_per_page,
            'pagination_url' => 'restaurants' . $with_search,
            'pagination' => $pagination,
            'pagination_visible' => $pagination_visible,
            'pages_nav' => $pages_nav,
            'res_list' => $res_list,
            'search_text' => $search_text,
            'count_of_results' => $total_records,
            'not_empty' => count($res_list),
        );
    }
}
