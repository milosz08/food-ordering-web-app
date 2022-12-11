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
 * Ostatnia modyfikacja: 2022-12-11 01:02:22                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services;

use App\Models\RestaurantModel;
use PDO;
use Exception;

use App\Utils\Utils;
use App\Core\Config;
use App\Core\MvcService;

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
                
                $this->dbh->beginTransaction();

                if (!($v_name['invl'] || $v_price['invl'] || $v_banner['invl'] || $v_profile['invl'] || $v_street['invl'] || 
                      $v_building_no['invl'] ||$v_post_code['invl'] || $v_city['invl'])) 
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
                        INSERT INTO restaurants (name, delivery_price, street, building_locale_nr, post_code, city) VALUES (?,?,?,?,?,?)
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $v_name['value'], $v_price['value'], $v_street['value'], $v_building_no['value'], $v_post_code['value'],
                        $v_city['value'],
                    ));
                    // Sekcja zapytań zwracająca id ostatnio dodanej restauracji
                    $query = "SELECT id FROM restaurants ORDER BY id DESC LIMIT 1";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute();
                    $thisRestaurant = $statement->fetchAll(PDO::FETCH_ASSOC);
                    $id_image = $thisRestaurant[0]['id'];

                    $photos = $this->create_images_if_not_exist($id_image, $v_profile, $v_banner);
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
                    header('Location:index.php?action=restaurant/panel/myrestaurants', true, 301);
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
            if (!isset($_GET['id'])) header('Location:index.php?action=restaurant/panel/myrestaurants', true, 301);

            $this->dbh->beginTransaction();

            // Zapytanie zwracające aktualne wartości edytowanej restauracji z bazy danych
            $query = "SELECT * FROM restaurants WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));
            $restaurant = $statement->fetchAll(PDO::FETCH_ASSOC);
            if (count($restaurant) == 0) header('Location:index.php?action=restaurant/panel/myrestaurants', true, 301);
            
            $v_name = array('value' => $restaurant[0]['name'], 'invl' => false, 'bts_class' => '');
            $v_street = array('value' => $restaurant[0]['street'], 'invl' => false, 'bts_class' => '');
            $v_building_no = array('value' => $restaurant[0]['building_locale_nr'], 'invl' => false, 'bts_class' => '');
            $v_post_code = array('value' => $restaurant[0]['post_code'], 'invl' => false, 'bts_class' => '');
            $v_city = array('value' => $restaurant[0]['city'], 'invl' => false, 'bts_class' => '');
            $v_price = array('value' => $restaurant[0]['delivery_price'], 'invl' => false, 'bts_class' => '');

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

                if (!($v_name['invl'] || $v_price['invl'] || $v_banner['invl'] || $v_profile['invl'] || $v_street['invl'] ||
                      $v_building_no['invl'] || $v_post_code['invl'] || $v_city['invl']))
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

                    $photos = $this->create_images_if_not_exist($_GET['id'], $v_profile, $v_banner);
                    // Sekcja zapytań aktualizujących pola w tabeli
                    $v_price = str_replace(',', '.', $v_price);
                    $query = "
                        UPDATE restaurants SET name = ?, delivery_price = ?, street = ?, building_locale_nr = ?, 
                        post_code = ?, city = ?, baner_url = ?, profile_url = ? WHERE id = ?
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $v_name['value'], $v_price['value'], $v_street['value'], $v_building_no['value'], $v_post_code['value'],
                        $v_city['value'], $photos['banner'], $photos['profile'], $_GET['id']
                    ));
                    $statement->closeCursor();
                    $this->_banner_message = 'Restauracja została pomyślnie zedytowana.';

                    $_SESSION['manipulate_restaurant_banner'] = array(
                        'banner_message' => $this->_banner_message,
                        'show_banner' => !empty($this->_banner_message),
                        'banner_class' => 'alert-success',
                    );
                    header('Location:index.php?action=restaurant/panel/myrestaurants', true, 301);
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
            'error' => $this->_banner_message,
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
        if (!isset($_GET['id'])) header('Location:index.php?action=restaurant/panel/myrestaurants', true, 301);
        try
        {
            $this->dbh->beginTransaction();

            $query = "SELECT COUNT(*) FROM restaurants WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));

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

    private function create_images_if_not_exist($id, $field_profile, $field_banner)
    {
        $images_paths = array('banner' => '', 'profile' => '');
        if (!empty($field_profile['value']) && !empty($field_banner['value']))
        {
            if (!file_exists("uploads/restaurants/$id/")) mkdir("uploads/restaurants/$id/");
        }
        if (!empty($field_profile['value'])) 
        {
            $profile = "uploads/restaurants/$id/" . $id . '_profile.' . $field_profile['ext'];
            move_uploaded_file($field_profile['path'], $profile);
            $images_paths['profile'] = $profile;
        }
        if (!empty($field_banner['value']))
        {
            $banner = "uploads/restaurants/$id/" . $id . '_banner.' . $field_banner['ext'];
            move_uploaded_file($field_banner['path'], $banner);
            $images_paths['banner'] = $banner;
        }
        return $images_paths;
    }

    public function create_restaurant_table()
    {
        try {
            $i = 1;
            $it = array();
            $user_restaurant = array();
            $query = "SELECT  name, street, building_locale_nr, post_code, city, delivery_price, id FROM restaurants WHERE user_id = ? ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));
            
            while($restaurant = $statement->fetchObject(RestaurantModel::class)) // przejdź przez wszystkie rekordy
            {
                array_push($user_restaurant, array('res' => $restaurant, 'iterator' => $i) );
                $i++;
            }
            $pagination = array();
            $j = 1;
            while($j < var_dump((int)$i / 6))
            {
                array_push($paginationm, array('page' => var_dump((int) $i / 6)));
                $j++;
            }
        }
        catch (Exception $e)
        {
            $this->_banner_message = $e->getMessage();
        }
        return array(
            'pagination' => $pagination,
            'user_restaurant' => $user_restaurant,
        );
    }
    
}
