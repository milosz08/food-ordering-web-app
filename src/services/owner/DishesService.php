<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: DishesService.php                              *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-03, 16:21:27                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-09 18:32:07                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Owner\Services;

use PDO;
use Exception;
use App\Core\Config;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\AddEditDishModel;
use App\Models\DishDetailsModel;
use App\Models\DishRestaurantModel;
use App\Models\ActiveRestaurantModel;
use App\Services\Helpers\ImagesHelper;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\PaginationHelper;
use App\Services\Helpers\ValidationHelper;

ResourceLoader::load_model('AddEditDishModel', 'dish');
ResourceLoader::load_model('DishDetailsModel', 'dish');
ResourceLoader::load_model('DishRestaurantModel', 'dish');
ResourceLoader::load_model('ActiveRestaurantModel', 'restaurant');
ResourceLoader::load_service_helper('ImagesHelper');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('PaginationHelper');
ResourceLoader::load_service_helper('ValidationHelper');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class DishesService extends MvcService
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
     * Metoda zwracająca wszystkie dania stworzone przez użytkownika z informacją o przypisaniu dania do wybranej restauracji.
     */
    public function get_all_dishes()
    {
        $all_dishes = array();
        $not_empty = false;
        $pagination = array();
        $pages_nav = array();
        $pagination_visible = true;
        try
        {
            $this->dbh->beginTransaction();

            $curr_page = $_GET['page'] ?? 1;
            $page = ($curr_page - 1) * 5;
            $total_per_page = $_GET['total'] ?? 5;
            $search_text = $_POST['search-dish-name'] ?? '';

            $redirect_url = 'owner/dishes';
            PaginationHelper::check_parameters($redirect_url);

            $query = "
                SELECT ROW_NUMBER() OVER(ORDER BY d.id) AS it, d.id AS d_id, d.name AS d_name, t.name AS d_type, r.id AS r_id, 
                r.name AS r_name, r.description AS r_description, d.description AS d_description
                FROM ((dishes AS d
                INNER JOIN restaurants AS r ON d.restaurant_id = r.id)
                INNER JOIN dish_types AS t ON d.dish_type_id = t.id)
                WHERE r.user_id = :userid AND d.name LIKE :search LIMIT :total OFFSET :page
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('userid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
            $statement->bindValue('search', '%' . $search_text . '%');
            $statement->bindValue('total', $total_per_page, PDO::PARAM_INT);
            $statement->bindValue('page', $page, PDO::PARAM_INT);
            $statement->execute();

            while ($row = $statement->fetchObject(DishRestaurantModel::class)) array_push($all_dishes, $row);
            $not_empty = count($all_dishes);

            $query = "SELECT count(*) FROM dishes AS d INNER JOIN restaurants AS r ON d.restaurant_id = r.id WHERE user_id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));
            $total_records = $statement->fetchColumn();
            
            $total_pages = ceil($total_records / $total_per_page);
            for ($i = 1; $i <= $total_pages; $i++) array_push($pagination, array(
                'it' => $i,
                'url' => $redirect_url . '?page=' . $i . '&total=' . $total_per_page, 
                'selected' => $curr_page ==  $i ? 'active' : '',
            ));

            PaginationHelper::check_if_page_is_greaten_than($redirect_url, $total_pages);
            $pages_nav = PaginationHelper::get_pagination_nav($curr_page, $total_per_page, $total_pages, $total_records, 'owner/dishes');
            $statement->closeCursor();
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $pagination_visible = false;
            $this->_banner_error = true;
            $this->_banner_message = $e->getMessage();
            $this->dbh->rollback();
        }
        return array(
            'dishes' => $all_dishes,
            'banner_active' => !empty($this->_banner_message),
            'banner_message' => $this->_banner_message,
            'total_per_page' => $total_per_page,
            'pagination_url' => 'owner/dishes?',
            'pagination' => $pagination,
            'pagination_visible' => $pagination_visible,
            'pages_nav' => $pages_nav,
            'search_text' => $search_text,
            'not_empty' => $not_empty,
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda zwracająca wszystkie restauracje wybranego zalogowanego użytkownika z możliwością dodania do nich potrawy poprzez przycisk.
     * Kliknięcie w przycisk przenosi użytkownika do strony /owner/dishes/add-dish z id restauracji.
     */
    public function get_all_restaurants_with_dishes()
    {
        $active_restaurants = array();
        $not_empty = false;
        $pagination = array();
        $pages_nav = array();
        $pagination_visible = true;
        try
        {
            $this->dbh->beginTransaction();

            $curr_page = $_GET['page'] ?? 1;
            $page = ($curr_page - 1) * 5;
            $total_per_page = $_GET['total'] ?? 5;
            $search_text = $_POST['search-restaurant-name'] ?? '';

            $redirect_url = 'owner/dishes/add-dish-to-restaurant';
            PaginationHelper::check_parameters($redirect_url);

            $query = "
                SELECT ROW_NUMBER() OVER(ORDER BY id) as it, id, name,
                (SELECT COUNT(*) FROM dishes WHERE restaurant_id = r.id) AS count_of_dishes,
                CONCAT('ul. ', street, ' ', building_locale_nr, ', ', post_code, ' ', city) AS address
                FROM restaurants AS r
                WHERE user_id = :userid AND accept = 1 AND name LIKE :search LIMIT :total OFFSET :page
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('userid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
            $statement->bindValue('search', '%' . $search_text . '%');
            $statement->bindValue('total', $total_per_page, PDO::PARAM_INT);
            $statement->bindValue('page', $page, PDO::PARAM_INT);
            $statement->execute();

            while ($row = $statement->fetchObject(ActiveRestaurantModel::class)) array_push($active_restaurants, $row);
            $not_empty = count($active_restaurants);
            
            $query = "SELECT count(*) FROM restaurants WHERE accept = 1 AND user_id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));
            $total_records = $statement->fetchColumn();

            $total_pages = ceil($total_records / $total_per_page);
            for ($i = 1; $i <= $total_pages; $i++) array_push($pagination, array(
                'it' => $i,
                'url' => 'owner/dishes/add-dish-to-restaurant?page=' . $i . '&total=' . $total_per_page, 
                'selected' => $curr_page ==  $i ? 'active' : '',
            ));

            PaginationHelper::check_if_page_is_greaten_than($redirect_url, $total_pages);
            $pages_nav = PaginationHelper::get_pagination_nav($curr_page, $total_per_page, $total_pages, $total_records, $redirect_url);
            $statement->closeCursor();
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $pagination_visible = false;
            $this->_banner_error = true;
            $this->_banner_message = $e->getMessage();
            $this->dbh->rollback();
        }
        return array(
            'active_restaurants' => $active_restaurants,
            'banner_active' => !empty($this->_banner_message),
            'banner_message' => $this->_banner_message,
            'total_per_page' => $total_per_page,
            'pagination_url' => 'owner/dishes/add-dish-to-restaurant?',
            'pagination' => $pagination,
            'pagination_visible' => $pagination_visible,
            'pages_nav' => $pages_nav,
            'search_text' => $search_text,
            'not_empty' => $not_empty,
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda umożliwiająca dodanie potrawy do restauracji na podstawie id restauracji przekazywanego w parametrach GET zapytania. Metoda
     * uruchamiana również poprzez adres proxy dodający do parametrów GET id (/owner/dishes/add-dish). Jeśli id restauracji jest puste lub
     * restauracja nie istnieje, przekierowanie na poprzednią stronę (wszystkie potrawy wybranej restauracji).
     */
    public function add_dish_to_restaurant()
    {
        $default_dish_types = array();
        $dish = new AddEditDishModel;
        try
        {
            $this->dbh->beginTransaction();
            $this->check_if_restaurant_is_valid();
            $default_dish_types = $this->get_default_dish_types();
            if (isset($_POST['add-edit-dish-button']))
            {
                $dish->name = ValidationHelper::validate_field_regex('dish-name', '/^[a-zA-Z0-9ąćęłńóśźżĄĆĘŁŃÓŚŹŻ\-\/%@$: ]{2,50}$/');
                $dish->description = ValidationHelper::validate_field_regex('dish-description', Config::get('__REGEX_DESCRIPTION__'));
                $dish->price = ValidationHelper::validate_field_regex('dish-price', Config::get('__REGEX_PRICE__'));
                $dish->photo_url = ValidationHelper::validate_image_regex('dish-profile');
                $default_dish_types = $this->get_default_dish_types($_POST['dish-type']);
                $dish->prepared_time = ValidationHelper::validate_field_regex('dish-prepared-time', '/^(?!0|1$)[0-9]{1,3}$/');
                if ($_POST['dish-type'] === 'Niestandardowy typ potrawy') $dish->custom_type = ValidationHelper::validate_field_regex(
                    'new-dish-type', '/^[a-zA-Z0-9ąćęłńóśźżĄĆĘŁŃÓŚŹŻ\-\/%@$: ]{2,50}$/'
                );
                else $dish->type['value'] = $_POST['dish-type'];
                
                if ($dish->all_is_valid())
                {
                    // sprawdź, czy nie następuje próba dodania istniejącej potrawy do wybranej restauracji (duplikacja potrawy)
                    $query = "
                        SELECT COUNT(*) FROM dishes AS d
                        INNER JOIN restaurants AS r ON d.restaurant_id = r.id
                        WHERE LOWER(d.name) = LOWER(?) AND user_id = ? AND restaurant_id = ?
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($dish->name['value'], $_SESSION['logged_user']['user_id'], $_GET['resid']));
                    if (!empty($statement->fetchColumn())) throw new Exception('
                        Potrawa o nazwie <strong>' . $dish->name['value'] . '</strong> istnieje już w systemie i jest już przypisana do 
                        wybranej restauracji. Zmień nazwę dodawanej potrawy lub zmień restaurację.
                    ');
                    $dish_type_id = $this->add_new_dish_type_or_select_exist($dish->custom_type['value'], $dish->type['value']);
                    $query = "
                        INSERT INTO dishes (name, description, price, prepared_time, dish_type_id, restaurant_id) VALUES (?,?,?,?,?,?)
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $dish->name['value'], $dish->description['value'], $dish->price['value'], $dish->prepared_time['value'],
                        $dish_type_id, $_GET['resid'],
                    ));

                    $query = "SELECT LAST_INSERT_ID()";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute();
                    $added_dish_id = $statement->fetchColumn();
                    $image = ImagesHelper::upload_dish_image($dish->photo_url, $added_dish_id);

                    $query = "UPDATE dishes SET photo_url = NULLIF(?,'') WHERE id = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($image, $added_dish_id));

                    $dish->photo_url['value'] = $image;
                    $banner_message = '
                        Dodawanie nowej potrawy <strong>' . $dish->name['value'] . '</strong> zostało pomyślnie ukończone.
                    ';
                    SessionHelper::create_session_banner(SessionHelper::RESTAURANT_DETAILS_PAGE_BANNER, $banner_message, false);
                    $statement->closeCursor();
                    $this->dbh->commit();
                    header('Refresh:0; url=' . __URL_INIT_DIR__ . 'owner/restaurants/restaurant-details?id=' . $_GET['resid']);
                    die;
                }
            }
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::ADD_EDIT_DISH_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'dish' => $dish,
            'restaurant_id' => $_GET['resid'],
            'default_dishes_types' => $default_dish_types,
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda umożliwiająca edycję potrawy przypisanej do restauracji na podstawie id restauracji i id dania. Jeśli restauracja lub potrawa
     * nie istnieje, przekierowanie na poprzednią stronę (wszystkie potrawy wybranej restauracji).
     */
    public function edit_dish_from_restaurant()
    {
        $default_dish_types = array();
        $dish = new AddEditDishModel;
        try
        {
            $this->dbh->beginTransaction();
            $this->check_if_restaurant_is_valid();
            $this->check_if_dish_is_valid();

            $query = "
                SELECT d.name AS name, description, photo_url, price, prepared_time, t.name AS type
                FROM dishes AS d
                INNER JOIN dish_types AS t ON d.dish_type_id = t.id
                WHERE d.id = ?
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['dishid']));
            $dish = $statement->fetchObject(AddEditDishModel::class);
            $dish_photo = $dish->photo_url['value'];
            $default_dish_types = $this->get_default_dish_types($dish->type['value']);

            if (isset($_POST['add-edit-dish-button']))
            {
                $dish->name = ValidationHelper::validate_field_regex('dish-name', '/^[a-zA-Z0-9ąćęłńóśźżĄĆĘŁŃÓŚŹŻ\-\/%@$: ]{2,50}$/');
                $dish->description = ValidationHelper::validate_field_regex('dish-description', Config::get('__REGEX_DESCRIPTION__'));
                $dish->price = ValidationHelper::validate_field_regex('dish-price', Config::get('__REGEX_PRICE__'));
                $dish->photo_url = ValidationHelper::validate_image_regex('dish-profile');
                $dish->prepared_time = ValidationHelper::validate_field_regex('dish-prepared-time', '/^(?!0|1$)[0-9]{1,3}$/');
                $default_dish_types = $this->get_default_dish_types($_POST['dish-type']);
                if ($_POST['dish-type'] === 'Niestandardowy typ potrawy') $dish->custom_type = ValidationHelper::validate_field_regex(
                    'new-dish-type', '/^[a-zA-Z0-9ąćęłńóśźżĄĆĘŁŃÓŚŹŻ\-\/%@$: ]{2,50}$/');
                else $dish->type['value'] = $_POST['dish-type'];
                
                if ($dish->all_is_valid())
                {
                    $dish_type_id = $this->add_new_dish_type_or_select_exist($dish->custom_type['value'], $dish->type['value']);
                    $profile = ImagesHelper::upload_dish_image($dish->photo_url, $_GET['dishid'], $dish_photo);
                    
                    $query = "
                        UPDATE dishes SET
                        name = ?, description = ?, photo_url = NULLIF(?,''), price = ?, prepared_time = ?, dish_type_id = ?
                        WHERE id = ?
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $dish->name['value'], $dish->description['value'], $profile, $dish->price['value'], $dish->prepared_time['value'],
                        $dish_type_id, $_GET['dishid'],
                    ));
                    $dish->photo_url['value'] = $profile;
                    $banner_message = '
                        Edycja potrawy <strong>' . $dish->name['value'] . '#' . $_GET['dishid'] . '</strong> została pomyślnie zakończona.
                    ';
                    SessionHelper::create_session_banner(SessionHelper::RESTAURANT_DETAILS_PAGE_BANNER, $banner_message, false);
                    $statement->closeCursor();
                    $this->dbh->commit();
                    header('Refresh:0; url=' . __URL_INIT_DIR__ . 'owner/restaurants/restaurant-details?id=' . $_GET['resid']);
                    die;
                } else $dish->photo_url['value'] = $dish_photo;
            }
            $statement->closeCursor();
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::ADD_EDIT_DISH_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'dish' => $dish,
            'restaurant_id' => $_GET['resid'],
            'dish_id' => $_GET['dishid'],
            'has_image' => !empty($dish->photo_url['value']),
            'default_dishes_types' => $default_dish_types,
            'hide_image_preview_class' => $dish->photo_url['invl'] ? 'display-none' : '',
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda umożliwiająca usuwanie potrawy z wybranej restauracji na podstawie id. Jeśli potrawa z wybranym id nie istnieje bądź id jest
     * puste, przekierowanie na poprzednią stronę.
     */
    public function delete_dish_from_restaurant()
    {
        try
        {
            $this->dbh->beginTransaction();
            $this->check_if_restaurant_is_valid();
            $this->check_if_dish_is_valid();
            $query = "
                SELECT CONCAT(d.name, '#', d.id) AS d_name, CONCAT(r.name, '#', r.id) AS r_name, d.photo_url AS photo_url
                FROM dishes AS d INNER JOIN restaurants AS r ON d.restaurant_id = r.id
                WHERE d.id = ?
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['dishid']));
            $deleted_dish = $statement->fetch(PDO::FETCH_ASSOC);
            
            $query = "DELETE FROM dishes WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['dishid']));
            
            unlink($deleted_dish['photo_url']);
            $this->_banner_message = '
                Pomyślnie usunięto potrawę <strong>' . $deleted_dish['d_name'] . '</strong> z restauracji <strong>' . 
                $deleted_dish['r_name'] . '</strong>.
            ';
            $statement->closeCursor();
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->_banner_error = true;
            $this->_banner_message = $e->getMessage();
            $this->dbh->rollback();
        }
        SessionHelper::create_session_banner(SessionHelper::RESTAURANT_DETAILS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
        return array(
            'restaurant_id' => $_GET['resid'],
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda odpowiadająca za usuwanie zdjęcia potrawy, na podstawie id potrawy w parametrze GET dishid
     */
    public function delete_dish_image()
    {
        try
        {
            $this->dbh->beginTransaction();
            $this->check_if_restaurant_is_valid();
            $this->check_if_dish_is_valid();

            $query = "SELECT photo_url FROM dishes WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['dishid']));
            $image_path = $statement->fetchColumn();
            if (empty($image_path)) throw new Exception('Wybrana potrawa nie posiada żadnego zdjęcia.');

            $query = "UPDATE dishes SET photo_url = NULL WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['dishid']));

            unlink($image_path);
            $this->_banner_message = 'Pomyślnie usunięto zdjęcie wybranej potrawy.';
            $statement->closeCursor();
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->_banner_error = true;
            $this->_banner_message = $e->getMessage();
            $this->dbh->rollback();
        }
        SessionHelper::create_session_banner(SessionHelper::ADD_EDIT_DISH_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
        return array(
            'redirect_path' => 'owner/dishes/edit-dish?resid=' . $_GET['resid']  . '&dishid=' . $_GET['dishid'],
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda pobierająca szczegółowe dane na temat potrawy. Jeśli id restauracji jest puste bądź potrawy lub jeden z nich nie istnieje w
     * bazie i nie jest przypisany do zalogowanego użytkownika, przekieruj na stronę z restauracjami lub szczegółami wybranej restauracji.
     */
    public function get_dish_details()
    {
        $dish_details = new DishDetailsModel;
        try
        {
            $this->dbh->beginTransaction();
            $this->check_if_restaurant_is_valid();
            $this->check_if_dish_is_valid();
            
            $query = "
                SELECT d.name, t.name AS type, d.description AS description, price, r.name AS r_name, d.photo_url AS photo_url,
                CONCAT('ul. ', street, ' ', building_locale_nr, ', ', post_code, ' ', city) AS r_address, 
                IFNULL(delivery_price, 'za darmo') AS r_delivery_price,
                CONCAT(u.first_name, ' ', u.last_name) AS r_full_name,
                IFNULL(delivery_price, 0) + price AS total_price,
                t.user_id IS NOT NULL AS is_custom_type, prepared_time
                FROM (((dishes AS d
                INNER JOIN restaurants AS r ON d.restaurant_id = r.id)
                INNER JOIN dish_types AS t ON d.dish_type_id = t.id)
                INNER JOIN users AS u ON r.user_id = u.id)
                WHERE d.id = :id AND r.user_id = :userid
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('id', $_GET['dishid'], PDO::PARAM_INT);
            $statement->bindValue('userid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
            $statement->execute();

            $dish_details = $statement->fetchObject(DishDetailsModel::class);

            $statement->closeCursor();
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->_banner_error = true;
            $this->_banner_message = $e->getMessage();
            $this->dbh->rollback();
        }
        SessionHelper::create_session_banner(SessionHelper::RESTAURANT_DETAILS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
        if ($this->_banner_error) header('Location:' . __URL_INIT_DIR__ . 'owner/restaurants/restaurant-details?id=' . $_GET['resid'], true, 301);
        return array(
            'error_redirect' => $this->_banner_error,
            'restaurant_id' => $_GET['resid'],
            'dish_id' => $_GET['dishid'],
            'details'=> $dish_details,
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda sprawdzająca, czy restauracja na podstawie id pobieranego z parametrów GET zapytania istnieje oraz, czy jest to restauracja
     * stworzona przez zalogowanego użytkownika.
     */
    private function check_if_restaurant_is_valid()
    {
        if (!isset($_GET['resid'])) header('Location:' . __URL_INIT_DIR__ . 'owner/restaurants', true, 301);

        $query = "SELECT COUNT(*) FROM restaurants WHERE id = :id AND user_id = :userid";
        $statement = $this->dbh->prepare($query);
        $statement->bindValue('id', $_GET['resid'], PDO::PARAM_INT);
        $statement->bindValue('userid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
        $statement->execute();
        if ($statement->fetchColumn() != 0) return;
        
        $this->_banner_message = 'Wybrana restauracja nie istnieje lub nie jest przypisana do Twojego konta.';
        SessionHelper::create_session_banner(SessionHelper::RESTAURANTS_PAGE_BANNER, $this->_banner_message, true);
        $statement->closeCursor();
        $this->dbh->commit();
        header('Location:' . __URL_INIT_DIR__ . 'owner/restaurants');
        die;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda sprawdzająca, czy potrawa na podstawie id pobieranego z parametrów GET zapytania istnieje oraz, czy ta potrawa przypisana
     * jest do wybranej restauracji.
     */
    private function check_if_dish_is_valid()
    {
        if (!isset($_GET['dishid']) && isset($_GET['resid']))
        {
            header('Location:' . __URL_INIT_DIR__ . 'owner/restaurants/restaurant-details?id=' . $_GET['resid'], true, 301);
        }
        $query = "SELECT COUNT(*) FROM dishes WHERE id = :id AND restaurant_id = :restid";
        $statement = $this->dbh->prepare($query);
        $statement->bindValue('id', $_GET['dishid'], PDO::PARAM_INT);
        $statement->bindValue('restid', $_GET['resid'], PDO::PARAM_INT);
        $statement->execute();
        if ($statement->fetchColumn() != 0) return;
        
        $this->_banner_message = 'Wybrana potrawa nie istnieje lub nie jest przypisana do Twojej restauracji.';
        SessionHelper::create_session_banner(SessionHelper::RESTAURANT_DETAILS_PAGE_BANNER, $this->_banner_message, true);
        $statement->closeCursor();
        $this->dbh->commit();
        header('Location:' . __URL_INIT_DIR__ . 'owner/restaurants/restaurant-details?id=' . $_GET['resid'], true, 301);
        die;
    }
    
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda zwraca domyślne typy potraw oraz te niestandardowe, stworzone przez użytkownika.
     */
    private function get_default_dish_types($selected_dish_type = '')
    {
        $dish_types = array();
        array_push($dish_types, array('name' => 'Wybierz typ potrawy', 'selected' => empty($selected_dish_type) ? 'selected' : ''));
        
        $query = "SELECT name FROM dish_types WHERE user_id IS NULL OR user_id = ?";
        $statement = $this->dbh->prepare($query);
        $statement->execute(array($_SESSION['logged_user']['user_id']));
        $default_dish_types = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($default_dish_types as $dish_type) array_push($dish_types, array(
            'name' => $dish_type['name'],
            'selected' => $dish_type['name'] === $selected_dish_type ? 'selected' : ''
        ));
        
        array_push($dish_types, array(
            'name' => 'Niestandardowy typ potrawy',
            'selected' => $selected_dish_type === 'Niestandardowy typ potrawy' ? 'selected' : ''
        ));
        $statement->closeCursor();
        return $dish_types;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Dodaj nowy typ dania lub wybierz istniejący typ na podstawie pola select box.
     */
    private function add_new_dish_type_or_select_exist($custom_type_value, $type_value)
    {
        $dish_type_id = 0;
        // jeśli nic o tej samej nazwie typu nie jest wpisane, to dodaj ten typ dania do tabeli.
        if ($_POST['dish-type'] === 'Niestandardowy typ potrawy')
        {
            $query = "SELECT COUNT(*) FROM dish_types WHERE (user_id = ? OR user_id IS NULL) AND LOWER(name) = LOWER(?)";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id'], $custom_type_value));
            if (!empty($statement->fetchColumn())) throw new Exception('
                Wybrany typ potrawy istnieje już na rozwijanej liście. Wybierz typ potrawy z rozwijanej listy 
                lub wprowadź nowy typ potrawy.
            ');
            $query = "INSERT INTO dish_types (name, user_id) VALUES (?,?)";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($custom_type_value, $_SESSION['logged_user']['user_id']));

            $query = "SELECT id FROM dish_types WHERE LOWER(name) = LOWER(?)";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($custom_type_value));
            $dish_type_id = $statement->fetchColumn();
        }
        else // jeśli wybrano typ z rozwijanej listy, pobierz od niego ID
        {
            $query = "SELECT id FROM dish_types WHERE LOWER(name) = LOWER(?) AND (user_id = ? OR user_id IS NULL)";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($type_value, $_SESSION['logged_user']['user_id']));
            $dish_type_id = $statement->fetchColumn();
        }
        return $dish_type_id;
    }
}
