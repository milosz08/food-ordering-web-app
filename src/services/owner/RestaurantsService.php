<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: RestaurantsService.php                         *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-03, 00:04:58                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-16 03:04:12                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Owner\Services;

use PDO;
use Exception;
use App\Core\Config;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\DishModel;
use App\Models\RestaurantModel;
use App\Models\RestaurantHourModel;
use App\Models\RestaurantDetailsModel;
use App\Models\AddEditRestaurantModel;
use App\Models\DiscountResDetailsModel;
use App\Services\Helpers\ImagesHelper;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\PaginationHelper;
use App\Services\Helpers\ValidationHelper;
use App\Services\Helpers\RestaurantsHelper;

ResourceLoader::load_model('DishModel', 'dish');
ResourceLoader::load_model('RestaurantModel', 'restaurant');
ResourceLoader::load_model('RestaurantHourModel', 'restaurant');
ResourceLoader::load_model('RestaurantDetailsModel', 'restaurant');
ResourceLoader::load_model('AddEditRestaurantModel', 'restaurant');
ResourceLoader::load_model('DiscountResDetailsModel', 'discount');
ResourceLoader::load_service_helper('ImagesHelper');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('PaginationHelper');
ResourceLoader::load_service_helper('ValidationHelper');
ResourceLoader::load_service_helper('RestaurantsHelper');

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

    /**
     * Metoda odpowiadająca za tworzenie tabeli w zakładce 'Lista restauracji'.
     * Tabela przechowuje kolejno wszystkie restauracje, które posiada zalogowany użytkownik.
     * Tabela przechowuje poszczególne informacje o restauracji, a także przyciski odpowiadające za przejście
     * do zakładki edytowania wybranej restauracji oraz jej usunięcie. Tabela została wzbogacona o funkcję paginacji, 
     * wyświetlającej tylko 6 elementów na jednej ze stron.
     */
    public function get_user_restaurants()
    {
        $pagination = array(); // tablica przechowująca liczby przekazywane do dynamicznego tworzenia elementów paginacji
        $user_restaurants = array();
        $pages_nav = array();
        $not_empty = false;
        try
        {
            $this->dbh->beginTransaction();

            $curr_page = $_GET['page'] ?? 1; // pobranie indeksu paginacji
            $page = ($curr_page - 1) * 10;
            $total_per_page = $_GET['total'] ?? 10;
            $search_text = SessionHelper::persist_search_text('search-res-name', SessionHelper::OWNER_RES_SEARCH);
            
            $redirect_url = 'owner/restaurants';
            PaginationHelper::check_parameters('owner/restaurants');

            // zapytanie do bazy danych, które zwróci poszczególne wartości wszystkich restauracji dla obecnie zalogowanego użytkownika
            $query = "
                SELECT ROW_NUMBER() OVER(ORDER BY id) as it, name, accept, id,
                CONCAT('ul. ', street, ' ', building_locale_nr, ', ', post_code, ' ', city) AS address
                FROM restaurants WHERE user_id = :id AND name LIKE :search LIMIT :total OFFSET :page
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('id', $_SESSION['logged_user']['user_id']);
            $statement->bindValue('search', '%' . $search_text . '%');
            $statement->bindValue('total', $total_per_page, PDO::PARAM_INT);
            $statement->bindValue('page', $page, PDO::PARAM_INT);
            $statement->execute();

            while ($row = $statement->fetchObject(RestaurantModel::class)) array_push($user_restaurants, $row);
            $not_empty = count($user_restaurants);
            
            // zapytanie zliczające wszystkie restauracje przypisane do użytkownika
            $query = "SELECT count(*) FROM restaurants WHERE user_id = :id AND name LIKE :search";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('id', $_SESSION['logged_user']['user_id']);
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
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::RESTAURANTS_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'total_per_page' => $total_per_page,
            'pagination_url' => 'owner/restaurants?',
            'pagination' => $pagination,
            'pages_nav' => $pages_nav,
            'user_restaurants' => $user_restaurants,
            'search_text' => $search_text,
            'not_empty' => $not_empty,
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda odpowiadająca za dodawanie danych nowej restauracji oraz sprawdzanie ich z istniejącą bazą danych.
     * Jeśli restauracja została pomyślnie dodana następuje (tymczasowo) przekierowanie do strony głównej.
     */
    public function add_restaurant()
    {
        $res = new AddEditRestaurantModel;
        $res_hours = array();
        try
        {
            $this->dbh->beginTransaction();
            // pobieranie wszystkich dni tygodnia
            $query = "SELECT w.name, w.name_eng AS identifier FROM weekdays AS w ORDER BY w.id";
            $statement = $this->dbh->prepare($query);
            $statement->execute();
            while ($row = $statement->fetchObject(RestaurantHourModel::class)) array_push($res_hours, $row);

            if (isset($_POST['restaurant-button']))
            {
                $res->name = ValidationHelper::validate_field_regex('restaurant-name', '/^[a-zA-Z0-9ąćęłńóśźżĄĆĘŁŃÓŚŹŻ\-\/%@$: ]{2,50}$/');
                $res->delivery_price = ValidationHelper::check_optional('restaurant-delivery-price', 'restaurant-delivery-free', Config::get('__REGEX_PRICE__'));
                $res->min_price = ValidationHelper::check_optional('restaurant-min-price', 'restaurant-no-min-price', Config::get('__REGEX_PRICE__'));
                $res->building_locale_nr = ValidationHelper::validate_field_regex('restaurant-building-no', Config::get('__REGEX_BUILDING_NO__'));
                $res->post_code = ValidationHelper::validate_field_regex('restaurant-post-code', Config::get('__REGEX_POSTCODE__'));
                $res->city = ValidationHelper::validate_field_regex('restaurant-city', Config::get('__REGEX_CITY__'));
                $res->street = ValidationHelper::validate_field_regex('restaurant-street', Config::get('__REGEX_STREET__'));
                $res->banner_url = ValidationHelper::validate_image_regex('restaurant-banner');
                $res->profile_url = ValidationHelper::validate_image_regex('restaurant-profile');
                $res->description = ValidationHelper::validate_field_regex('restaurant-description', Config::get('__REGEX_DESCRIPTION__'));
                $res->phone_number = ValidationHelper::validate_field_regex('restaurant-phone', Config::get('__REGEX_PHONE_PL__'));
                foreach ($res_hours as $res_hour) ValidationHelper::validate_hour($res_hour); // walidacja godzin
                $all_hours_valid = true; foreach ($res_hours as $res_hour) $all_hours_valid = $res_hour->all_hours_is_valid();

                if ($res->all_is_valid() && $all_hours_valid)
                {
                    $this->check_if_phone_number_exist($res->phone_number['value']);
                    // Zapytanie zwracające liczbę istniejących już restauracji o podanej nazwie
                    $query = "
                        SELECT COUNT(id) FROM restaurants
                        WHERE street = ? AND building_locale_nr = ? AND post_code = ? AND city = ?
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $res->street['value'], $res->building_locale_nr['value'], $res->post_code['value'], $res->city['value']
                    ));
                    if ($statement->fetchColumn() > 0) throw new Exception('
                        Podana restauracja istnieje już w tym miejscu. Podaj inne dane adresowe.
                    ');

                    // Sekcja zapytań dodająca wprowadzone dane do tabeli restaurants
                    $query = "
                        INSERT INTO restaurants 
                        (name, delivery_price, min_price street, building_locale_nr, post_code, city, description, phone_number, user_id)
                        VALUES (?,
                        NULLIF(CAST(REPLACE(?, ',', '.') AS DECIMAL(10,2)),''),
                        NULLIF(CAST(REPLACE(?, ',', '.') AS DECIMAL(10,2)),''),
                        ?,?,?,?,?,REPLACE(?,' ',''),?)
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $res->name['value'], $res->delivery_price['value'], $res->min_price['value'], $res->street['value'],
                        $res->building_locale_nr['value'], $res->post_code['value'], $res->city['value'], $res->description['value'],
                        $res->phone_number['value'],$_SESSION['logged_user']['user_id'],
                    ));

                    // Sekcja zapytań zwracająca id ostatnio dodanej restauracji
                    $query = "SELECT LAST_INSERT_ID()";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute();
                    $rest_id = $statement->fetchColumn();

                    foreach ($res_hours as $res_hour) // dodaj szczegóły na temat otwarcia i zamknięcia restauracji
                    {
                        // jeśli restauracja w danym dniu tygodnia ma status zamknięty, nie dodawaj nic
                        if ($res_hour->is_closed == 'checked') continue;
                        $query = "SELECT id FROM weekdays WHERE name = ?";
                        $statement = $this->dbh->prepare($query);
                        $statement->execute(array($res_hour->name));
                        $weekday_id = $statement->fetchColumn();
                                
                        $query = "INSERT INTO restaurant_hours (open_hour, close_hour, weekday_id, restaurant_id) VALUES (?,?,?,?)";
                        $statement = $this->dbh->prepare($query);
                        $statement->execute(array(
                            $res_hour->open_hour['value'], $res_hour->close_hour['value'], $weekday_id, $rest_id
                        ));
                    }
                    $photos = ImagesHelper::upload_restaurant_images($res->profile_url, $res->banner_url, $rest_id);

                    // Sekcja zapytań uzupełniająca url zdjęcia oraz baneru
                    $query = "UPDATE restaurants SET banner_url = NULLIF(?,''), profile_url = NULLIF(?,'') WHERE id = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($photos['banner'], $photos['profile'], $rest_id));

                    // aktulizowanie adresów profilu i banera w zmiennych po pobraniu zmienionych wartości w bazie danych
                    $query = "SELECT profile_url, banner_url FROM restaurants WHERE id = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($rest_id));
                    $images = $statement->fetch(PDO::FETCH_ASSOC);
                    $res->profile_url['value'] = $images['profile_url'];
                    $res->banner_url['value'] = $images['banner_url'];

                    // wysyłanie wiadomości email do tego co stworzył restaurację i do administratorów o utworzonej nowej restauracji,
                    // która czeka na zatwierdzenie

                    $statement->closeCursor();
                    $this->_banner_message = '
                        Restauracja została pomyślnie utworzona i przeszła w stan oczekiwania na zatwierdzenie administratora systemu.
                    ';
                    SessionHelper::create_session_banner(SessionHelper::RESTAURANTS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
                    header('Refresh:0; url=' . __URL_INIT_DIR__ . 'owner/restaurants', true, 301);
                    die;
                }
            }
            $statement->closeCursor();
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $res->profile_url['value'] = '';
            $res->banner_url['value'] = '';
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::ADD_EDIT_RESTAURANT_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'res' => $res,
            'is_delivery_free' => isset($_POST['restaurant-delivery-free']) ? 'checked' : '',
            'is_no_min_price' => isset($_POST['restaurant-no-min-price']) ? 'checked' : '',
            'res_hours' => $res_hours,
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda odpowiadająca za edytowanie istniejącej już restauracji przez właściciela restauracji na podstawie id restauracji.
     */
    public function edit_restaurant()
    {
        $res = new AddEditRestaurantModel;
        $res_hours = array();
        try
        {
            if (!isset($_GET['id'])) header('Location:' . __URL_INIT_DIR__ . 'owner/restaurants', true, 301);
            $this->dbh->beginTransaction();

            // Zapytanie zwracające aktualne wartości edytowanej restauracji z bazy danych
            $query = "
                SELECT name, street, building_locale_nr, post_code, city, banner_url, profile_url, description,
                REPLACE(CAST(delivery_price as DECIMAL(10,2)), '.', ',') AS delivery_price,
                IFNULL(delivery_price, '') AS delivery_free,
                CONCAT(SUBSTRING(phone_number, 1, 3), ' ', SUBSTRING(phone_number, 3, 3), ' ', SUBSTRING(phone_number, 6, 3)) AS phone_number,
                REPLACE(IFNULL(min_price, ''), '.', ',') AS min_price
                FROM restaurants
                WHERE id = ? AND user_id = ?
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id'], $_SESSION['logged_user']['user_id']));
            $res = $statement->fetchObject(AddEditRestaurantModel::class);
            if (!$res) header('Location:' . __URL_INIT_DIR__ . 'owner/restaurants', true, 301);
            $profile_photo = $res->profile_url['value'];
            $banner_photo = $res->banner_url['value'];

            $res_hours = $this->get_restaurant_weekdays_and_hours();
            $is_delivery_free = empty($res->delivery_free) ? 'checked' : '';
            $is_min_price = empty($res->min_price) ? 'checked' : '';
            if (isset($_POST['restaurant-button']))
            {
                $res->name = ValidationHelper::validate_field_regex('restaurant-name', '/^[a-zA-Z0-9ąćęłńóśźżĄĆĘŁŃÓŚŹŻ\-\/%@$: ]{2,50}$/');
                $res->delivery_price = ValidationHelper::check_optional('restaurant-delivery-price', 'restaurant-delivery-free', Config::get('__REGEX_PRICE__'));
                $res->min_price = ValidationHelper::check_optional('restaurant-min-price', 'restaurant-no-min-price', Config::get('__REGEX_PRICE__'));
                $res->banner_url = ValidationHelper::validate_image_regex('restaurant-banner');
                $res->profile_url = ValidationHelper::validate_image_regex('restaurant-profile');
                $res->street = ValidationHelper::validate_field_regex('restaurant-street', Config::get('__REGEX_STREET__'));
                $res->building_no = ValidationHelper::validate_field_regex('restaurant-building-no', Config::get('__REGEX_BUILDING_NO__'));
                $res->post_code = ValidationHelper::validate_field_regex('restaurant-post-code', Config::get('__REGEX_POSTCODE__'));
                $res->city = ValidationHelper::validate_field_regex('restaurant-city', Config::get('__REGEX_CITY__'));
                $res->description = ValidationHelper::validate_field_regex('restaurant-description', Config::get('__REGEX_DESCRIPTION__'));
                $res->phone_number = ValidationHelper::validate_field_regex('restaurant-phone', Config::get('__REGEX_PHONE_PL__'));
                foreach ($res_hours as $res_hour) ValidationHelper::validate_hour($res_hour); // walidacja godzin
                $all_hours_valid = true; foreach ($res_hours as $res_hour) $all_hours_valid = $res_hour->all_hours_is_valid();

                $is_delivery_free = isset($_POST['restaurant-delivery-free']) ? 'checked' : '';
                $is_min_price = isset($_POST['restaurant-no-min-price']) ? 'checked' : '';
                if ($res->all_is_valid() && $all_hours_valid)
                {
                    // Zapytanie zwracające liczbę istniejących już restauracji o podanej nazwie
                    $query = "
                        SELECT COUNT(*) FROM restaurants
                        WHERE street = ? AND building_locale_nr = ? AND post_code = ? AND city = ? AND NOT id = ?
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $res->street['value'], $res->building_no['value'], $res->post_code['value'], $res->city['value'], $_GET['id']
                    ));
                    if ($statement->fetchColumn() > 0) throw new Exception('
                        Podana restauracja istnieje już w tym miejscu. Podaj inne dane adresowe.
                    ');
                    $this->check_if_phone_number_exist($res->phone_number['value']);

                    foreach ($res_hours as $res_hour)
                    {
                        // sprawdź, czy dzień tygodnia jest już wpisany do tabeli, jeśli tak zwróć id
                        $query = "
                            SELECT h.id FROM restaurant_hours AS h 
                            INNER JOIN weekdays AS w ON h.weekday_id = w.id WHERE alias = ? AND restaurant_id = ?
                        ";
                        $statement = $this->dbh->prepare($query);
                        $statement->execute(array($res_hour->alias, $_GET['id']));
                        $hour_id = $statement->fetchColumn();
                        // jeśli restauracja w danym dniu tygodnia ma status zamknięty, oraz jeśli rekord istnieje, usuń
                        if ($res_hour->is_closed == 'checked')
                        {
                            $query = "DELETE FROM restaurant_hours WHERE id = ?";
                            $statement = $this->dbh->prepare($query);
                            $statement->execute(array($hour_id));
                        }
                        else // jeśli restauracja normalnie jest otwarta
                        {
                            if ($hour_id) // edytuj ten, który już istnieje
                            {
                                $query = "UPDATE restaurant_hours SET open_hour = ?, close_hour = ? WHERE id = ?";
                                $statement = $this->dbh->prepare($query);
                                $statement->execute(array($res_hour->open_hour['value'], $res_hour->close_hour['value'], $hour_id));
                            }
                            else // dodaj nowy dzień tygodnia
                            {
                                $query = "SELECT id FROM weekdays WHERE alias = ?";
                                $statement = $this->dbh->prepare($query);
                                $statement->execute(array($res_hour->alias));
                                $weekday_id = $statement->fetchColumn();
                                
                                $query = "INSERT INTO restaurant_hours (open_hour, close_hour, weekday_id, restaurant_id) VALUES (?,?,?,?)";
                                $statement = $this->dbh->prepare($query);
                                $statement->execute(array(
                                    $res_hour->open_hour['value'], $res_hour->close_hour['value'], $weekday_id, $_GET['id']
                                ));
                            }
                        }
                    }
                    $photos = ImagesHelper::upload_restaurant_images(
                        $res->profile_url, $res->banner_url, $_GET['id'], $profile_photo, $banner_photo
                    );
                    // Sekcja zapytań aktualizujących pola w tabeli
                    $query = "
                        UPDATE restaurants SET name = ?,
                        delivery_price = NULLIF(CAST(REPLACE(?, ',', '.') AS DECIMAL(10,2)),''), street = ?, 
                        building_locale_nr = ?, post_code = ?, city = ?, banner_url = NULLIF(?,''), profile_url = NULLIF(?,''),
                        description = ?, phone_number = REPLACE(?,' ',''),
                        min_price = NULLIF(CAST(REPLACE(?, ',', '.') AS DECIMAL(10,2)),'')
                        WHERE id = ?
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $res->name['value'], $res->delivery_price['value'], $res->street['value'], $res->building_no['value'], 
                        $res->post_code['value'], $res->city['value'], $photos['banner'], $photos['profile'], $res->description['value'], 
                        $res->phone_number['value'], $res->min_price['value'], $_GET['id'],
                    ));

                    // aktulizowanie adresów profilu i banera w zmiennych po pobraniu zmienionych wartości w bazie danych
                    $query = "SELECT profile_url, banner_url FROM restaurants WHERE id = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($_GET['id']));
                    $images = $statement->fetch(PDO::FETCH_ASSOC);
                    $res->profile_url['value'] = $images['profile_url'];
                    $res->banner_url['value'] = $images['banner_url'];

                    $statement->closeCursor();
                    $this->_banner_message = 'Pomyślnie wprowadzono nowe dane dla restauracji <strong>' . $res->name['value'] . '</strong>.';
                    $this->dbh->commit();

                    SessionHelper::create_session_banner(SessionHelper::RESTAURANTS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
                    header('Refresh:0; url=' . __URL_INIT_DIR__ . 'owner/restaurants', true, 301);
                    die;
                }
                else
                {
                    $res->profile_url['value'] = $profile_photo;
                    $res->banner_url['value'] = $banner_photo;
                }
            }
            $statement->closeCursor();
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::ADD_EDIT_RESTAURANT_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'res' => $res,
            'res_id' => $_GET['id'],
            'res_hours' => $res_hours,
            'is_delivery_free' => $is_delivery_free,
            'is_no_min_price' => $is_min_price,
            'has_profile' => !empty($res->profile_url['value']),
            'has_banner' => !empty($res->banner_url['value']),
            'hide_profile_preview_class' => $res->profile_url['invl'] ? 'display-none' : '',
            'hide_banner_preview_class' => $res->banner_url['invl'] ? 'display-none' : '',
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda odpowiadająca za usuwanie obecnej restauracji. Można usunąć jedynie restaurację, która nie posiada aktywnych zamówień.
     * Jeśli restauracja została pomyślnie usunięta następuje przekierowanie do strony z restauracjami.
     */
    public function delete_restaurant()
    {
        if (!isset($_GET['id'])) return;
        try
        {
            $this->dbh->beginTransaction();
            RestaurantsHelper::check_if_restaurant_exist($this->dbh, 'id', '');
            $query = "SELECT COUNT(*) FROM orders WHERE restaurant_id = ? AND status_id = 1";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));
            if ($statement->fetchColumn() != 0) throw new Exception('
                Wybrana restauracja nie istnieje bądź posiada aktywne zamówienia. Z systemu możesz usunąć jednynie te resturacje, które
                nie mają w obecnej chwili aktywnych zamówień
            ');
            $query = "DELETE FROM restaurants WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));

            // wysyłanie wiadomości email do tego co usunął restaurację i do administratorów systemu z informacją o usunięciu restauracji
            // i jej aktualnym statusie (aktywna/w oczekiwaniu)

            rmdir('uploads/restaurants/' . $_GET['id']);
            $this->_banner_message = 'Pomyślnie usunięto wybraną restaurację z systemu.';
            $statement->closeCursor();
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $this->_banner_message = $e->getMessage();
            $this->_banner_error = true;
        }
        SessionHelper::create_session_banner(SessionHelper::RESTAURANTS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda usuwająca zdjęcie w tle (baner) lub zdjęcie profilowe restauracji wybranej na podstawie id przekazywanego w parametrze GET 
     * zapytania oraz parametrów metody. Ustawia również wartość NULL w kolumnie przechowującej link do grafiki.
     */
    public function delete_restaurant_image($image_column_name, $deleted_type)
    {
        $redirect_url = 'owner/restaurants';
        if (!isset($_GET['id'])) return $redirect_url;
        try
        {
            $this->dbh->beginTransaction();
            $query = "SELECT COUNT(*) FROM restaurants WHERE id = ? AND user_id AND accept = 1";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id'], ));
            if ($statement->fetchColumn() == 0) throw new Exception('
                Wybrana restuaracja nie istnieje lub nie jest przypisana to Twojego konta.
            ');
            $redirect_url .= '/edit-restaurant?id=' . $_GET['id'];

            $query = "SELECT $image_column_name FROM restaurants WHERE id = ? AND user_id AND accept = 1";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id'], $_SESSION['logged_user']['user_id']));
            $result = $statement->fetchColumn();
            if (!$result) throw new Exception('Wybrana restuaracja nie posiada typu zdjęcia: <strong>' . $image_column_name .  '</strong>.');

            $query = "UPDATE restaurants SET $image_column_name = NULL WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));

            if (file_exists($result)) unlink($result);
            $this->_banner_message = 'Pomyślnie usunięto ' . $deleted_type . ' z wybranej restauracji z systemu.';
            $statement->closeCursor();
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $this->_banner_error = true;
            SessionHelper::create_session_banner(SessionHelper::RESTAURANTS_PAGE_BANNER, $e->getMessage(), true);
        }
        if (!$this->_banner_error)
        {
            SessionHelper::create_session_banner(SessionHelper::ADD_EDIT_RESTAURANT_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
        }
        return $redirect_url;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda odpowiadająca za pobranie szczegółów dań wybranej restauracji z bazy danych i zwrócenie ich do widoku.
     */
    public function get_restaurant_details()
    {
        if (!isset($_GET['id'])) header('Location:' . __URL_INIT_DIR__ . 'owner/restaurants', true, 301);

        $restaurant_details = new RestaurantDetailsModel;
        $pagination = array();
        $restaurant_discounts = array();
        $restaurant_dishes = array();
        $res_hours = array();
        $pages_nav = array();
        try
        {
            $this->dbh->beginTransaction();

            $curr_page = $_GET['page'] ?? 1; // pobranie indeksu paginacji
            $page = ($curr_page - 1) * 10;
            $total_per_page = $_GET['total'] ?? 10;
    
            $search_code = SessionHelper::persist_search_text('search-discount-code', SessionHelper::DISCOUNT_SEARCH);
            $search_text = SessionHelper::persist_search_text('search-dish-name', SessionHelper::OWNER_RES_DETAILS_SEARCH);
            
            $redirect_url = 'owner/restaurants/restaurant-details?id=' . $_GET['id'];
            PaginationHelper::check_parameters($redirect_url);

            $restaurant_query = "
                SELECT r.id, name, accept, description, building_locale_nr, street, post_code, city,
                IFNULL(r.profile_url, 'static/images/default-profile.jpg') AS profile_url,
                IFNULL(r.banner_url, 'static/images/default-banner.jpg') AS banner_url,
                CONCAT(first_name, ' ', last_name) AS full_name,
                IF(delivery_price, CONCAT(REPLACE(CAST(delivery_price AS DECIMAL(10,2)), '.', ','), ' zł'), 'za darmo') AS delivery_price, 
                CONCAT('ul. ', street, ' ', building_locale_nr, ', ', post_code, ' ', city) AS address,
                (SELECT COUNT(*) FROM dishes WHERE restaurant_id = r.id) AS count_of_dishes,
                CONCAT(SUBSTRING(phone_number, 1, 3), ' ', SUBSTRING(phone_number, 3, 3), ' ', SUBSTRING(phone_number, 6, 3)) AS phone_number,
                IF(min_price, CONCAT(REPLACE(CAST(min_price AS DECIMAL(10,2)), '.', ','), ' zł'), 'brak najniższej ceny') AS min_price,
                IFNULL(NULLIF((SELECT COUNT(*) FROM discounts WHERE restaurant_id = r.id), 0), 'brak rabatów') AS discounts_count
                FROM restaurants AS r
                INNER JOIN users AS u ON r.user_id = u.id
                WHERE r.id = :id AND r.user_id = :userid
            ";
            $statement = $this->dbh->prepare($restaurant_query);
            $statement->bindValue('id', $_GET['id'], PDO::PARAM_INT);
            $statement->bindValue('userid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
            $statement->execute();
            $restaurant_details = $statement->fetchObject(RestaurantDetailsModel::class);
            if (!$restaurant_details)
            {
                $this->_banner_message = 'Wybrana restauracja nie istnieje lub nie jest przypisana do Twojego konta.';
                SessionHelper::create_session_banner(SessionHelper::RESTAURANTS_PAGE_BANNER, $this->_banner_message, true);
                $statement->closeCursor();
                $this->dbh->commit();
                header('Location:' . __URL_INIT_DIR__ . 'owner/restaurants');
            }
            $raw_res_hours = $this->get_restaurant_weekdays_and_hours();
            foreach ($raw_res_hours as $raw_res_hour) array_push($res_hours, $raw_res_hour->format_to_details_view()); 

            // zapytanie do bazy danych, które zwróci poszczególne dania dla obecnie wybranej restauracji
            $dishes_query = "
                SELECT ROW_NUMBER() OVER(ORDER BY d.id) as it, d.id, d.name, t.name AS type, d.description,
                CONCAT(REPLACE(CAST(d.price AS DECIMAL(10,2)), '.', ','), ' zł') AS price
                FROM dishes AS d
                INNER JOIN dish_types AS t ON dish_type_id = t.id
                WHERE restaurant_id = :id AND d.name LIKE :search LIMIT :total OFFSET :page
            ";
            $statement = $this->dbh->prepare($dishes_query);
            $statement->bindValue('id', $_GET['id']);
            $statement->bindValue('search', '%' . $search_text . '%');
            $statement->bindValue('total', $total_per_page, PDO::PARAM_INT);
            $statement->bindValue('page', $page, PDO::PARAM_INT);
            $statement->execute();
            while ($row = $statement->fetchObject(DishModel::class)) array_push($restaurant_dishes, $row);
            
            // zapytanie zliczające wszystkie dania przypisane do restauracji
            $query = "SELECT count(*) FROM dishes WHERE restaurant_id = :id AND name LIKE :search";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('id', $_GET['id']);
            $statement->bindValue('search', '%' . $search_text . '%');
            $statement->execute();
            $total_records = $statement->fetchColumn();

            $total_pages = ceil($total_records / $total_per_page);
            for ($i = 1; $i <= $total_pages; $i++) array_push($pagination, array(
                'it' => $i,
                'url' => $redirect_url . '&page=' . $i . '&total=' . $total_per_page, 
                'selected' => $curr_page ==  $i ? 'active' : '',
            ));

            PaginationHelper::check_if_page_is_greaten_than($redirect_url, $total_pages);
            $pages_nav = PaginationHelper::get_pagination_nav($curr_page, $total_per_page, $total_pages, $total_records, $redirect_url);
            
            $discounts_query = "
                SELECT ROW_NUMBER() OVER(ORDER BY id) as it, id, code, description,
                CONCAT(REPLACE(CAST(percentage_discount as DECIMAL(10,2)), '.', ','), '%') AS percentage_discount,
                CONCAT(usages, '/', IFNULL(max_usages, '∞')) AS total_usages,
                IF(max_usages, '', 'disabled') AS increase_usages_active, IF(expired_date, '', 'disabled') AS increase_time_active, 
                IFNULL(expired_date, '∞') AS expired_date, restaurant_id AS res_id, CONCAT(SUBSTRING(code, 1, 3), '********') AS hide_code,
                IF((SELECT COUNT(*) > 0 FROM discounts WHERE restaurant_id = d.restaurant_id AND ((expired_date > NOW() OR 
                expired_date IS NULL) AND (usages < max_usages OR max_usages IS NULL))), 'aktywny', 'wygasły') AS status,
                IF((SELECT COUNT(*) > 0 FROM discounts WHERE restaurant_id = d.restaurant_id AND ((expired_date > NOW() OR 
                expired_date IS NULL) AND (usages < max_usages OR max_usages IS NULL))), 'text-success', 'text-danger') AS expired_bts_class
                FROM discounts AS d WHERE restaurant_id = :id AND code LIKE :search
            ";
            $statement = $this->dbh->prepare($discounts_query);
            $statement->bindValue('id', $_GET['id']);
            $statement->bindValue('search', '%' . $search_code . '%');
            $statement->execute();
            while ($row = $statement->fetchObject(DiscountResDetailsModel::class)) array_push($restaurant_discounts, $row);

            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::RESTAURANT_DETAILS_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'total_per_page' => $total_per_page,
            'pagination_url' => 'owner/restaurants/restaurant-details?id=' . $_GET['id'] . '&',
            'pagination' => $pagination,
            'pages_nav' => $pages_nav,
            'restaurant_dishes' => $restaurant_dishes,
            'search_text' => $search_text,
            'details' => $restaurant_details,
            'not_empty' => count($restaurant_dishes),
            'res_hours' => $res_hours,
            'res_discounts' => $restaurant_discounts,
            'search_code' => $search_code,
            'discounts_not_empty' => count($restaurant_discounts),
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda sprawdzająca, czy przekazywany numer telefonu jest już przypisany do restauracji od innego właściciela (taki sam numer telefonu
     * może być przypisany jedynie do restauracji pochodzących od jednego właściciela).
     */
    private function check_if_phone_number_exist($phone_number)
    {
        $query = "SELECT COUNT(*) FROM restaurants WHERE phone_number = REPLACE(?, ' ', '') AND NOT user_id = ?";
        $statement = $this->dbh->prepare($query);
        $statement->execute(array($phone_number, $_SESSION['logged_user']['user_id']));
        if (!empty($statement->fetchColumn())) throw new Exception('
            Podany numer telefonu jest już przypisany do resturacji innego właściciela. Taki sam numer telefonu możesz przypisać jedynie
            do restauracji stworzonych przez siebie i przypisanych do Twojego konta.
        ');
        $statement->closeCursor();
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
}
