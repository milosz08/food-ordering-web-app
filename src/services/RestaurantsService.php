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
 * Ostatnia modyfikacja: 2023-01-11 15:21:39                   *
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

            // Tablice pomocnicze kolejno uzupełniająca koszyk oraz obsługująca wartość dostawy restauracji
            $shopping_card = array();
            $restaurantArray = array();
            // Zmienne zliczające wartość dodanych dań oraz dostawy danej restauracji
            $dishesSum = 0;
            $deliverySum = 0;
            // Sprawdzanie, czy plik cookies został dodany.
            if (isset($_COOKIE['dishes'])) {

                $cart = $_COOKIE['dishes'];
                $cart = json_decode($cart);
                // Pętla iterująca po otrzymanej tablicy zdekodowanego pliku json.
                foreach ($cart as $c) {
                    // Zapytanie pobierające potrzebne szczegóły dania
                    $query = "SELECT d.id, d.name, d.description, d.price, r.delivery_price FROM dishes d 
                    INNER JOIN restaurants r ON d.restaurant_id = r.id WHERE d.id = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(
                        array(
                            $c->dishid
                        )
                    );
                    $row = $statement->fetchObject();
                    // Zliczenie ilości posiadanych danych dań w koszyku
                    $row->price = $row->price * $c->count;
                    // Uzupełnienie tablicy przechowującej szczegóły dania 
                    array_push($shopping_card, array('list' => $row, 'il' => $c->count));
                    // Zwiększenie sumy dodanych dań
                    $dishesSum += $row->price;

                    /*  If sprawdzający, czy w podanej tablicy pomocniczej występuje jakiekolwiek id restauracji, jeżeli jest ona pusta
                    *   nastąpi dodanie pierwszego id, wraz z dodaniem wartości dostawy do zmiennej 'deliverySum'.
                    */
                    if (empty($restaurantArray)) {
                        array_push($restaurantArray, $c->resid);
                        $deliverySum += $row->delivery_price;
                    }

                    /*  Pętla przechodzi po wszystkich elementach tablicy, jeżeli natrafi się ID restauracji, której dostawa 
                    *   nie została jeszcze uwzględniona, nastąpi jej dodanie do tablicy 'restaurantArray' oraz dodanie wartości 
                    *   dostawy dania z podanej restauracji.
                    */
                    foreach ($restaurantArray as $singleID) {
                        if ($singleID != $c->resid) {
                            array_push($restaurantArray, $c->resid);
                            $deliverySum += $row->delivery_price;
                        }
                    }
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
            $this->dbh->beginTransaction();
            //--------------------------------------------------------------------------------------------------------------------
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
                header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);


            // Walidacja id dania dla podanej restauracji
            if (isset($_GET['dishid']))
                $dish_id = $_GET['dishid'];
            else
                header('Location:' . __URL_INIT_DIR__ . 'restaurants/restaurant-details?id=' . $residCheck->id, true, 301);

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
                header('Location:' . __URL_INIT_DIR__ . 'restaurants/restaurant-details?id=' . $residCheck->id, true, 301);
            //--------------------------------------------------------------------------------------------------------------------

            // Obsługa koszyka
            $tempArray = array();
            $il = 1;
            // Flaga sprawdzająca czy dany element w tablicy już się tam znajduje. Gdy dany element jest w tablicy jego ilość zostaje 
            // inkrementowana, a nie zostaje dodany jako nowy obiekt
            $isElementInArray = true;
            // Flaga sprawdzająca czy ilość danych elementów jest większa czy mniejsza niż 1, aby kolejno zinkrementować jego wartość
            // bądź nie dodawać go do nowej tablicy 
            $isCountHigherThan1 = true;
            if (isset($_COOKIE['dishes'])) {
                $card = $_COOKIE['dishes'];
                $tempArray = json_decode($card);

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
                        if ($isCountHigherThan1 == true)
                            array_push($new_json_array, $a);
                        // jeżeli dany element jest mniejszy od 1, to nie dodajemy go do nowej tablicy i kasujemy flagę 
                        // na następny element 
                        else
                            $isCountHigherThan1 = true;
                            $this->_banner_message = 'Produkt został pomyślnie usunięty z koszyka';
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
                        $this->_banner_message = 'Produkt został pomyślnie dodany do koszyka';
                    }
                }

                // Sprawdzanie, czy dany element istnieje w tablicy
                if ($isElementInArray == true) {
                    // Dodanie nowego elementu do tablicy i przypisanie mu kolejno wartości.
                    array_push($tempArray, array('dishid' => $dish_id, 'count' => $il, 'resid' => $res_id));
                    setcookie('dishes', json_encode($tempArray), time() + (86400 * 30), "/");
                } else
                    setcookie('dishes', json_encode($new_json_array), time() + (86400 * 30), "/");
            }
            // Jeżeli plik cookies nie został jeszcze utworzony, dodajemy elementy do tablicy i tworzymy nowe cookies. 
            else {
                array_push($tempArray, array('dishid' => $dish_id, 'count' => $il, 'resid' => $res_id));
                setcookie('dishes', json_encode($tempArray), time() + (86400 * 30), "/");
                $this->_banner_message = 'Produkt został pomyślnie dodany do koszyka';
            }
            
            SessionHelper::create_session_banner(SessionHelper::ORDER_FINISH_PAGE, $this->_banner_message, false);
            header('Location:' . __URL_INIT_DIR__ . 'restaurants/restaurant-details?id='. $res_id, true, 301);
            
            $statement->closeCursor();
            $this->dbh->commit();

        } catch (Exception $e) {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::HOME_RESTAURANTS_LIST_PAGE_BANNER, $e->getMessage(), true);
        }
        return $_GET['resid'];
    }

}
