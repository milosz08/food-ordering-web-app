<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: AdminService.php                               *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-12-06, 15:20:33                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2022-12-23 22:07:27                   *
 * Modyfikowany przez: patrick012016                           *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services;

use PDO;
use Exception;

use App\Utils\Utils;
use App\Core\Config;
use App\Core\MvcService;
use App\Models\RestaurantModel;
use App\Models\AcceptationModel;

class AdminService extends MvcService
{
    private $_banner_message;
    private $_show_banner = false;
    private $_banner_error = false;
    private $_if_banner_error;

    //--------------------------------------------------------------------------------------------------------------------------------------

    protected function __construct()
    {
        parent::__construct();
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda odpowiadająca za wyświetlanie panelu wraz z listą restauracji do zaakceptowania.
     */
    public function show_accept_restaurants()
    {
        $pagination = array(); // tablica przechowująca liczby przekazywane do dynamicznego tworzenia elementów paginacji
        $waiting_restaurants = array(); // tablica

        try {
            $this->dbh->beginTransaction();

            $res_index = 1; // index restauracji w tabeli
            $thispage = $_GET['page'] ?? 0; // pobranie indeksu paginacji
            $page = $thispage * 5;
            $elements = $_GET['el'] ?? 5;

            if (isset($_POST['search-res-button']))
                $like = $_POST['search-res-name'];
            else
                $like = "";


            // zapytanie do bazy danych, które zwróci poszczególne wartości wszystkich restauracji dla obecnie zalogowanego użytkownika
            $query = "SELECT r.id, CONCAT(u.first_name, ' ', u.last_name) AS full_name, r.name, CONCAT(r.street,' ', r.building_locale_nr, ' ',  r.post_code, ' ', r.city)
            AS address FROM restaurants r INNER JOIN users u ON r.user_id = u.id WHERE name LIKE CONCAT ('%', :search, '%') 
            AND accept = 0 LIMIT :el OFFSET :p";

            $statement = $this->dbh->prepare($query);
            $statement->bindParam(':el', $elements, PDO::PARAM_INT);
            $statement->bindParam(':p', $page, PDO::PARAM_INT);
            $statement->bindParam(':search', $like, PDO::PARAM_STR);
            $statement->execute();


            // 'while' odpowiadada za przejście przez wszystkie znaleznione rekordy
            while ($restaurant = $statement->fetchObject(AcceptationModel::class)) {
                // wkładanie do tablicy $user_restaurant poszczególnych restauracji wraz z ich numerem w kolejności
                array_push(
                    $waiting_restaurants,
                    array(
                        'res' => $restaurant,
                        'status' => array(
                            'text' => empty($restaurant->accept) ? 'oczekująca' : 'aktywna',
                            'color_bts' => empty($restaurant->accept) ? 'text-danger' : 'text-success'
                        ),
                        'iterator' => $res_index + $page,
                    )
                );
                $res_index++;
            }

            // zapytanie zliczające wszystkie restauracje przypisane do użytkownika
            $query = "SELECT count(id) FROM restaurants";
            $statement = $this->dbh->prepare($query);
            $statement->execute();
            $res_sum_number = $statement->fetchColumn();

            $i = 0; // zmienna pomocnicza
            // W zależności od posiadanych restauracji podzielonych przez 6, tyle razy wykona się pętla 
            while ($i < (($res_sum_number + 1) / $elements)) {
                // dodawanie iteracji do tablicy $pagination
                array_push($pagination, array(
                    'page' => $i + 1,
                    'i' => $i,
                    'previous' => $i - 1
                )
                );
                $i++;
            }

            $previous = $thispage;
            $next = $thispage;
            // Obsługa strzałek w paginacji
            if ($thispage > 0)
                $previous = $thispage - 1;
            if ($thispage < $i - 1)
                $next = $thispage + 1;
            $this->dbh->commit();
        } catch (Exception $e) {
            $this->dbh->rollback();
            $this->_banner_message = $e->getMessage();
        }
        return array(
            'element' => $elements,
            'previous' => $previous,
            'next' => $next,
            'elm_count' => 5,
            'pagination' => $pagination,
            'waiting_restaurants' => $waiting_restaurants
        );
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda odpowiadająca za akceptację wybranej restauracji z tabeli.
     */
    public function accept_restaurant()
    {
        if (!isset($_GET['id']))
            header('Location:' . __URL_INIT_DIR__ . 'admin/panel/restaurant/accept', true, 301);
        try {
            $this->dbh->beginTransaction();

            $query = "UPDATE restaurants SET accept = 1 WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));

            $this->_banner_message = 'Pomyślnie zaakceptowano wybraną restaurację.';
            $statement->closeCursor();
            $this->dbh->commit();
        } catch (Exception $e) {
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
     * Metoda odpowiadająca za odrzucanie a tym samym usuwanie danej restauracji.
     */
    public function reject_restaurant()
    {
        if (!isset($_GET['id']))
            header('Location:' . __URL_INIT_DIR__ . 'admin/panel/restaurant/accept', true, 301);
        try {
            $this->dbh->beginTransaction();

            $query = "SELECT COUNT(*) FROM restaurants WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));

            if ($statement->fetchColumn() == 0)
                throw new Exception('Podana resturacja nie istnieje w systemie lub została już odrzucona.');

            $query = "DELETE FROM restaurants WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));

            $this->_banner_message = 'Pomyślnie odrzucono wybraną restaurację z systemu.';
            $statement->closeCursor();
            $this->dbh->commit();
        } catch (Exception $e) {
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
}
