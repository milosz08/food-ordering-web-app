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
 * Ostatnia modyfikacja: 2024-06-08 00:51:40                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services;

use PDO;
use Exception;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\OpinionModel;
use App\Models\ListRestaurantModel;
use App\Models\RestaurantFilterModel;
use App\Models\DishDetailsCartModel;
use App\Models\RestaurantDetailsModel;
use App\Models\RestaurantPersistFilterModel;
use App\Models\RestaurantWithDishesPageModel;
use App\Services\Helpers\CookieHelper;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\PaginationHelper;

ResourceLoader::load_service_helper('CookieHelper');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('PaginationHelper');
ResourceLoader::load_model('OpinionModel', 'rating');
ResourceLoader::load_model('DishDetailsCartModel', 'cart');
ResourceLoader::load_model('ListRestaurantModel', 'restaurant');
ResourceLoader::load_model('RestaurantFilterModel', 'restaurant');
ResourceLoader::load_model('RestaurantDetailsModel', 'restaurant');
ResourceLoader::load_model('RestaurantPersistFilterModel', 'restaurant');
ResourceLoader::load_model('RestaurantWithDishesPageModel', 'restaurant');

class RestaurantsService extends MvcService
{
    private $_banner_message = '';
    private $_banner_error = false;

    protected function __construct()
    {
        parent::__construct();
    }

    public function get_all_accepted_restaurants()
    {
        $pagination = array();
        $res_list = array();
        $pages_nav = array();
        $with_search = '?';
        $total_records = 0;
        $filter = new RestaurantFilterModel;
        try
        {
            $this->dbh->beginTransaction();
            $curr_page = $_GET['page'] ?? 1; // pobranie indeksu paginacji
            $page = ($curr_page - 1) * 10;
            $total_per_page = $_GET['total'] ?? 10;
            $search_text = $_GET['search'] ?? '';

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

            if (isset($_POST['filter-change']))
            {
                // filtrowanie tylko otwartych restauracji
                $filter->open_selected = isset($_POST['restaurant-open-now']) ? 'checked' : '';
                // filtrowanie tylko tych restauracji co oferują darmową dostawę
                $filter->delivery_free_selected = isset($_POST['restaurant-delivery-free']) ? 'checked' : '';
                // filtrowanie tylko tych restauracji co posiadają kody rabatowe
                $filter->has_discounts_selected = isset($_POST['restaurant-discounts']) ? 'checked' : '';
                // filtrowanie tylko tych restauracji co posiadają zdjęcie profilowe
                $filter->has_profile_selected = isset($_POST['restaurant-has-images']) ? 'checked' : '';
            }
            if (empty($filter->open_selected)) $filter->filter_query = str_replace($restaurant_open_query, '', $filter->filter_query);
            else $filter->filter_query .= $restaurant_open_query;
            if (empty($filter->delivery_free_selected)) $filter->filter_query = str_replace($delivery_free_query, '', $filter->filter_query);
            else $filter->filter_query .= $delivery_free_query;
            if (empty($filter->has_discounts_selected)) $filter->filter_query = str_replace($restaurant_has_discounts_query, '', $filter->filter_query);
            else $filter->filter_query .= $restaurant_has_discounts_query;
            if (empty($filter->has_profile_selected)) $filter->filter_query = str_replace($restaurant_has_images_query, '', $filter->filter_query);
            else $filter->filter_query .= $restaurant_has_images_query;

            if (isset($_POST['filter-change'])) // filtrowanie po ilości ocen
            {
                $grade_stars = isset($_POST['restaurant-grade-stars']) 
                    ? filter_var($_POST['restaurant-grade-stars'][0], FILTER_SANITIZE_NUMBER_INT) : '';
                $filter->grade_stars['stars'] = $grade_stars;
            }
            else $grade_stars = $filter->grade_stars['stars'];
            if (!empty($grade_stars)) $filter->grade_stars['query'] = " AND
                (SELECT AVG((restaurant_grade + delivery_grade) / 2) FROM restaurants_grades AS rg INNER JOIN orders AS o ON rg.order_id = o.id 
                WHERE restaurant_id = r.id) >= $grade_stars
            ";
            else $filter->grade_stars['query'] = '';
            for ($i = 0; $i < count($filter->grade_stars['data']); $i++)
            {
                if ($i + 1 <= $grade_stars) $filter->grade_stars['data'][$i]['checked'] = 'checked';
                else $filter->grade_stars['data'][$i]['checked'] = '';
            }
            $filter->grade_stars['data'] = array_reverse($filter->grade_stars['data']);
            
            if (isset($_POST['filter-change'])) // filtrowanie po minimalnej kwocie zamówienia
            {
                $min_delivery_price = isset($_POST['restaurant-min-deliv-price']) ? 
                    filter_var($_POST['restaurant-min-deliv-price'], FILTER_SANITIZE_NUMBER_INT) : '-';
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

            if (isset($_POST['filter-change'])) // sortowanie po dodatkowych parametrach
            {
                $sorting_param = $_POST['restaurant-sort-properties'] ?? '-';
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
                
            if (isset($_POST['filter-change'])) // ustawienie dodatkowego kierunku sortowania (rosnące/malejące)
            {
                $sorting_dir = $_POST['restaurant-sort-direction'] ?? 'ASC';
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
                CONCAT('ul. ', street, ' ', building_locale_nr) AS street_number, CONCAT(post_code, ' ', city) AS city_post_code,
                CONCAT('+48 ', phone_number) AS phone_number, description, delivery_price IS NULL AS delivery_free,
                IFNULL(banner_url, 'static/images/default-banner.jpg') AS banner_url,
                IFNULL(profile_url, 'static/images/default-profile.jpg') AS profile_url,
                IF(min_price, CONCAT(REPLACE(min_price, '.', ','), ' zł'), 'brak') AS min_delivery_price,
                (SELECT CONCAT(
                    IFNULL(NULLIF(CONCAT(HOUR(SEC_TO_TIME(AVG(TIME_TO_SEC(TIMEDIFF(finish_order, date_order))))), 'h '), 0), ''),
                    IFNULL(NULLIF(CONCAT(MINUTE(SEC_TO_TIME(AVG(TIME_TO_SEC(TIMEDIFF(finish_order, date_order))))), 'min'), 0), '?')
                ) FROM orders WHERE restaurant_id = r.id) AS avg_delivery_time,
                (SELECT COUNT(*) > 0 FROM discounts AS dsc WHERE dsc.restaurant_id = r.id) AS has_discounts,
                (SELECT GROUP_CONCAT(
                    DISTINCT(t.name) SEPARATOR ', ') FROM dishes AS d INNER JOIN dish_types AS t ON d.dish_type_id = t.id 
                    WHERE restaurant_id = r.id ORDER BY t.name
                ) AS dish_types,
                (SELECT IFNULL(NULLIF(REPLACE(ROUND(AVG((restaurant_grade + delivery_grade) / 2), 1), '.', ','), 0), '?')
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

            if (!empty($res_list))
            {
                $restaurants_ids_impl = '';
                $this->dbh->prepare("SET lc_time_names = 'pl_PL'")->execute();
                $query = "
                    SELECT restaurant_id AS res_id, restaurant_grade, delivery_grade, description,
                    IF(anonymously = 1, 'Anonimowy', CONCAT(first_name, ' ', last_name)) AS signature,
                    CONCAT(DAYNAME(give_on), ', ', DAY(give_on), ' ', MONTHNAME(give_on), ' ', YEAR(give_on)) AS give_on
                    FROM ((restaurants_grades AS rg
                    INNER JOIN orders AS o ON rg.order_id = o.id)
                    INNER JOIN users AS u ON o.user_id = u.id)
                    ORDER BY give_on DESC
                ";
                $statement = $this->dbh->prepare($query);
                $statement->execute();
                foreach ($res_list as $res) $restaurants_ids_impl .= $res->id . ',';
                while ($row = $statement->fetchObject(OpinionModel::class))
                {
                    foreach ($res_list as $res) if ($row->res_id == $res->id) array_unshift($res->opinions, array('opinion' => $row));
                }
                $query = "
                    SELECT r.id AS res_id, w.name AS name,
                    CONCAT(IF((SELECT DATE_FORMAT(open_hour, '%H:%i') 
                        FROM restaurant_hours WHERE restaurant_id = r.id AND weekday_id = w.id) IS NULL,'',
                        CONCAT((SELECT DATE_FORMAT(open_hour, '%H:%i')
                        FROM restaurant_hours WHERE restaurant_id = r.id AND weekday_id = w.id), ' - ')),
                        IFNULL((SELECT DATE_FORMAT(close_hour, '%H:%i') FROM restaurant_hours WHERE restaurant_id = r.id AND weekday_id = w.id),
                        'nieczynne')
                    ) AS hours
                    FROM weekdays AS w
                    LEFT OUTER JOIN restaurants AS r ON r.id IN(" . rtrim($restaurants_ids_impl, ',') . ")
                    ORDER BY w.id DESC
                ";
                $statement = $this->dbh->prepare($query);
                $statement->execute();
                $all_hours_from_all_restaurants = $statement->fetchAll(PDO::FETCH_ASSOC);
                foreach ($res_list as $res)
                {
                    foreach ($all_hours_from_all_restaurants as $row)
                    {
                        if ($row['res_id'] == $res->id)
                        {
                            $res_hours = array('day' => $row['name'], 'hours' => $row['hours']);
                            array_unshift($res->delivery_hours, array('hour' => $res_hours));
                        }
                    }
                }
            }
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
                'selected' => $curr_page == $i ? 'active' : '',
            ));
            PaginationHelper::check_if_page_is_greaten_than($redirect_url, $total_pages);
            $pages_nav = PaginationHelper::get_pagination_nav($curr_page, $total_per_page, $total_pages, $total_records, $redirect_url);
            $statement->closeCursor();
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::HOME_RESTAURANTS_LIST_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'total_per_page' => $total_per_page,
            'pagination_url' => 'restaurants' . $with_search,
            'pagination' => $pagination,
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

    public function get_restaurant_dishes_with_cart()
    {
        $row = new RestaurantDetailsModel;
        $cookie = $_COOKIE[CookieHelper::get_shopping_cart_name($_GET['id'])] ?? null;
        $dish_types = array();
        $res_details = array();
        try
        {
            $this->dbh->beginTransaction();

            // Walidacja $GET danej restauracji, w przeciwnym wypadku powróci do strony restauracji
            if (!isset($_GET['id'])) header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
            $search_dish_name = $_GET['search'] ?? '';

            // Pobranie nazwy pojedyńczej restauracji, do umieszczenia jej w zakładce
            $query = "
                SELECT r.id, r.name, REPLACE(CAST(delivery_price AS DECIMAL(10,2)), '.', ',') AS delivery_price_no, description,
                IF(delivery_price, CONCAT(REPLACE(CAST(delivery_price AS DECIMAL(10,2)), '.', ','), ' zł'), '0,00') AS delivery_price,
                CONCAT('ul. ', street, ' ', building_locale_nr) AS street_number, CONCAT(post_code, ' ', city) AS city_post_code,
                CONCAT('+48 ', phone_number) AS phone_number, delivery_price IS NULL AS delivery_free,
                IF(min_price, CONCAT(REPLACE(min_price, '.', ','), ' zł'), 'brak') AS min_delivery_price,
                IFNULL(banner_url, 'static/images/default-banner.jpg') AS banner_url,
                IFNULL(profile_url, 'static/images/default-profile.jpg') AS profile_url,
                (SELECT COUNT(*) > 0 FROM discounts AS dsc WHERE dsc.restaurant_id = r.id) AS has_discounts,
                IFNULL(REPLACE(CAST(min_price AS DECIMAL(10,2)), '.', ','), 0) AS min_price,
                (SELECT IFNULL(NULLIF(REPLACE(ROUND(AVG((restaurant_grade + delivery_grade) / 2), 1), '.', ','), 0), '?')
                    FROM restaurants_grades AS rg INNER JOIN orders AS o ON rg.order_id = o.id
                    WHERE restaurant_id = r.id
                ) AS avg_grades,
                (SELECT IF(COUNT(*) > 0, CONCAT('(', COUNT(*), ')'), '')
                    FROM restaurants_grades AS rg INNER JOIN orders AS o ON rg.order_id = o.id WHERE restaurant_id = r.id
                ) AS total_grades
                FROM ((restaurants AS r
                INNER JOIN restaurant_hours AS h ON r.id = h.restaurant_id)
                INNER JOIN weekdays AS wk ON h.weekday_id = wk.id)
                WHERE wk.name_eng = LOWER(DAYNAME(NOW())) AND h.open_hour <= CURTIME() AND h.close_hour >= CURTIME()
                AND r.id = ? AND accept = 1
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));
            $res_details = $statement->fetchObject(RestaurantWithDishesPageModel::class);
            if (!$res_details)
            {
                $this->_banner_message = 'Wybrana restauracja nie istnieje, bądź nie jest otwarta.';
                SessionHelper::create_session_banner(SessionHelper::HOME_RESTAURANTS_LIST_PAGE_BANNER, $this->_banner_message, true);
                header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
                die;
            }
            $this->dbh->prepare("SET lc_time_names = 'pl_PL'")->execute();
            $query = "
                SELECT restaurant_id AS res_id, restaurant_grade, delivery_grade, description,
                IF(anonymously = 1, 'Anonimowy', CONCAT(first_name, ' ', last_name)) AS signature,
                CONCAT(DAYNAME(give_on), ', ', DAY(give_on), ' ', MONTHNAME(give_on), ' ', YEAR(give_on)) AS give_on
                FROM ((restaurants_grades AS rg
                INNER JOIN orders AS o ON rg.order_id = o.id)
                INNER JOIN users AS u ON o.user_id = u.id)
                WHERE restaurant_id = ?
                ORDER BY give_on DESC
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));
            while ($row = $statement->fetchObject(OpinionModel::class)) array_unshift($res_details->opinions, array('opinion' => $row));
            $query = "
                SELECT w.name AS name, CONCAT(IF((SELECT DATE_FORMAT(open_hour, '%H:%i') 
                    FROM restaurant_hours WHERE restaurant_id = :resid AND weekday_id = w.id) IS NULL,'',
                    CONCAT((SELECT DATE_FORMAT(open_hour, '%H:%i')
                    FROM restaurant_hours WHERE restaurant_id = :resid AND weekday_id = w.id), ' - ')),
                    IFNULL((SELECT DATE_FORMAT(close_hour, '%H:%i') FROM restaurant_hours WHERE restaurant_id = :resid AND weekday_id = w.id),
                    'nieczynne')
                ) AS hours
                FROM weekdays AS w
                ORDER BY w.id DESC
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('resid', $_GET['id'], PDO::PARAM_INT);
            $statement->execute();
            foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row)
            {
                $res_hours = array('day' => $row['name'], 'hours' => $row['hours']);
                array_unshift($res_details->delivery_hours, array('hour' => $res_hours));
            }
            $min_price_num = (float)str_replace(',', '.', $res_details->min_price) * 100;

            // Zapytanie pobierające wszystkie kategorie podanej restauracji bez powtórzeń oraz tych samych kategorii bez powtórzeń
            // służących do przemieszczania się po stronie.
            $query = "
                SELECT DISTINCT CONCAT(UPPER(SUBSTRING(dt.name,1,1)), LOWER(SUBSTRING(dt.name,2))) AS dish_type_name,
                LOWER(REPLACE(dt.name,' ', '-')) AS dish_type_nav FROM dishes d
                INNER JOIN dish_types dt ON d.dish_type_id = dt.id WHERE restaurant_id = :resid AND
                (d.name LIKE :search OR d.description LIKE :search) ORDER BY dt.name
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('resid', $_GET['id'], PDO::PARAM_INT);
            $statement->bindValue('search', '%' . $search_dish_name . '%');
            $statement->execute();

            // Pętla odpowiada za wpisywanie podanych dań wraz z pasującą do nich kategorią
            while ($row = $statement->fetchObject())
            {
                $dishes = array();
                $d_query = "
                    SELECT d.id, d.name AS dish_name, d.description, d.photo_url, d.prepared_time,
                    REPLACE(CAST(d.price AS DECIMAL(10,2)), '.', ',') AS price FROM dishes d 
                    INNER JOIN dish_types dt ON d.dish_type_id = dt.id WHERE d.restaurant_id = ? AND
                    dt.name = ?
                ";
                $d_statement = $this->dbh->prepare($d_query);
                $d_statement->execute(array($_GET['id'], $row->dish_type_name));
                // Wpisanie wszytstkich szczegółów znalezionych dań pasujących do kategorii.
                while ($d_row = $d_statement->fetchObject()) array_push($dishes, $d_row);
                // Uzupełnienie tablicy $dishTypes podaną kategorią, wraz z wszystkimi znalezionymi daniami.
                array_push($dish_types, array('type' => $row, 'dishes' => $dishes));
                // Wyczyszczenie tablicy, aby przy nastepnym powtórzeniu nie wpisywały się poprzednie wartości
                $d_statement->closeCursor();
            }

            // Tablice pomocnicze kolejno uzupełniająca koszyk oraz obsługująca wartość dostawy restauracji
            $dish_details_not_founded = false;
            $shopping_cart = array();
            $summary_prices = array(
                'total' => '0', 'total_num' => 0, 'total_with_delivery' => '0', 'diff_not_enough' => 0, 
                'percentage_discount' => '1'
            );
            if (isset($cookie))
            {
                $cart_cookie = json_decode($cookie, true);
                // Pętla iterująca po otrzymanej tablicy zdekodowanego pliku json.
                foreach ($cart_cookie['dishes'] as $dish)
                {
                    // Zapytanie pobierające potrzebne szczegóły dania
                    $query = "
                        SELECT d.id, d.name, d.description, REPLACE(CAST(d.price * :count AS DECIMAL(10,2)), '.', ',') AS total_dish_cost
                        FROM dishes d INNER JOIN restaurants r ON d.restaurant_id = r.id WHERE d.id = :id
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->bindValue('count', $dish['count'], PDO::PARAM_INT);
                    $statement->bindValue('id', $dish['dishid']);
                    $statement->execute();
                    // sprawdź, czy podana potrawa z id odczytanym z jsona istnieje, jeśli nie, nie dodawaj do koszyka
                    if ($dish_details = $statement->fetchObject(DishDetailsCartModel::class)) 
                    {
                        // Uzupełnienie tablicy przechowującej szczegóły dania 
                        array_push($shopping_cart, array('cart_dishes' => $dish_details, 'count_of_dish' => $dish['count']));
                        $summary_prices['total_num'] += (float)str_replace(',', '.', $dish_details->total_dish_cost) * 100;
                    }
                    else $dish_details_not_founded = true;
                }
                if(!empty($cart_cookie['code']))
                {
                    $query = "SELECT REPLACE(CAST(percentage_discount AS DECIMAL(10,2)), '.', ',') AS percentage_discount 
                    FROM discounts WHERE code = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($cart_cookie['code']));
                    if ($perc = $statement->fetch(PDO::FETCH_ASSOC)) $summary_prices['percentage_discount'] = $perc['percentage_discount'];
                }
                if ($dish_details_not_founded) CookieHelper::delete_cookie(CookieHelper::get_shopping_cart_name($_GET['id']));
                else
                {
                    $delivery = (float)str_replace(',', '.', $res_details->delivery_price_no) * 100;

                    if ($summary_prices['percentage_discount'] == 1) $percent = 100;
                    else $percent = (100 - (float) str_replace(',', '.', $summary_prices['percentage_discount']));

                    $calculate = (($summary_prices['total_num']/100) * $percent);
                    $summary_prices['total'] = number_format(($summary_prices['total_num']*($percent/100)) / 100, 2, ',', ' ');
                    $summary_prices['total_with_delivery'] = number_format(($calculate + $delivery) / 100, 2, ',', ' ');
                    $summary_prices['diff_not_enough'] = $min_price_num - $summary_prices['total_num'];
                }
            }
            $statement->closeCursor();
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::RESTAURANT_DISHES_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'res_details' => $res_details,
            'dish_types' => $dish_types,
            'res_id' => $_GET['id'],
            'search_text' => $search_dish_name,
            'shopping_cart' => $shopping_cart,
            'summary_prices' => $summary_prices,
            'diff_not_enough' => number_format($summary_prices['diff_not_enough'] / 100, 2, ',', ' '),
            'not_enough_total_sum' => $min_price_num > $summary_prices['total_num'],
            'cart_is_empty' => !isset($cookie),
        );
    }
}
