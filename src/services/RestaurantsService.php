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
 * Ostatnia modyfikacja: 2023-01-12 14:17:17                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services;

use PDO;
use Exception;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\ListRestaurantModel;
use App\Models\RestaurantFilterModel;
use App\Models\DishDetailsCartModel;
use App\Models\RestaurantDetailsModel;
use App\Models\RestaurantPersistFilterModel;
use App\Services\Helpers\CookieHelper;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\PaginationHelper;

ResourceLoader::load_service_helper('CookieHelper');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('PaginationHelper');
ResourceLoader::load_model('DishDetailsCartModel', 'cart');
ResourceLoader::load_model('ListRestaurantModel', 'restaurant');
ResourceLoader::load_model('RestaurantFilterModel', 'restaurant');
ResourceLoader::load_model('RestaurantDetailsModel', 'restaurant');
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
                description, banner_url, profile_url, delivery_price IS NULL AS delivery_free,
                IF(min_price, CONCAT(REPLACE(min_price, '.', ','), ' zł'), '-') AS min_delivery_price,
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
                'selected' => $curr_page == $i ? 'active' : '',
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

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function get_restaurant_dishes_with_cart()
    {
        $row = new RestaurantDetailsModel;
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
                SELECT r.name, REPLACE(CAST(delivery_price AS DECIMAL(10,2)), '.', ',') AS delivery_price,
                IFNULL(REPLACE(CAST(min_price AS DECIMAL(10,2)), '.', ','), 0) AS min_price
                FROM ((restaurants AS r
                INNER JOIN restaurant_hours AS h ON r.id = h.restaurant_id)
                INNER JOIN weekdays AS wk ON h.weekday_id = wk.id)
                WHERE wk.name_eng = LOWER(DAYNAME(NOW())) AND h.open_hour <= CURTIME() AND h.close_hour >= CURTIME()
                AND r.id = ? AND accept = 1
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));
            $res_details = $statement->fetch(PDO::FETCH_ASSOC);
            if (!$res_details)
            {
                $this->_banner_message = 'Wybrana restauracja nie istnieje, bądź nie jest otwarta.';
                SessionHelper::create_session_banner(SessionHelper::HOME_RESTAURANTS_LIST_PAGE_BANNER, $this->_banner_message, true);
                //header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
                die;
            }
            $min_price_num = (float)str_replace(',', '.', $res_details['min_price']) * 100;

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
            $code_not_found = false;
            $shopping_cart = array();
            $summary_prices = array('total' => '0', 'total_num' => 0, 'total_with_delivery' => '0', 'diff_not_enough' => 0);
            // Sprawdzanie, czy plik cookies został dodany.
            if (isset($_COOKIE[CookieHelper::get_shopping_cart_name($_GET['id'])]))
            {
                $cart_cookie = json_decode($_COOKIE[CookieHelper::get_shopping_cart_name($_GET['id'])], true);
                // Pętla iterująca po otrzymanej tablicy zdekodowanego pliku json.
                foreach ($cart_cookie as $dish)
                {
                    // Zapytanie pobierające potrzebne szczegóły dania
                    $query = "
                        SELECT d.id, d.name, d.description, REPLACE(CAST(r.delivery_price AS DECIMAL(10,2)), '.', ',') AS delivery_price,
                        REPLACE(CAST(d.price * :count AS DECIMAL(10,2)), '.', ',') AS total_dish_cost
                        FROM dishes d
                        INNER JOIN restaurants r ON d.restaurant_id = r.id WHERE d.id = :id
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
                    if(!empty($dish['code']))
                    {
                        $codeName = $dish['code']; 
                        $code_not_found = true;
                    }
                }
                if($code_not_found)
                {
                    $query = "SELECT REPLACE(CAST(percentage_discount AS DECIMAL(10,2)), '.', ',') AS percentage_discount 
                    FROM discounts WHERE code = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(
                        array(
                            $codeName
                        )
                    );
                    $discountPercentage = $statement->fetch(PDO::FETCH_ASSOC);
                }
                if ($dish_details_not_founded) CookieHelper::delete_cookie(CookieHelper::get_shopping_cart_name($_GET['id']));
                else
                {
                    $delivery = (float)str_replace(',', '.', $dish_details->delivery_price) * 100;
                    $percent = 100 - (float)str_replace(',', '.', $discountPercentage['percentage_discount'] ?? 1);
                    $calculate = ($summary_prices['total_num']/100) * $percent;
                    $summary_prices['total'] = number_format(($summary_prices['total_num']*($percent/100) ) / 100, 2, ',');
                    $summary_prices['total_with_delivery'] = number_format(($calculate + $delivery) / 100, 2, ',');
                    $summary_prices['diff_not_enough'] = $min_price_num - $summary_prices['total_num'];
                }
            }
            $statement->closeCursor();
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::HOME_RESTAURANTS_LIST_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'res_details' => $res_details,
            'dish_types' => $dish_types,
            'res_id' => $_GET['id'],
            'search_text' => $search_dish_name,
            'shopping_cart' => $shopping_cart,
            'summary_prices' => $summary_prices,
            'diff_not_enough' => number_format($summary_prices['diff_not_enough'] / 100, 2, ','),
            'not_enough_total_sum' => $min_price_num > $summary_prices['total_num'],
            'cart_is_empty' => !isset($_COOKIE[CookieHelper::get_shopping_cart_name($_GET['id'])]),
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function addDishToShoppingCard()
    {
        // Walidacja id restauracji w linku
        if (isset($_GET['resid'])) $res_id = $_GET['resid'];
        else header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
        try
        {
            $this->dbh->beginTransaction();
            $query = "
                SELECT COUNT(*) > 0 FROM ((restaurants AS r
                INNER JOIN restaurant_hours AS h ON r.id = h.restaurant_id)
                INNER JOIN weekdays AS wk ON h.weekday_id = wk.id)
                WHERE wk.name_eng = LOWER(DAYNAME(NOW())) AND h.open_hour <= CURTIME() AND h.close_hour >= CURTIME()
                AND r.id = ? AND accept = 1
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($res_id));
            $res_id_exist = $statement->fetchColumn();
            if (!$res_id_exist)
            {
                $this->_banner_message = 'Wybrana restauracja nie istnieje, bądź nie jest otwarta.';
                SessionHelper::create_session_banner(SessionHelper::HOME_RESTAURANTS_LIST_PAGE_BANNER, $this->_banner_message, true);
                header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
                die;
            }
            // Walidacja id dania dla podanej restauracji
            if (isset($_GET['dishid'])) $dish_id = $_GET['dishid'];
            else header('Location:' . __URL_INIT_DIR__ . 'restaurants/restaurant-details?id=' . $res_id_exist->id, true, 301);

            $query = "SELECT COUNT(*) > 0 FROM dishes WHERE restaurant_id = ? AND id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($res_id, $dish_id));
            $dish_id_exist = $statement->fetchColumn();
            if (!$dish_id_exist)
            {
                $this->_banner_message = 'Wybrana potrawa nie istnieje, bądź nie jest przypisana do żadnej restauracji.';
                SessionHelper::create_session_banner(SessionHelper::HOME_RESTAURANTS_LIST_PAGE_BANNER, $this->_banner_message, true);
                header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
                die;
            }

            // Obsługa koszyka
            $tempArray = array();
            $il = 1;
            // Flaga sprawdzająca czy dany element w tablicy już się tam znajduje. Gdy dany element jest w tablicy jego ilość zostaje 
            // inkrementowana, a nie zostaje dodany jako nowy obiekt
            $isElementInArray = true;
            // Flaga sprawdzająca czy ilość danych elementów jest większa czy mniejsza niż 1, aby kolejno zinkrementować jego wartość
            // bądź nie dodawać go do nowej tablicy 
            $isCountHigherThan1 = true;
            if (isset($_COOKIE[CookieHelper::get_shopping_cart_name($_GET['resid'])]))
            {
                $tempArray = json_decode($_COOKIE[CookieHelper::get_shopping_cart_name($_GET['resid'])]);

                // Nowa tablica pomocnicza, do której element nie zostaje dodany, w momencie, gdy jego dekrementowana wartość 'il'
                // będzie kolejno mniejsza niż 1.
                $new_json_array = array();

                // Sprawdzanie, czy została wykonana akcja odpowiadająca za dodaj/odejmij element z koszyka, bazowo ustawiona na 1
                // powodująca dodanie do koszyka elementu 
                $action = $_GET['act'] ?? 1;
                // jeżeli wykonana została akcja odejmowania elementu z koszyka
                if ($action == 0) {
                    // Pętla iterująca elementy w koszyku
                    foreach ($tempArray as $a) {
                        // Jeżeli dany element pasuje po id, do wybranego elementu
                        if ($a->dishid == $dish_id) {
                            // Sprawdzenie, czy dany element jest większy od 1, gdy jest to po prostu odejmujemy od niego 1
                            if ($a->count > 1)
                                $a->count -= 1;
                            // W przeciwnym wypadku flaga zostaje ustawiona na 'false', aby element nie został dodany do nowej tablicy                 
                            else
                                $isCountHigherThan1 = false;
                            // Element istnieje w koszyku, więc chcemy dodać nową tablice
                            $isElementInArray = false;
                        }
                        // Jeżeli dany element nie jest mniejszy od 1, to włożymy go do nowej tablicy
                        if ($isCountHigherThan1)
                            array_push($new_json_array, $a);
                        // jeżeli dany element jest mniejszy od 1, to nie dodajemy go do nowej tablicy i kasujemy flagę 
                        // na następny element 
                        else
                            $isCountHigherThan1 = true;
                    }
                } else {
                    // Pętla iteruje po elementach sprawdzając, który został wybrany, aby jego ilość została zinkrementowana
                    foreach ($tempArray as $a) {
                        if ($a->dishid == $dish_id) {
                            $a->count += 1;
                            $isElementInArray = false;
                        }
                        // Dodanie każdego z elementu do nowej tablicy.
                        array_push($new_json_array, $a);
                    }
                }
                // Sprawdzanie, czy dany element istnieje w tablicy
                if ($isElementInArray)
                {
                    // Dodanie nowego elementu do tablicy i przypisanie mu kolejno wartości.
                    array_push($tempArray, array('dishid' => $dish_id, 'count' => $il, 'code' => ""));
                    CookieHelper::set_non_expired_cookie(CookieHelper::get_shopping_cart_name($_GET['resid']), json_encode($tempArray));
                }
                else CookieHelper::set_non_expired_cookie(CookieHelper::get_shopping_cart_name($_GET['resid']), json_encode($new_json_array));
            }
            // Jeżeli plik cookies nie został jeszcze utworzony, dodajemy elementy do tablicy i tworzymy nowe cookies. 
            else
            {
                array_push($tempArray, array('dishid' => $dish_id, 'count' => $il, 'code' => ""));
                CookieHelper::set_non_expired_cookie(CookieHelper::get_shopping_cart_name($_GET['resid']), json_encode($tempArray));
            }
            if (empty($new_json_array)) CookieHelper::delete_cookie(CookieHelper::get_shopping_cart_name($_GET['resid']));

            $statement->closeCursor();
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::HOME_RESTAURANTS_LIST_PAGE_BANNER, $e->getMessage(), true);
        }
        return $_GET['resid'];
    }
}
