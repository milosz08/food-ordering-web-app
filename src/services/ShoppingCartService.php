<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: ShoppingCartService.php                        *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-11, 22:15:43                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-13 01:45:06                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services;

use Exception;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Helpers\CookieHelper;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_service_helper('CookieHelper');
ResourceLoader::load_service_helper('SessionHelper');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class ShoppingCartService extends MvcService
{
    private $_banner_message = '';
    private $_banner_error = false;

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    protected function __construct()
    {
        parent::__construct();
    }

    private function validate()
    {
        // Walidacja id restauracji w linku
        if (isset($_GET['resid']))
            $res_id = $_GET['resid'];
        else
            header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
        $this->dbh->beginTransaction();
        $query = "
        SELECT COUNT(*) > 0 FROM ((restaurants AS r
        INNER JOIN restaurant_hours AS h ON r.id = h.restaurant_id)
        INNER JOIN weekdays AS wk ON h.weekday_id = wk.id)
        WHERE wk.name_eng = LOWER(DAYNAME(NOW())) AND h.open_hour <= CURTIME() AND h.close_hour >= CURTIME()
        AND r.id = ? AND accept = 1
    ";
        $statement = $this->dbh->prepare($query);
        $statement->execute(array($res_id));
        $res_id_exist = $statement->fetchColumn();
        if (!$res_id_exist) {
            $this->_banner_message = 'Wybrana restauracja nie istnieje, bądź nie jest otwarta.';
            SessionHelper::create_session_banner(SessionHelper::HOME_RESTAURANTS_LIST_PAGE_BANNER, $this->_banner_message, true);
            header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
            die;
        }
        // Walidacja id dania dla podanej restauracji
        if (isset($_GET['dishid']))
            $dish_id = $_GET['dishid'];
        else
            header('Location:' . __URL_INIT_DIR__ . 'restaurants/restaurant-details?id=' . $res_id_exist->id, true, 301);

        $query = "SELECT COUNT(*) > 0 FROM dishes WHERE restaurant_id = ? AND id = ?";
        $statement = $this->dbh->prepare($query);
        $statement->execute(array($res_id, $dish_id));
        $dish_id_exist = $statement->fetchColumn();
        if (!$dish_id_exist) {
            $this->_banner_message = 'Wybrana potrawa nie istnieje, bądź nie jest przypisana do żadnej restauracji.';
            SessionHelper::create_session_banner(SessionHelper::HOME_RESTAURANTS_LIST_PAGE_BANNER, $this->_banner_message, true);
            header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
            die;
        }
        $statement->closeCursor();
        $this->dbh->commit();
        return $dish_id;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function add_dish_to_shopping_cart()
    {
        try {
            $dish_id = $this->validate();

            // Obsługa koszyka
            $tempArray = array();
            $codeName = "";
            $il = 1;
            // Flaga sprawdzająca czy dany element w tablicy już się tam znajduje. Gdy dany element jest w tablicy jego ilość zostaje 
            // inkrementowana, a nie zostaje dodany jako nowy obiekt
            $isElementInArray = true;
            if (isset($_COOKIE[CookieHelper::get_shopping_cart_name($_GET['resid'])])) {
                $tempArray = json_decode($_COOKIE[CookieHelper::get_shopping_cart_name($_GET['resid'])]);

                // Nowa tablica pomocnicza, do której element nie zostaje dodany, w momencie, gdy jego dekrementowana wartość 'il'
                // będzie kolejno mniejsza niż 1.
                $new_json_array = array();

                // Pętla iteruje po elementach sprawdzając, który został wybrany, aby jego ilość została zinkrementowana
                foreach ($tempArray as $a) {
                    if ($a->dishid == $dish_id) {
                        $a->count += 1;
                        $isElementInArray = false;
                    }
                    // Dodanie każdego z elementu do nowej tablicy.
                    array_push($new_json_array, $a);
                }
                // Sprawdzanie, czy dany element istnieje w tablicy
                if ($isElementInArray) {
                    // Dodanie nowego elementu do tablicy i przypisanie mu kolejno wartości.
                    array_push($tempArray, array('dishid' => $dish_id, 'count' => $il, 'code' => $codeName));
                    CookieHelper::set_non_expired_cookie(CookieHelper::get_shopping_cart_name($_GET['resid']), json_encode($tempArray));
                } else
                    CookieHelper::set_non_expired_cookie(CookieHelper::get_shopping_cart_name($_GET['resid']), json_encode($new_json_array));
            }
            // Jeżeli plik cookies nie został jeszcze utworzony, dodajemy elementy do tablicy i tworzymy nowe cookies. 
            else {
                array_push($tempArray, array('dishid' => $dish_id, 'count' => $il, 'code' => $codeName));
                CookieHelper::set_non_expired_cookie(CookieHelper::get_shopping_cart_name($_GET['resid']), json_encode($tempArray));
            }

        } catch (Exception $e) {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::HOME_RESTAURANTS_LIST_PAGE_BANNER, $e->getMessage(), true);
        }
        return $_GET['resid'];
    }


    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function remove_dish_from_shopping_cart()
    {
        $dish_id = $this->validate();
        // Flaga sprawdzająca czy dany element w tablicy już się tam znajduje. Gdy dany element jest w tablicy jego ilość zostaje 
        // inkrementowana, a nie zostaje dodany jako nowy obiekt
        $isElementInArray = true;
        // Flaga sprawdzająca czy ilość danych elementów jest większa czy mniejsza niż 1, aby kolejno zinkrementować jego wartość
        // bądź nie dodawać go do nowej tablicy 
        $isCountHigherThan1 = true;

        if (isset($_COOKIE[CookieHelper::get_shopping_cart_name($_GET['resid'])])) {
            $tempArray = json_decode($_COOKIE[CookieHelper::get_shopping_cart_name($_GET['resid'])]);
            $new_json_array = array();
            // Pętla iterująca elementy w koszyku
            foreach ($tempArray as $a) {
                // Przypisanie kodu rabatowego jeżeli ten istnieje do świeżo dodanego dania
                if (!empty($a->code)) {
                    $codeName = $a->code;
                    foreach ($tempArray as $pom) {
                        $pom->code = $codeName;
                    }
                }
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
                if ($isCountHigherThan1)
                    array_push($new_json_array, $a);
                // jeżeli dany element jest mniejszy od 1, to nie dodajemy go do nowej tablicy i kasujemy flagę 
                // na następny element 
                else
                    $isCountHigherThan1 = true;
            }
            CookieHelper::set_non_expired_cookie(CookieHelper::get_shopping_cart_name($_GET['resid']), json_encode($new_json_array));
            if (empty($new_json_array))
            CookieHelper::delete_cookie(CookieHelper::get_shopping_cart_name($_GET['resid']));
        }
        

        return $_GET['resid'];
    }
}
