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
 * Ostatnia modyfikacja: 2022-12-06 17:54:01                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services;

use PDO;
use Exception;

use App\Utils\Utils;
use App\Core\Config;
use App\Core\MvcService;

class RestaurantService extends MvcService
{
    private $_error;

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
                $v_name = Utils::validate_field_regex('restaurant-name', '/^[a-zA-Z0-9ąćęłńóśźżĄĆĘŁŃÓŚŹŻ-\/%@$: ]{2,50}$/');
                $v_price = Utils::validate_field_regex('restaurant-delivery-price', Config::get('__REGEX_PRICE__'));
                $v_building_no = Utils::validate_field_regex('restaurant-building-no', Config::get('__REGEX_BUILDING_NO__'));
                $v_post_code = Utils::validate_field_regex('restaurant-post-code', '');
                $v_city = Utils::validate_field_regex('restaurant-city', Config::get('__REGEX_CITY__'));
                $v_street = Utils::validate_field_regex('restaurant-street', Config::get('__REGEX_STREET__'));
                $v_banner = Utils::validate_image_regex('restaurant-banner');
                $v_profile = Utils::validate_image_regex('restaurant-profile');
                
                $this->dbh->beginTransaction();

                if (!($v_name['invl'] || $v_price['invl'] || $v_banner['invl'] || $v_profile['invl'] ||  $v_street['invl'] || 
                      $v_building_no['invl'] ||$v_post_code['invl'] || $v_city['invl'])) 
                {
                    // Zapytanie zwracające liczbę istniejących już restauracji o podanej nazwie
                    $query = "
                        SELECT COUNT(id) FROM restaurants
                        WHERE street = ? AND building_locale_nr = ? AND post_code = ? AND city = ?
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $v_street['value'],
                        $v_building_no['value'],
                        $v_post_code['value'],
                        $v_city['value']
                    ));

                    if ($statement->fetchColumn() > 0)
                        throw new Exception('Podana restauracja istnieje już w tym miejscu. Podaj inne dane adresowe.');

                    $v_price = str_replace(',', '.', $v_price);
                    
                    // Sekcja zapytań dodająca wprowadzone dane do tabeli restaurants
                    $query = "
                        INSERT INTO restaurants (name, delivery_price, street, building_locale_nr, post_code, city) 
                        VALUES (?,?,?,?,?,?)
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $v_name['value'],
                        $v_price['value'],
                        $v_street['value'],
                        $v_building_no['value'],
                        $v_post_code['value'],
                        $v_city['value']
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
                    // Tymczasowe przekierowanie do strony głównej po poprawnym dodaniu restauracji
                    header('Location:index.php?action=home/welcome');
                }
                $this->dbh->commit();
            } 
            catch (Exception $e) 
            {
                $this->dbh->rollback();
                $this->_error = $e->getMessage();
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
                'error' => $this->_error,
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
            if (!isset($_GET['id'])) header('Location:index.php?action=home/welcome');

            $this->dbh->beginTransaction();

            // Zapytanie zwracające aktualne wartości edytowanej restauracji z bazy danych
            $query = "SELECT * FROM restaurants WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));
            $restaurant = $statement->fetchAll(PDO::FETCH_ASSOC);
            if (count($restaurant) == 0) header('Location:index.php?action=home/welcome');
            
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
                        $v_street['value'],
                        $v_building_no['value'],
                        $v_post_code['value'],
                        $v_city['value'],
                        $_GET['id']
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
                        $v_name['value'],
                        $v_price['value'],
                        $v_street['value'],
                        $v_building_no['value'],
                        $v_post_code['value'],
                        $v_city['value'],
                        $photos['banner'],
                        $photos['profile'],
                        $_GET['id']
                    ));
                    $statement->closeCursor();
                    // Tymczasowe przekierowanie do strony głównej po poprawnym dodaniu restauracji
                    header('Location:index.php?action=home/welcome');
                }
            }
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $this->_error = $e->getMessage();
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
            'error' => $this->_error,
        );
    }

    //--------------------------------------------------------------------------------------------------------------------------------------
    
    /**
     * Metoda odpowiadająca za usuwanie obecnej restauracji.
     * Jeśli restauracja została pomyślnie usunięta następuje (tymczasowo) przekierowanie do strony głównej.
     * dorobienie weryfikacji id podczas sesji//
     */
    public function delete_restaurant()
    {
        if (isset($_POST['restaurant-delete-button'])) {
            try {
                $this->dbh->beginTransaction();
                if (!isset($_GET['id']))
                    header('Location:index.php?action=home/welcome');
                $query = " DELETE FROM restaurants WHERE id = ?  ";
                $statement = $this->dbh->prepare($query);
                $restaurant = $statement->fetchAll(PDO::FETCH_ASSOC);
                $statement->execute(
                    array($_GET['id'])
                );

                if ($restaurant->fetchColumn() > 0) // > ? zmiana znaku
                    throw new Exception('Nie ma takiej restauracji lub nie można jej usunąć.');
                $statement->closeCursor();
                $this->dbh->commit();
                // Tymczasowe przekierowanie do strony głównej po usunieciu restauracji
                header('Location:index.php?action=home/welcome');
            } catch (Exception $e) {
                $this->dbh->rollback();
                $this->_error = $e->getMessage();
            }
        }
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    private function create_images_if_not_exist($id, $field_profile, $field_banner)
    {
        if (!file_exists("uploads/restaurants/$id/")) mkdir("uploads/restaurants/$id/");
        $banner = "uploads/restaurants/$id/" . $id . '_banner.' . $field_banner['ext'];
        $profile = "uploads/restaurants/$id/" . $id . '_profile.' . $field_profile['ext'];
        move_uploaded_file($field_banner['path'], $banner);
        move_uploaded_file($field_profile['path'], $profile);
        return array('banner' => $banner, 'profile' => $profile);
    }
}
