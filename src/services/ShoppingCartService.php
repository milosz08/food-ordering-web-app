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
 * Ostatnia modyfikacja: 2023-01-13 08:50:06                   *
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

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function add_dish_to_shopping_cart()
    {
        try
        {
            $this->check_if_restaurant_exist();
            // Obsługa koszyka
            $temp_array = array('code' => '', 'dishes' => array());
            $cookie = $_COOKIE[CookieHelper::get_shopping_cart_name($_GET['resid'])] ?? null;
            // Flaga sprawdzająca czy dany element w tablicy już się tam znajduje. Gdy dany element jest w tablicy jego ilość zostaje 
            // inkrementowana, a nie zostaje dodany jako nowy obiekt
            $isElementInArray = true;
            if (isset($cookie))
            {
                $temp_array = json_decode($cookie, true);
                // Nowa tablica pomocnicza, do której element nie zostaje dodany, w momencie, gdy jego dekrementowana wartość 'il'
                // będzie kolejno mniejsza niż 1.
                $new_json_array = array('code' => '', 'dishes' => array());
                // Pętla iteruje po elementach sprawdzając, który został wybrany, aby jego ilość została zinkrementowana
                foreach ($temp_array['dishes'] as $a)
                {
                    if ($a['dishid'] == $_GET['dishid'])
                    {
                        $a['count'] += 1;
                        $isElementInArray = false;
                    }
                    // Dodanie każdego z elementu do nowej tablicy.
                    array_push($new_json_array['dishes'], $a);
                }
                // Sprawdzanie, czy dany element istnieje w tablicy
                if ($isElementInArray)
                {
                    // Dodanie nowego elementu do tablicy i przypisanie mu kolejno wartości.
                    array_push($temp_array['dishes'], array('dishid' => $_GET['dishid'], 'count' => 1));
                    CookieHelper::set_non_expired_cookie(CookieHelper::get_shopping_cart_name($_GET['resid']), json_encode($temp_array));
                }
                else CookieHelper::set_non_expired_cookie(CookieHelper::get_shopping_cart_name($_GET['resid']), json_encode($new_json_array));
            }
            // Jeżeli plik cookies nie został jeszcze utworzony, dodajemy elementy do tablicy i tworzymy nowe cookies. 
            else
            {
                array_push($temp_array['dishes'], array('dishid' => $_GET['dishid'], 'count' => 1));
                CookieHelper::set_non_expired_cookie(CookieHelper::get_shopping_cart_name($_GET['resid']), json_encode($temp_array));
            }
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::HOME_RESTAURANTS_LIST_PAGE_BANNER, $e->getMessage(), true);
        }
        return $_GET['resid'];
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function delete_dish_from_shopping_cart()
    {
        try
        {
            $this->check_if_restaurant_exist();
            // Flaga sprawdzająca czy ilość danych elementów jest większa czy mniejsza niż 1, aby kolejno zinkrementować jego wartość
            // bądź nie dodawać go do nowej tablicy 
            $is_count_higher_than_1 = true;
            $cookie = $_COOKIE[CookieHelper::get_shopping_cart_name($_GET['resid'])] ?? null;
            if (isset($cookie))
            {
                $temp_array = json_decode($cookie, true);
                $new_json_array = array('code' => '', 'dishes' => array());
                // Pętla iterująca elementy w koszyku
                foreach ($temp_array['dishes'] as $a)
                {
                    // Jeżeli dany element pasuje po id, do wybranego elementu
                    if ($a['dishid'] == $_GET['dishid'])
                    {
                        // Sprawdzenie, czy dany element jest większy od 1, gdy jest to po prostu odejmujemy od niego 1
                        if ($a['count'] > 1) $a['count'] -= 1;
                        // W przeciwnym wypadku flaga zostaje ustawiona na 'false', aby element nie został dodany do nowej tablicy                 
                        else $is_count_higher_than_1 = false;
                    }
                    // Jeżeli dany element nie jest mniejszy od 1, to włożymy go do nowej tablicy
                    if ($is_count_higher_than_1) array_push($new_json_array['dishes'], $a);
                    // jeżeli dany element jest mniejszy od 1, to nie dodajemy go do nowej tablicy i kasujemy flagę 
                    // na następny element 
                    else $is_count_higher_than_1 = true;
                }
                CookieHelper::set_non_expired_cookie(CookieHelper::get_shopping_cart_name($_GET['resid']), json_encode($new_json_array));
                if (empty($new_json_array['dishes'])) CookieHelper::delete_cookie(CookieHelper::get_shopping_cart_name($_GET['resid']));
            }
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::HOME_RESTAURANTS_LIST_PAGE_BANNER, $e->getMessage(), true);
        }
        return $_GET['resid'];
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function delete_all_dishes_from_shopping_cart()
    {
        if (!isset($_GET['id'])) header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
        if (isset($_COOKIE[CookieHelper::get_shopping_cart_name($_GET['id'])]))
        {
            CookieHelper::delete_cookie(CookieHelper::get_shopping_cart_name($_GET['id']));
            SessionHelper::create_session_banner(SessionHelper::RESTAURANT_DISHES_PAGE_BANNER, 'Koszyk został wyczyszczony.', false);
        }
        return $_GET['id'];
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function check_if_restaurant_exist()
    {
        if (!isset($_GET['resid'])) header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
        $this->dbh->beginTransaction();
        $query = "
            SELECT COUNT(*) > 0 FROM ((restaurants AS r
            INNER JOIN restaurant_hours AS h ON r.id = h.restaurant_id)
            INNER JOIN weekdays AS wk ON h.weekday_id = wk.id)
            WHERE wk.name_eng = LOWER(DAYNAME(NOW())) AND h.open_hour <= CURTIME() AND h.close_hour >= CURTIME()
            AND r.id = ? AND accept = 1
        ";
        $statement = $this->dbh->prepare($query);
        $statement->execute(array($_GET['resid']));
        if (!$statement->fetchColumn()) {
            $this->_banner_message = 'Wybrana restauracja nie istnieje, bądź nie jest otwarta.';
            SessionHelper::create_session_banner(SessionHelper::HOME_RESTAURANTS_LIST_PAGE_BANNER, $this->_banner_message, true);
            header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
            die;
        }
        if (!isset($_GET['dishid'])) // Walidacja id dania dla podanej restauracji
        {
            header('Location:' . __URL_INIT_DIR__ . 'restaurants/restaurant-details?id=' . $_GET['resid'], true, 301);
        }
        $query = "SELECT COUNT(*) > 0 FROM dishes WHERE restaurant_id = ? AND id = ?";
        $statement = $this->dbh->prepare($query);
        $statement->execute(array($_GET['resid'], $_GET['dishid']));
        if (!$statement->fetchColumn())
        {
            $this->_banner_message = 'Wybrana potrawa nie istnieje, bądź nie jest przypisana do żadnej restauracji.';
            SessionHelper::create_session_banner(SessionHelper::HOME_RESTAURANTS_LIST_PAGE_BANNER, $this->_banner_message, true);
            header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);
            die;
        }
        $statement->closeCursor();
        $this->dbh->commit();
    }
}
