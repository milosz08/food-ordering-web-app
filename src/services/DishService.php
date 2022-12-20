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
 * Ostatnia modyfikacja: 2022-12-20 23:02:19                   *
 * Modyfikowany przez: Lukasz Krawczyk                         *
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

    protected function __construct()
    {
        parent::__construct();
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda odpowiadająca za dodawanie danych nowej restauracji oraz sprawdzanie ich z istniejącą bazą danych.
     * Jeśli restauracja została pomyślnie dodana następuje (tymczasowo) przekierowanie do strony głównej.
     */
    public function add_dish()
    {
        try {
            $restaurants = array();

            $this->dbh->beginTransaction();

            $query = "SELECT name, street, building_locale_nr, city, id FROM restaurants WHERE user_id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));
            $restaurants = $statement->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            $this->dbh->rollback();
            $this->_banner_message = $e->getMessage();
        }
        return array(
            'restaurants' => $restaurants,
            //'v_name' => $v_name,
            //'v_price' => $v_price,
            //'v_profile' => $v_profile,
            //'v_description' => $v_description,
            //'error' => $this->_banner_message,
        );
        
/*
        if (isset($_POST['dish-button']))
        {
            try
            {
                $v_name = Utils::validate_field_regex('dish-name', '/^[a-zA-Z0-9ąćęłńóśźżĄĆĘŁŃÓŚŹŻ\-\/%@$: ]{2,50}$/');
                $v_price = Utils::validate_field_regex('dish-price', Config::get('__REGEX_PRICE__'));
                $v_profile = Utils::validate_image_regex('dish-profile');
                $v_type = Utils::validate_field_regex('dish-name', '/^[a-zA-Z0-9ąćęłńóśźżĄĆĘŁŃÓŚŹŻ\-\/%@$: ]{2,50}$/');
                //$v_restaurant = Utils::validate_field_regex('')
                $v_description = Utils::validate_field_regex('restaurant-description', Config::get('__REGEX_DESCRIPTION__'));
                
                if (!($v_name['invl'] || $v_price['invl'] || $v_profile['invl']  || $v_type['invl'] || $v_description['invl'])) 
                {
                    // Sekcja zapytań dodająca wprowadzone dane do tabeli restaurants
                    $query = "
                        INSERT INTO dishes (name, description, photo_url, price, )
                        VALUES (?,?,?, ?)
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $v_name['value'], $v_price['value'], $v_profile['value'], $v_description['value'],
                    ));
                    // Sekcja zapytań zwracająca id ostatnio dodanej restauracji
                    $query = "SELECT id FROM restaurants ORDER BY id DESC LIMIT 1";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute();
                    $thisRestaurant = $statement->fetchAll(PDO::FETCH_ASSOC);
                    $id_image = $thisRestaurant[0]['id'];

                    $v_banner = null;
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
            } */       
    }
}
