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
 * Ostatnia modyfikacja: 2022-12-03 17:37:20                   *
 * Modyfikowany przez: BubbleWaffle                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services;

use PDO;
use Exception;

use App\Utils\Utils;
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
     * Funkcja odpowiadająca za dodawanie danych nowej restauracji oraz sprawdzanie ich z istniejącą bazą danych.
     * Jeśli restauracja została pomyślnie dodana następuje (tymczasowo) przekierowanie do strony głównej.
     */
    public function add_restaurant()
    {
        if(isset($_POST['restaurant-button']))
        {
            try
            {
                $v_name_restaurant = Utils::validate_field_regex('restaurant-name', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ" "1234567890]{2,50}$/');
                $v_delivery_price = Utils::validate_field_regex('restaurant-delivery-price', '/^[1-9]{1}(?:[0-9])?(?:[\.\,][0-9]{1,2})?$/');
                $v_locale_no = Utils::validate_field_regex('restaurant-building-no', '/^([0-9]+(?:[a-z]{0,1})){1,5}$/');
                $v_post_code = Utils::validate_field_regex('restaurant-post-code', '/^[0-9]{2}-[0-9]{3}$/');
                $v_city = Utils::validate_field_regex('restaurant-city', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ" "]{2,60}$/');
                $v_street= Utils::validate_field_regex('restaurant-street', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ1234567890" "]{2,100}$/');
                

                $this->dbh->beginTransaction();

                if (!($v_name_restaurant['invl'] || $v_delivery_price['invl'] || $v_street['invl'] || $v_locale_no['invl'] || 
                $v_post_code['invl'] || $v_city['invl']))
                {
                    // Zapytanie zwracające liczbę istniejących już restauracji o podanej nazwie
                    $query = "SELECT COUNT(id) FROM restaurants WHERE street = ? AND building_locale_nr = ? AND post_code = ? AND city = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $v_street['value'],
                        $v_locale_no['value'],
                        $v_post_code['value'],
                        $v_city['value']
                    ));
                    
                    if ($statement->fetchColumn() > 0)
                    throw new Exception('Podana restauracja istnieje już w tym miejscu. Podaj inne dane adresowe.');
     
                    // Sekcja zapytań dodająca wprowadzone dane do tabeli restaurants
                    $v_delivery_price = str_replace(',', '.', $v_delivery_price);
                    $query = "INSERT INTO restaurants (name, delivery_price, street, building_locale_nr, post_code, city) VALUES (?,?,?,?,?,?)";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $v_name_restaurant['value'],
                        $v_delivery_price['value'],
                        $v_street['value'],
                        $v_locale_no['value'],
                        $v_post_code['value'],
                        $v_city['value']
                    ));
                    
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
                'v_name_restaurant' => $v_name_restaurant,
                'v_delivery_price' => $v_delivery_price,
                'v_street' => $v_street,
                'v_locale_no' => $v_locale_no,
                'v_post_code' => $v_post_code,
                'v_city' => $v_city,
                'error' => $this->_error,
            );
        }
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    public function edit_restaurant()
    {
        $id_test = 9;
        if (isset($_POST['restaurant-button'])) {
            try {
                $v_name = Utils::validate_field_regex('restaurant-name', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{2,50}$/');
                $v_price = Utils::validate_field_regex('restaurant-delivery-price','/^([1-9][0-9]*|0)(\.[0-9]{2})?$/');
                $v_banner = Utils::validate_image_regex('restaurant-banner');
                $v_profile = Utils::validate_image_regex('restaurant-profile');
                $v_street = Utils::validate_field_regex('restaurant-street', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ ]{2,100}$/');
                $v_building_no = Utils::validate_field_regex('restaurant-building-no', '/^[0-9]{1,5}$/');
                $v_post_code = Utils::validate_field_regex('restaurant-post-code', '/^[0-9]{2}-[0-9]{3}$/');
                $v_city = Utils::validate_field_regex('restaurant-city', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{2,60}$/');

                if (!($v_name['invl'] || $v_price['invl'] || $v_banner['invl'] || $v_profile['invl'] || $v_street['invl'] ||
                    $v_building_no['invl'] || $v_post_code['invl'] || $v_city['invl'])) {
                    move_uploaded_file($v_banner['path'], 'publicUploads/restaurantsUploads/'.$id_test.'_banner.'.$v_banner['ext']);
                    move_uploaded_file($v_profile['path'], 'publicUploads/restaurantsUploads/'.$id_test.'_profile.'.$v_profile['ext']);
                }
                
            } catch (Exception $e) {
                $this->dbh->rollback();
                $this->_error = $e->getMessage();
            }
            $this->_error = 'Błąd!';
            // tak samo jak w authservice (w sensie wartości zwrócone w postaci array('value' => '', 'invl' => false, 'bts_class' => 'is-valid'))
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
}

    //--------------------------------------------------------------------------------------------------------------------------------------
