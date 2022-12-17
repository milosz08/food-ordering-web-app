<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: RestaurantService.php                          *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-11-27, 20:00:52                       *
 * Autor: cptn3m012                                            *
 *                                                             *
 * Ostatnia modyfikacja: 2022-12-17 16:59:07                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services;

use PDO;
use Exception;

use App\Utils\Utils;
use App\Core\Config;
use App\Core\MvcService;
use App\Models\RestaurantModel;

class RestaurantService extends MvcService
{
    private $_banner_message;
    private $_if_banner_error;

    //--------------------------------------------------------------------------------------------------------------------------------------

    protected function __construct()
    {
        parent::__construct();
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda odpowiadająca za dodawanie danych nowej restauracji oraz sprawdzanie ich z istniejącą bazą danych.
     * Jeśli restauracja została pomyślnie dodana następuje (tymczasowo) przekierowanie do strony głównej.
     */
    public function add_restaurant()
    {
        if (isset($_POST['restaurant-button']))
        {
            try
            {
                $v_name = Utils::validate_field_regex('restaurant-name', '/^[a-zA-Z0-9ąćęłńóśźżĄĆĘŁŃÓŚŹŻ\-\/%@$: ]{2,50}$/');
                $v_price = Utils::validate_field_regex('restaurant-delivery-price', Config::get('__REGEX_PRICE__'));
                $v_building_no = Utils::validate_field_regex('restaurant-building-no', Config::get('__REGEX_BUILDING_NO__'));
                $v_post_code = Utils::validate_field_regex('restaurant-post-code', Config::get('__REGEX_POSTCODE__'));
                $v_city = Utils::validate_field_regex('restaurant-city', Config::get('__REGEX_CITY__'));
                $v_street = Utils::validate_field_regex('restaurant-street', Config::get('__REGEX_STREET__'));
                $v_banner = Utils::validate_image_regex('restaurant-banner');
                $v_profile = Utils::validate_image_regex('restaurant-profile');
                $v_description = Utils::validate_field_regex('restaurant-description', Config::get('__REGEX_DESCRIPTION__'));
                
                $this->dbh->beginTransaction();

                if (!($v_name['invl'] || $v_price['invl'] || $v_banner['invl'] || $v_profile['invl'] || $v_street['invl'] || 
                      $v_building_no['invl'] ||$v_post_code['invl'] || $v_city['invl'] || $v_description['invl'])) 
                {
                    // Zapytanie zwracające liczbę istniejących już restauracji o podanej nazwie
                    $query = "
                        SELECT COUNT(id) FROM restaurants
                        WHERE street = ? AND building_locale_nr = ? AND post_code = ? AND city = ?
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $v_street['value'], $v_building_no['value'], $v_post_code['value'], $v_city['value']
                    ));

                    if ($statement->fetchColumn() > 0)
                        throw new Exception('Podana restauracja istnieje już w tym miejscu. Podaj inne dane adresowe.');

                    $v_price = str_replace(',', '.', $v_price);
                    
                    // Sekcja zapytań dodająca wprowadzone dane do tabeli restaurants
                    $query = "
                        INSERT INTO restaurants (name, delivery_price, street, building_locale_nr, post_code, city, description, user_id)
                        VALUES (?,?,?,?,?,?,?,?)
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $v_name['value'], $v_price['value'], $v_street['value'], $v_building_no['value'], $v_post_code['value'],
                        $v_city['value'], $v_description['value'], $_SESSION['logged_user']['user_id'],
                    ));
                    // Sekcja zapytań zwracająca id ostatnio dodanej restauracji
                    $query = "SELECT id FROM restaurants ORDER BY id DESC LIMIT 1";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute();
                    $thisRestaurant = $statement->fetchAll(PDO::FETCH_ASSOC);
                    $id_image = $thisRestaurant[0]['id'];

                    $photos = Utils::create_images_if_not_exist($id_image, $v_profile, $v_banner);
                    // Sekcja zapytań uzupełniająca url zdjęcia oraz baneru
                    $query = "UPDATE restaurants SET baner_url = ?, profile_url = ? WHERE id = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($photos['banner'], $photos['profile'], $id_image));

                    $statement->closeCursor();
                    $this->_banner_message = 'Restauracja została pomyślnie utworzona. Teraz czeka na zatwierdzenie administratora.';
                    $_SESSION['manipulate_restaurant_banner'] = array(
                        'banner_message' => $this->_banner_message,
                        'show_banner' => !empty($this->_banner_message),
                        'banner_class' => 'alert-success',
                    );
                    header('Location:' . __URL_INIT_DIR__ . 'restaurant/panel/myrestaurants', true, 301);
                }
                $this->dbh->commit();
            } 
            catch (Exception $e) 
            {
                $this->dbh->rollback();
                $this->_banner_message = $e->getMessage();
            }
            return array(
                'v_name' => $v_name,
                'v_price' => $v_price,
                'v_banner' => $v_banner,
                'v_profile' => $v_profile,
                'v_street' => $v_street,
                'v_building_no' => $v_building_no,
                'v_post_code' => $v_post_code,
                'v_city' => $v_city,
                'v_description' => $v_description,
                'error' => $this->_banner_message,
            );
        }
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    public function edit_restaurant()
    {
        $v_banner = array('invl' => false, 'bts_class' => '');
        $v_profile = array('invl' => false, 'bts_class' => '');
        try
        {
            if (!isset($_GET['id'])) header('Location:' . __URL_INIT_DIR__ . 'restaurant/panel/myrestaurants', true, 301);
            $this->dbh->beginTransaction();

            // Zapytanie zwracające aktualne wartości edytowanej restauracji z bazy danych
            $query = "SELECT * FROM restaurants WHERE id = ? AND user_id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id'], $_SESSION['logged_user']['user_id']));
            $restaurant = $statement->fetchAll(PDO::FETCH_ASSOC);
            if (count($restaurant) == 0) header('Location:' . __URL_INIT_DIR__ . 'restaurant/panel/myrestaurants', true, 301);
            
            $v_name = array('value' => $restaurant[0]['name'], 'invl' => false, 'bts_class' => '');
            $v_street = array('value' => $restaurant[0]['street'], 'invl' => false, 'bts_class' => '');
            $v_building_no = array('value' => $restaurant[0]['building_locale_nr'], 'invl' => false, 'bts_class' => '');
            $v_post_code = array('value' => $restaurant[0]['post_code'], 'invl' => false, 'bts_class' => '');
            $v_city = array('value' => $restaurant[0]['city'], 'invl' => false, 'bts_class' => '');
            $v_price = array('value' => $restaurant[0]['delivery_price'], 'invl' => false, 'bts_class' => '');
            $v_description = array('value' => $restaurant[0]['description'], 'invl' => false, 'bts_class' => '');

            if (isset($_POST['restaurant-button']))
            {
                $v_name = Utils::validate_field_regex('restaurant-name', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{2,50}$/');
                $v_price = Utils::validate_field_regex('restaurant-delivery-price', '/^([1-9][0-9]*|0)(\,[0-9]{2})?$/');
                $v_banner = Utils::validate_image_regex('restaurant-banner');
                $v_profile = Utils::validate_image_regex('restaurant-profile');
                $v_street = Utils::validate_field_regex('restaurant-street', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ ]{2,100}$/');
                $v_building_no = Utils::validate_field_regex('restaurant-building-no', '/^[0-9]{1,5}$/');
                $v_post_code = Utils::validate_field_regex('restaurant-post-code', '/^[0-9]{2}-[0-9]{3}$/');
                $v_city = Utils::validate_field_regex('restaurant-city', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{2,60}$/');
                $v_description = Utils::validate_field_regex('restaurant-description', Config::get('__REGEX_DESCRIPTION__'));

                if (!($v_name['invl'] || $v_price['invl'] || $v_banner['invl'] || $v_profile['invl'] || $v_street['invl'] ||
                      $v_building_no['invl'] || $v_post_code['invl'] || $v_city['invl'] || $v_description['invl']))
                {
                    // Zapytanie zwracające liczbę istniejących już restauracji o podanej nazwie
                    $query = "
                        SELECT COUNT(id) FROM restaurants
                        WHERE street = ? AND building_locale_nr = ? AND post_code = ? AND city = ? AND NOT id = ?
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $v_street['value'], $v_building_no['value'], $v_post_code['value'], $v_city['value'], $_GET['id']
                    ));

                    if ($statement->fetchColumn() > 0)
                        throw new Exception('Podana restauracja istnieje już w tym miejscu. Podaj inne dane adresowe.');

                    $photos = Utils::create_images_if_not_exist($_GET['id'], $v_profile, $v_banner);
                    // Sekcja zapytań aktualizujących pola w tabeli
                    $v_price = str_replace(',', '.', $v_price);
                    $query = "
                        UPDATE restaurants SET name = ?, delivery_price = ?, street = ?, building_locale_nr = ?, 
                        post_code = ?, city = ?, baner_url = ?, profile_url = ?, description = ?
                        WHERE id = ?
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $v_name['value'], $v_price['value'], $v_street['value'], $v_building_no['value'], $v_post_code['value'],
                        $v_city['value'], $photos['banner'], $photos['profile'], $v_description['value'], $_GET['id']
                    ));
                    $statement->closeCursor();
                    $this->_banner_message = 'Pomyślnie wprowadzono nowe dane dla restauracji <strong>' . $v_name['value'] . '</strong>.';

                    $_SESSION['manipulate_restaurant_banner'] = array(
                        'banner_message' => $this->_banner_message,
                        'show_banner' => !empty($this->_banner_message),
                        'banner_class' => 'alert-success',
                    );
                    header('Location:' . __URL_INIT_DIR__ . 'restaurant/panel/myrestaurants', true, 301);
                }
            }
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $this->_banner_message = $e->getMessage();
        }
        return array(
            'v_name' => $v_name,
            'v_price' => $v_price,
            'v_banner' => $v_banner,
            'v_profile' => $v_profile,
            'v_street' => $v_street,
            'v_building_no' => $v_building_no,
            'v_post_code' => $v_post_code,
            'v_city' => $v_city,
            'v_description' => $v_description,
            'error' => $this->_banner_message,
        );
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda odpowiadająca za pobranie szczegółów wybranej restauracji z bazy danych i zwrócenie ich do widoku. Jeśli nie znajdzie
     * restauracji z podanym ID przypisanym do użytkownika, przekierowanie do strony z listą restauracji.
     */
    public function get_restaurant_details()
    {
        return array(
            'res_id' => $_GET['id'] ?? 'brak id',
        );
    }

    //--------------------------------------------------------------------------------------------------------------------------------------
    
    /**
     * Metoda odpowiadająca za usuwanie obecnej restauracji.
     * Jeśli restauracja została pomyślnie usunięta następuje (tymczasowo) przekierowanie do strony głównej.
     * dorobienie weryfikacji id podczas sesji
     */
    public function delete_restaurant()
    {
        if (!isset($_GET['id'])) header('Location:' . __URL_INIT_DIR__ . 'restaurant/panel/myrestaurants', true, 301);
        try
        {
            $this->dbh->beginTransaction();

            $query = "SELECT COUNT(*) FROM restaurants WHERE id = ? AND user_id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id'], $_SESSION['logged_user']['user_id']));

            if ($statement->fetchColumn() == 0) throw new Exception('Podana resturacja nie istnieje w systemie lub została już usunięta.');

            $query = "DELETE FROM restaurants WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));

            $this->_banner_message = 'Pomyślnie usuniętą wybraną restaurację z systemu.';
            $statement->closeCursor();
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $this->_banner_message = $e->getMessage();
            $this->_if_banner_error = true;
        }
        $_SESSION['manipulate_restaurant_banner'] = array(
            'banner_message' => $this->_banner_message,
            'show_banner' => !empty($this->_banner_message),
            'banner_class' => $this->_if_banner_error ? 'alert-danger' : 'alert-success',
        );
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

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
        $pagination_visible = true; // widoczność paginacji
        try
        {
            $this->dbh->beginTransaction();

            $curr_page = $_GET['page'] ?? 1; // pobranie indeksu paginacji
            $page = ($curr_page - 1) * 5;
            $total_per_page = $_GET['total'] ?? 5;
            $search_text = $_POST['search-res-name'] ?? '';

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
            
            // zapytanie zliczające wszystkie restauracje przypisane do użytkownika
            $query = "SELECT count(*) FROM restaurants WHERE user_id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));

            $total_pages = ceil($statement->fetchColumn() / $total_per_page);
            for ($i = 1; $i <= $total_pages; $i++) array_push($pagination, array(
                'it' => $i,
                'url' => 'restaurant/panel/myrestaurants?page=' . $i . '&total=' . $total_per_page, 
                'selected' => $curr_page ==  $i ? 'active' : '',
            ));

            $pages_nav = Utils::get_pagination_nav($curr_page, $total_per_page, $total_pages, 'restaurant/panel/myrestaurants');
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $pagination_visible = false;
            $_SESSION['manipulate_restaurant_banner'] = array(
                'banner_message' => $e->getMessage(),
                'show_banner' => !empty($e->getMessage()),
                'banner_class' => 'alert-danger',
            );
        }
        return array(
            'total_per_page' => $total_per_page,
            'pagination_url' => 'restaurant/panel/myrestaurants',
            'pagination' => $pagination,
            'pagination_visible' => $pagination_visible,
            'pages_nav' => $pages_nav,
            'user_restaurants' => $user_restaurants,
            'search_text' => $search_text,
        );
    }
}
