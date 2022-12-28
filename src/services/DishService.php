<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: DishService.php                                *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-12-20, 19:10:30                       *
 * Autor: Lukasz Krawczyk                                      *
 *                                                             *
 * Ostatnia modyfikacja: 2022-12-28 14:51:59                   *
 * Modyfikowany przez: Desi                                    *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services;

use PDO;
use Exception;

use App\Utils\Utils;
use App\Core\Config;
use App\Core\MvcService;
use App\Models\RestaurantModel;

class DishService extends MvcService
{
    private $_banner_message;
    private $_if_banner_error;
    //--------------------------------------------------------------------------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    public function show_restaurants()
    {
        $restaurants = array();

        $this->dbh->beginTransaction();

        $query = "SELECT name, street, building_locale_nr, city, id FROM restaurants WHERE user_id = ?";
        $statement = $this->dbh->prepare($query);
        $statement->execute(array($_SESSION['logged_user']['user_id']));
        $restaurants = $statement->fetchAll(PDO::FETCH_ASSOC);

        return array(
            'restaurants' => $restaurants
        );
    }
    /**
     * Metoda odpowiadająca za dodawanie danych nowej restauracji oraz sprawdzanie ich z istniejącą bazą danych.
     * Jeśli restauracja została pomyślnie dodana następuje (tymczasowo) przekierowanie do strony głównej.
     */
    public function add_dish()
    {

        if (isset($_POST['dish-button'])) {

            try {
                $v_type = Utils::validate_field_regex('dish-type', '/^[a-zA-Z0-9ąćęłńóśźżĄĆĘŁŃÓŚŹŻ\-\/%@$: ]{2,50}$/');
                $v_name = Utils::validate_field_regex('dish-name', '/^[a-zA-Z0-9ąćęłńóśźżĄĆĘŁŃÓŚŹŻ\-\/%@$: ]{2,50}$/');
                $v_price = Utils::validate_field_regex('dish-price', Config::get('__REGEX_PRICE__'));
                $v_profile = Utils::validate_image_regex('dish-profile');
                $v_description = Utils::validate_field_regex('dish-description', Config::get('__REGEX_DESCRIPTION__'));

                $this->dbh->beginTransaction();
                
                if (!($v_type['invl'] || $v_name['invl'] || $v_price['invl'] || $v_profile['invl'] || empty($_POST['check_list']))) {

                    //Sekcja sprawdzania czy podany typ dania jest już wpisany do tabeli 'dish_type'.
                    $query = " SELECT Count(id) FROM dish_type WHERE name = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($v_type['value']));

                    // Jeśli nic o tej samej nazwie typu nie jest wpisane, to dodaj ten typ dania do tabeli.
                    if ($statement->fetchColumn() == 0) {
                        $query = "INSERT INTO dish_type (name) VALUES (?)";
                        $statement = $this->dbh->prepare($query);
                        $statement->execute(array($v_type['value']));
                    }
                    // Pobranie ID dodanego, bądź istniejącego dania. 
                    $query = " SELECT id FROM dish_type WHERE name = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($v_type['value']));
                    $thisDish = $statement->fetchAll(PDO::FETCH_ASSOC);
                    $id_dish = $thisDish[0]['id'];

                    // Umieszczenie w tabeli nowego dania z podanymi wartościami.
                    $query = "INSERT INTO dishes (name, description, photo_url, price, dish_type_id) VALUES (?,?,?,?,?)";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($v_name['value'], $v_description['value'], $v_profile['value'], $v_price['value'], $id_dish));

                    // Sekcja zapytań zwracająca id ostatnio dodanego dania
                    $query = "SELECT id FROM dishes ORDER BY id DESC LIMIT 1";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute();
                    $thisDish = $statement->fetchAll(PDO::FETCH_ASSOC);
                    $id_image = $thisDish[0]['id'];

                    // Obsługa check boxa
                    if (!empty($_POST['check_list'])) {
                        $i = 0;
                        foreach ($_POST['check_list'] as $selected) {
                            // Dodanie do tabeli łączacej podanego dania, z zaznaczonymi restauracjami.
                            $query = "INSERT INTO rest_dish_binding (dishes_id, restaurants_id) VALUES (?, ?)";
                            $statement = $this->dbh->prepare($query);
                            $statement->execute(array($id_image, $selected));
                        }
                    }

                    // ------------------------------------------------------------------------------------------- huj wie jak to dodać
                    $photo = Utils::create_image_if_not_exist_dish($id_image, $v_profile);
                    // Sekcja zapytań uzupełniająca id restauracji posiadającej danie z tabeli łączącej 
                    $query = "UPDATE dishes SET photo_url = ?, restaurant_id = ? WHERE id = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($photo['profile'] ,$id_image, $id_image));

                    $statement->closeCursor();
                    $this->_banner_message = 'Danie zostało pomyślnie dodane.';
                    $_SESSION['manipulate_restaurant_banner'] = array(
                        'banner_message' => $this->_banner_message,
                        'show_banner' => !empty($this->_banner_message),
                        'banner_class' => 'alert-success',
                    );
                    header('Location:' . __URL_INIT_DIR__ . 'restaurant/panel/dish/add', true, 301);
                }
                $this->dbh->commit();
            } catch (Exception $e) {
                $this->dbh->rollback();
                $this->_banner_message = $e->getMessage();
            }
            return array(
                'v_type' => $v_type,
                'v_name' => $v_name,
                'v_price' => $v_price,
                'v_profile' => $v_profile,
                'v_description' => $v_description,
                'error' => $this->_banner_message
            );
        }
    }
    //--------------------------------------------------------------------------------------------------------------------------------------
    public function edit_dish()
    {
        $v_profile = array('invl' => false, 'bts_class' => '');
        try {
            if (!isset($_GET['id']))
                header('Location:' . __URL_INIT_DIR__ . 'restaurant/panel/dish/add', true, 301);
            $this->dbh->beginTransaction();

            // Zapytanie zwracające aktualne wartości edytowanego dania z bazy danych
            $query = "SELECT * FROM dishes WHERE id = ? AND restaurant_id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id'], $_SESSION['logged_user']['user_id']));
            $dishes = $statement->fetchAll(PDO::FETCH_ASSOC);
            if (count($dishes) == 0)
                header('Location:' . __URL_INIT_DIR__ . 'restaurant/panel/dish/add', true, 301);

            $v_type = array('value' => $dishes[0]['type'], 'invl' => false, 'bts_class' => '');
            $v_name = array('value' => $dishes[0]['name'], 'invl' => false, 'bts_class' => '');
            $v_price = array('value' => $dishes[0]['price'], 'invl' => false, 'bts_class' => '');
            $v_description = array('value' => $dishes[0]['description'], 'invl' => false, 'bts_class' => '');

            if (isset($_POST['dish-button'])) {
                $v_type = Utils::validate_field_regex('dish-type', '/^[a-zA-Z0-9ąćęłńóśźżĄĆĘŁŃÓŚŹŻ\-\/%@$: ]{2,50}$/');
                $v_name = Utils::validate_field_regex('dish-name', '/^[a-zA-Z0-9ąćęłńóśźżĄĆĘŁŃÓŚŹŻ\-\/%@$: ]{2,50}$/');
                $v_price = Utils::validate_field_regex('dish-price', Config::get('__REGEX_PRICE__'));
                $v_profile = Utils::validate_image_regex('dish-profile');
                $v_description = Utils::validate_field_regex('dish-description', Config::get('__REGEX_DESCRIPTION__'));

                if (!($v_type['invl'] || $v_name['invl'] || $v_price['invl'] || $v_profile['invl'] || empty($_POST['check_list']))) {
                    $query = "
                     SELECT Count(id) FROM dish_type WHERE name = ?
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($v_type['value']));

                    // Jeśli nic o tej samej nazwie typu nie jest wpisane, to dodaj ten typ dania do tabeli.
                    if ($statement->fetchColumn() == 0) {
                        $query = "INSERT INTO dish_type (name) VALUES (?)";
                        $statement = $this->dbh->prepare($query);
                        $statement->execute(array($v_type['value']));
                    }
                    // Pobranie ID dodanego, bądź istniejącego dania. 
                    $query = " SELECT id FROM dish_type WHERE name = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($v_type['value']));
                    $thisDish = $statement->fetchAll(PDO::FETCH_ASSOC);
                    $id_dish = $thisDish[0]['id'];

                    $photo = Utils::create_image_if_not_exist_dish($_GET['id'], $v_profile);
                    // Zaktulizowane tabeli z danami nowymi wartościami
                    $query = "
                UPDATE dishes SET name = ?, description = ?, photo_url = ?, price = ?, dish_type_id = ? WHERE id = ?
                ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($v_name['value'], $v_description['value'], $v_profile['value'], $v_price['value'], $v_type['value']));

                    // Sprawdzanie czy użytkownik wgrał nowe zdjęcie
                    if (!$v_profile['invl']) {
                        // Zaktualizowanie nowego zdjęcia
                        $query = "UPDATE dishes SET photo_url = ? WHERE id = ?";
                        $statement = $this->dbh->prepare($query);
                        $statement->execute(array($v_profile['value']));
                    }
                }

                if (isset($_POST['dish-button'])) {
                    $v_dish_id = Utils::validate_field_regex('dish-id', '/^[0-9]{1,11}$/');

                    $this->dbh->beginTransaction();

                    if (!$v_dish_id['invl']) {
                        //Usuń wszystkie istniejące wiersze w tabeli rest_dish dla tego dania
                        $query = "
                        DELETE FROM rest_dish WHERE dish_id = ?
                        ";
                        $statement = $this->dbh->prepare($query);
                        $statement->execute(array($v_dish_id['value']));

                        //Sprawdzanie pola wyboru 
                        if (!empty($_POST['check_list'])) {
                            //Wstaw wiersze do tabeli rest_dish dla tego dania i wybranych restauracji
                            foreach ($_POST['check_list'] as $selected) {
                                $query = "
                                INSERT INTO rest_dish (dish_id, restaurant_id) VALUES (?, ?)
                                ";
                                $statement = $this->dbh->prepare($query);
                                $statement->execute(array($v_dish_id['value'], $selected));
                            }
                        }
                    }
                    $this->dbh->commit();
                }
            }
        } catch (Exception $e) {
            $this->dbh->rollback();
            $this->_banner_message = $e->getMessage();
        }
        return array(
            'v_type' => $v_type,
            'v_name' => $v_name,
            'v_price' => $v_price,
            'v_profile' => $v_profile,
            'v_description' => $v_description,
            'error' => $this->_banner_message
        );
    }
    //--------------------------------------------------------------------------------------------------------------------------------------
    /**
     * Metoda odpowiadająca za usuwanie dania.
     */
    public function remove_dish()
    {
        $v_profile = array('invl' => false, 'bts_class' => '');
        try {
            if (!isset($_GET['id']))
                header('Location:' . __URL_INIT_DIR__ . 'restaurant/panel/myrestaurants', true, 301);
            $this->dbh->beginTransaction();

            // Zapytanie usuwajace danie z restauracji
            $query = "DELETE * FROM dishes WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));
            //$dishes = $statement->fetchAll(PDO::FETCH_ASSOC);
            $statement->closeCursor();
            $this->_banner_message = 'Pomyślnie usunięto wybrane danie z systemu.';
            $this->dbh->commit();
        }
        catch (Exception $e) {
            $this->dbh->rollback();
            $this->_banner_message = $e->getMessage();
        }
        $_SESSION['manipulate_restaurant_banner'] = array(
            'banner_message' => $this->_banner_message,
            'show_banner' => !empty($this->_banner_message),
            'banner_class' => $this->_if_banner_error ? 'alert-danger' : 'alert-success',
        );
    }
}
