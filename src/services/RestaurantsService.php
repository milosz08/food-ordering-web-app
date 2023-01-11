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
 * Ostatnia modyfikacja: 2023-01-11 02:46:08                   *
 * Modyfikowany przez: Lukasz Krawczyk                         *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services;

use App\Models\RestaurantDetailsModel;
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
ResourceLoader::load_model('RestaurantDetailsModel', 'user');

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
        try {
            $this->dbh->beginTransaction();
            $curr_page = $_GET['page'] ?? 1; // pobranie indeksu paginacji
            $page = ($curr_page - 1) * 5;
            $total_per_page = $_GET['total'] ?? 5;
            $search_text = $_GET['search'] ?? ''; // wyszukiwanie po frazie nazwy restauracji

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
            while ($row = $statement->fetchObject(ListRestaurantModel::class))
                array_push($res_list, $row);

            // zapytanie zliczające wszystkie aktywne restauracje
            $query = "SELECT count(*) FROM restaurants WHERE accept = 1 AND name LIKE :search";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('search', '%' . $search_text . '%');
            $statement->execute();
            $total_records = $statement->fetchColumn();

            $total_pages = ceil($total_records / $total_per_page);
            for ($i = 1; $i <= $total_pages; $i++)
                array_push(
                    $pagination,
                    array(
                        'it' => $i,
                        'url' => 'restaurants' . $with_search . 'page=' . $i . '&total=' . $total_per_page,
                        'selected' => $curr_page == $i ? 'active' : '',
                    )
                );
            PaginationHelper::check_if_page_is_greaten_than($redirect_url, $total_pages);
            $pages_nav = PaginationHelper::get_pagination_nav($curr_page, $total_per_page, $total_pages, $total_records, $redirect_url);
            $statement->closeCursor();
            $this->dbh->commit();
        } catch (Exception $e) {
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
        );
    }

    public function getSingleRestaurantDetails()
    {
        $row = new RestaurantDetailsModel;
        $dishTypes = array();
        $restaurantDetails = array();
        try {
            $this->dbh->beginTransaction();

            // Walidacja $GET danej restauracji, w przeciwnym wypadku powróci do strony restauracji
            if (isset($_GET['id']))
                $restaurantId = $_GET['id'];
            else
                header('Location:' . __URL_INIT_DIR__ . '/restaurants', true, 301);

            // Pobranie nazwy pojedyńczej restauracji, do umieszczenia jej w zakładce
            $query = "SELECT name FROM restaurants WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(
                array(
                    $restaurantId
                )
            );
            $restaurantName = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$restaurantName)
                header('Location:' . __URL_INIT_DIR__ . '/restaurants', true, 301);

            /* Zapytanie pobierające wszystkie kategorie podanej restauracji bez powtórzeń oraz tych samych kategorii bez powtórzeń
             *   służących do przemieszczania się po stronie.
             */
            $query = "SELECT DISTINCT dt.name AS dishType_name, LOWER(REPLACE(dt.name,' ', '-')) AS dishType_nav FROM dishes d 
                INNER JOIN  dish_types dt ON d.dish_type_id = dt.id WHERE restaurant_id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(
                array(
                    $restaurantId
                )
            );
            // Pętla odpowiada za wpisywanie podanych dań wraz z pasującą do nich kategorią
            while ($row = $statement->fetchObject()) {
                $query2 = "SELECT d.id, d.name AS dish_name, d.description, d.photo_url, d.price, d.prepared_time FROM dishes d 
                            INNER JOIN dish_types dt ON d.dish_type_id = dt.id WHERE d.restaurant_id = ? AND
                            dt.name = ?";
                $statement2 = $this->dbh->prepare($query2);
                $statement2->execute(
                    array(
                        $restaurantId,
                        $row->dishType_name
                    )
                );
                // Wpisanie wszytstkich szczegółów znalezionych dań pasujących do kategorii.
                while ($row2 = $statement2->fetchObject(RestaurantDetailsModel::class)) {
                    array_push($restaurantDetails, $row2);
                }
                // Uzupełnienie tablicy $dishTypes podaną kategorią, wraz z wszystkimi znalezionymi daniami.
                array_push($dishTypes, array('type' => $row, 'list' => $restaurantDetails));
                // Wyczyszczenie tablicy, aby przy nastepnym powtórzeniu nie wpisywały się poprzednie wartości
                $restaurantDetails = array();
            }

            $shopping_card = array();
            $dishesSum = 0;
            $deliverySum = 0;
            if (isset($_COOKIE['dishes'])) {
                $cart = isset($_COOKIE['dishes']) ? $_COOKIE['dishes'] : '[]';
                $cart = json_decode($cart);
                foreach ($cart as $c) {
                    $query = "SELECT d.name, d.description, d.price, r.delivery_price FROM dishes d INNER JOIN restaurants r 
                    ON d.restaurant_id = r.id WHERE d.id = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(
                        array(
                            $c->dishid
                        )
                    );
                    $row = $statement->fetchObject();
                    $row->price = $row->price * $c->count;
                    array_push($shopping_card, array('list' => $row, 'il' => $c->count));
                    $dishesSum += $row->price;


                }
            }

            $statement->closeCursor();
            $this->dbh->commit();

        } catch (Exception $e) {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::HOME_RESTAURANTS_LIST_PAGE_BANNER, $e->getMessage(), true);

        }
        return array(
            'restaurantName' => $restaurantName,
            'dishTypes' => $dishTypes,
            'res_id' => $_GET['id'],
            'shoppingCard' => $shopping_card,
            'dishesSum' => $dishesSum,
            'deliverySum' => $deliverySum
        );
    }
    public function addDishToShoppingCard()
    {
        try {
            // Walidacja id restauracji w linku
            if (isset($_GET['resid']))
                $res_id = $_GET['resid'];
            else
                header('Location:' . __URL_INIT_DIR__ . '/restaurants', true, 301);

            $query = "SELECT id FROM restaurants WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(
                array(
                    $res_id
                )
            );
            $residCheck = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$residCheck)
                header('Location:' . __URL_INIT_DIR__ . '/restaurants', true, 301);


            // Walidacja id dania dla podanej restauracji
            if (isset($_GET['dishid']))
                $dish_id = $_GET['dishid'];
            else
                header('Location:' . __URL_INIT_DIR__ . '/restaurants/restaurant-details?id=' . $residCheck->id, true, 301);

            $query = "SELECT id FROM dishes WHERE restaurant_id = ? AND id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(
                array(
                    $res_id,
                    $dish_id
                )
            );
            $dishidCheck = $statement->fetch(PDO::FETCH_ASSOC);
            if (!$dishidCheck)
                header('Location:' . __URL_INIT_DIR__ . '/restaurants/restaurant-details?id=' . $residCheck->id, true, 301);

        
            // Obsługa koszyka
            $tempArray = array();
            $il = 1;
            $flag = true;
            if (isset($_COOKIE['dishes'])) {
                $card = $_COOKIE['dishes'];
                $tempArray = json_decode($card);
                foreach($tempArray as $a)
                {
                    if($a->dishid == $dish_id)
                    {
                        $a->count += 1;
                        $flag = false;
                    }
                }
                if($flag == true)
                    array_push($tempArray, array('dishid' => $dish_id, 'count' => $il, 'resid' => $res_id));
                    
            }
            else array_push($tempArray, array('dishid' => $dish_id, 'count' => $il, 'resid' => $res_id));
            setcookie('dishes', json_encode($tempArray), time() + (86400 * 30), "/");

        } catch (Exception $e) {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::HOME_RESTAURANTS_LIST_PAGE_BANNER, $e->getMessage(), true);

        }
        return $_GET['resid'];

    }

}
