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
 * Ostatnia modyfikacja: 2023-01-11 07:20:11                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services;

use PDO;
use Exception;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\ListRestaurantModel;
use App\Models\RestaurantFilterModel;
use App\Models\RestaurantPersistFilterModel;
use App\Services\Helpers\CookieHelper;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\PaginationHelper;

ResourceLoader::load_service_helper('CookieHelper');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('PaginationHelper');
ResourceLoader::load_model('ListRestaurantModel', 'restaurant');
ResourceLoader::load_model('RestaurantFilterModel', 'restaurant');
ResourceLoader::load_model('RestaurantPersistFilterModel', 'restaurant');

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
        $filter = new RestaurantFilterModel;
        try
        {
            $this->dbh->beginTransaction();
            $curr_page = $_GET['page'] ?? 1; // pobranie indeksu paginacji
            $page = ($curr_page - 1) * 5;
            $total_per_page = $_GET['total'] ?? 5;
            $search_text = SessionHelper::persist_search_text('search', SessionHelper::RES_MAIN_SEARCH);
            
            $with_search = empty($search_text) ? '?' : '?search=' . $search_text . '&';
            $redirect_url = $with_search == '?' ? 'restaurants' : 'restaurants' . $with_search;
            PaginationHelper::check_parameters($redirect_url);
            
            if (isset($_COOKIE[CookieHelper::RESTAURANT_FILTERS]))
            {
                $persist_assoc = json_decode($_COOKIE[CookieHelper::RESTAURANT_FILTERS], true);
                $filter = RestaurantPersistFilterModel::decode_to_filter_model($persist_assoc);
            }
           
            $restaurant_open_query = "
                AND (SELECT COUNT(*) > 0 FROM restaurant_hours AS h INNER JOIN weekdays AS wk ON h.weekday_id = wk.id
                WHERE wk.name_eng = LOWER(DAYNAME(NOW())) AND h.open_hour <= CURTIME() AND h.close_hour >= CURTIME()
                AND h.restaurant_id = r.id)
            ";
            $delivery_free_query = " AND delivery_price IS NULL";
            $restaurant_has_discounts_query = " AND (SELECT COUNT(*) > 0 FROM discounts AS dsc WHERE dsc.restaurant_id = r.id)";
            $restaurant_has_images_query = " AND r.profile_url IS NOT NULL";

            // filtrowanie tylko otwartych restauracji
            $filter->on_exist_filter_append('restaurant-open-now', $restaurant_open_query, $filter->open_selected);
            // filtrowanie tylko tych restauracji co oferują darmową dostawę
            $filter->on_exist_filter_append('restaurant-delivery-free', $delivery_free_query, $filter->delivery_free_selected);
            // filtrowanie tylko tych restauracji co posiadają kody rabatowe
            $filter->on_exist_filter_append('restaurant-discounts', $restaurant_has_discounts_query, $filter->has_discounts_selected);
            // filtrowanie tylko tych restauracji co posiadają zdjęcie profilowe
            $filter->on_exist_filter_append('restaurant-has-images', $restaurant_has_images_query, $filter->has_profile_selected);

            if (isset($_POST['restaurant-grade-stars'])) // filtrowanie po ilości ocen
            {
                $grade_stars = filter_var($_POST['restaurant-grade-stars'][0], FILTER_SANITIZE_NUMBER_INT);
                $filter->grade_stars['stars'] = $grade_stars;
            }
            else $grade_stars = $filter->grade_stars['stars'];
            
            if (!empty($grade_stars)) $filter->grade_stars['query'] = " AND
                (SELECT AVG(rg.grade) FROM restaurants_grades AS rg INNER JOIN orders AS o ON rg.order_id = o.id 
                WHERE restaurant_id = r.id) >= $grade_stars
            ";
            else $filter->grade_stars['query'] = '';
            for ($i = 0; $i < count($filter->grade_stars['data']); $i++)
            {
                if ($i + 1 <= $grade_stars) $filter->grade_stars['data'][$i]['checked'] = 'checked';
                else $filter->grade_stars['data'][$i]['checked'] = '';
            }
            $filter->grade_stars['data'] = array_reverse($filter->grade_stars['data']);
            
            if (isset($_POST['restaurant-min-deliv-price'])) // filtrowanie po minimalnej kwocie zamówienia
            {
                $min_delivery_price = filter_var($_POST['restaurant-min-deliv-price'], FILTER_SANITIZE_NUMBER_INT);
                $filter->min_delivery_price['price'] = $min_delivery_price;
            }
            else $min_delivery_price = $filter->min_delivery_price['price'];
            switch($min_delivery_price)
            {
                case '0':  $filter->min_delivery_price['query'] = " AND min_price IS NULL"; break;
                case '35': $filter->min_delivery_price['query'] = " AND (CAST(min_price AS DECIMAL(10,2)) BETWEEN 0 AND 35)"; break;
                case '50': $filter->min_delivery_price['query'] = " AND (CAST(min_price AS DECIMAL(10,2)) BETWEEN 0 AND 50)"; break;
                case '51': $filter->min_delivery_price['query'] = " AND (CAST(min_price AS DECIMAL(10,2)) > 50)"; break;
            }
            $filter->find_parameter_and_fill($min_delivery_price, $filter->min_delivery_price['data'], 'checked');

            if (isset($_POST['restaurant-sort-properties'])) // sortowanie po dodatkowych parametrach
            {
                $sorting_param = $_POST['restaurant-sort-properties'];
                $filter->sort_parameters['sortedby'] = $sorting_param;
            }
            else $sorting_param = $filter->sort_parameters['sortedby'];
            switch($sorting_param)
            {
                case 'dish-price':
                    $filter->sorting_query = "(SELECT AVG(d.price) FROM dishes AS d WHERE d.restaurant_id = r.id)";
                    break;
                case 'delivery-price':
                    $filter->sorting_query = "IFNULL(r.delivery_price, 0)";
                    break;
                case 'name-alphabetically':
                    $filter->sorting_query = "r.name";
                    break;
                case 'delivery-time':
                    $filter->sorting_query = "(SELECT AVG(TIMEDIFF(finish_order, date_order)) FROM orders WHERE restaurant_id = r.id)";
                    break;
                case 'restaurant-rating':
                    $filter->sorting_query = "
                        (SELECT AVG(g.grade) FROM restaurants_grades AS g INNER JOIN orders AS o ON g.order_id = o.id
                        WHERE restaurant_id = r.id)
                    ";
                    break;
                case 'count-of-grades':
                    $filter->sorting_query = "
                        (SELECT COUNT(*) FROM restaurants_grades AS g INNER JOIN orders AS o ON g.order_id = o.id
                        WHERE restaurant_id = r.id)
                    ";
                    break;
            }
            if ($sorting_param != '-') $filter->sorting_query = 'ORDER BY ' . $filter->sorting_query;
            $filter->find_parameter_and_fill($sorting_param, $filter->sort_parameters['data']);
                
            if (isset($_POST['restaurant-sort-direction'])) // ustawienie dodatkowego kierunku sortowania (rosnące/malejące)
            {
                $sorting_dir = $_POST['restaurant-sort-direction'];
                $filter->sort_directions['dir'] = $sorting_dir;
            }
            else $sorting_dir = $filter->sort_directions['dir'];
            if (!empty($filter->sorting_query))
            {
                $filter->sorting_query .= ' ' . $sorting_dir;
                $filter->find_parameter_and_fill($sorting_dir, $filter->sort_directions['data']);
            }
            if (isset($_POST['filter-change']))
            {
                $persist_model = new RestaurantPersistFilterModel($filter);
                CookieHelper::set_non_expired_cookie(CookieHelper::RESTAURANT_FILTERS, json_encode($persist_model));
            }
            // zapytanie do bazy danych, które zwróci poszczególne wartości wszystkich zaakceptowanych restauracji
            $query = "
                SELECT r.id, r.name, IF(delivery_price, CONCAT(REPLACE(delivery_price, '.', ','), ' zł'), 'darmowa') AS delivery_price,
                description, banner_url, profile_url, delivery_price IS NULL AS delivery_free,
                (SELECT CONCAT(
                    IFNULL(NULLIF(CONCAT(HOUR(SEC_TO_TIME(AVG(TIME_TO_SEC(TIMEDIFF(finish_order, date_order))))), 'h '), 0), ''),
                    IFNULL(NULLIF(CONCAT(MINUTE(SEC_TO_TIME(AVG(TIME_TO_SEC(TIMEDIFF(finish_order, date_order))))), 'min'), 0), '-')
                ) FROM orders WHERE restaurant_id = r.id) AS avg_delivery_time,
                (SELECT COUNT(*) > 0 FROM discounts AS dsc WHERE dsc.restaurant_id = r.id) AS has_discounts,
                (SELECT GROUP_CONCAT(
                    DISTINCT(t.name) SEPARATOR ', ') FROM dishes AS d INNER JOIN dish_types AS t ON d.dish_type_id = t.id 
                    WHERE restaurant_id = r.id ORDER BY t.name
                ) AS dish_types,
                (SELECT IFNULL(NULLIF(REPLACE(ROUND(AVG(rg.grade), 1), '.', ','), 0), '-')
                    FROM restaurants_grades AS rg INNER JOIN orders AS o ON rg.order_id = o.id
                    WHERE restaurant_id = r.id
                ) AS avg_grades,
                (SELECT IF(COUNT(*) > 0, CONCAT('(', COUNT(*), ')'), '')
                    FROM restaurants_grades AS rg INNER JOIN orders AS o ON rg.order_id = o.id WHERE restaurant_id = r.id
                ) AS total_grades,
                (SELECT IF(COUNT(*) > 0, '', true) 
                    FROM restaurant_hours AS h INNER JOIN weekdays AS wk ON h.weekday_id = wk.id
                    WHERE wk.name_eng = LOWER(DAYNAME(NOW())) AND h.open_hour <= CURTIME() AND h.close_hour >= CURTIME()
                    AND h.restaurant_id = r.id
                ) AS is_closed
                FROM restaurants AS r 
                WHERE accept = 1 " . $filter->combined_filter_query() . " AND name LIKE :search " . $filter->sorting_query . 
                " LIMIT :total OFFSET :page
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('search', '%' . $search_text . '%');
            $statement->bindValue('total', $total_per_page, PDO::PARAM_INT);
            $statement->bindValue('page', $page, PDO::PARAM_INT);
            $statement->execute();
            while ($row = $statement->fetchObject(ListRestaurantModel::class)) array_push($res_list, $row);
            
            // zapytanie zliczające wszystkie aktywne restauracje
            $query = "
                SELECT count(*) FROM restaurants AS r WHERE accept = 1 " . $filter->combined_filter_query() . " AND name LIKE :search
            ";
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
            SessionHelper::create_session_banner(SessionHelper::HOME_RESTAURANTS_LIST_PAGE_BANNER, $e->getMessage(), true);
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
            'filter' => $filter,
            'diff_filter_desktop' => array('diff' => 'desktop'),
            'diff_filter_mobile' => array('diff' => 'mobile'),
        );
    }
}
