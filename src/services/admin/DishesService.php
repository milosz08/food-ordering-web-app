<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: DishesService.php                              *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-15, 22:06:26                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-16 20:26:43                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Admin\Services;

use PDO;
use Exception;
use App\Core\Config;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\DishDetailsModel;
use App\Services\Helpers\SessionHelper;

ResourceLoader::load_model('DishDetailsModel', 'dish');
ResourceLoader::load_service_helper('SessionHelper');

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
     * Metoda zwracająca szczegóły potrawy na podstawie ID.
     */
    public function get_dish_details()
    {
        $dish_details = new DishDetailsModel;
        try
        {
            $this->dbh->beginTransaction();
            $this->check_if_dish_is_valid();
            
            $query = "
                SELECT d.name, t.name AS type, d.description AS description, r.name AS r_name,
                IFNULL(d.photo_url, 'static/images/default-profile.jpg') AS photo_url,
                CONCAT('ul. ', street, ' ', building_locale_nr, ', ', post_code, ' ', city) AS r_address, 
                IF(delivery_price, CONCAT(REPLACE(CAST(delivery_price as DECIMAL(10,2)), '.', ','), ' zł'), 'za darmo') AS r_delivery_price,
                CONCAT(REPLACE(CAST(price as DECIMAL(10,2)), '.', ','), ' zł') AS price,
                CONCAT(u.first_name, ' ', u.last_name) AS r_full_name,
                REPLACE(CONCAT(IFNULL(delivery_price, 0) + price, ' zł'), '.', ',') AS total_price,
                t.user_id IS NOT NULL AS is_custom_type, CONCAT(prepared_time, ' minut') AS prepared_time
                FROM (((dishes AS d
                INNER JOIN restaurants AS r ON d.restaurant_id = r.id)
                INNER JOIN dish_types AS t ON d.dish_type_id = t.id)
                INNER JOIN users AS u ON r.user_id = u.id)
                WHERE d.id = :id
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('id', $_GET['dishid'], PDO::PARAM_INT);
            $statement->execute();

            $dish_details = $statement->fetchObject(DishDetailsModel::class);

            $statement->closeCursor();
            if ($this->dbh->inTransaction())  $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::ADMIN_DISH_DETAILS_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'error_redirect' => $this->_banner_error,
            'restaurant_id' => $_GET['resid'],
            'dish_id' => $_GET['dishid'],
            'details'=> $dish_details,
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda usuwająca wybraną potrawę z restauracji. Posiada zabezpieczenie, tylko potrawy nieprzypisane do aktywnych zamówień można
     * usunąć.
     */
    public function delete_dish()
    {
        $additional_comment = $_POST['delete-dish-comment'] ?? 'brak komentarza';
        try
        {
            $this->dbh->beginTransaction();
            $this->check_if_dish_is_valid();
            $query = "
                SELECT CONCAT(d.name, '#', d.id) AS d_name, CONCAT(r.name, '#', r.id) AS r_name, d.photo_url AS photo_url,
                CONCAT(u.first_name, ' ', u.last_name) AS full_name, u.email
                FROM ((dishes AS d
                INNER JOIN restaurants AS r ON d.restaurant_id = r.id)
                INNER JOIN users AS u ON u.id = r.user_id)
                WHERE d.id = :dishid AND 
                (SELECT COUNT(*) FROM orders_with_dishes AS od INNER JOIN orders AS o ON od.order_id = o.id
                WHERE dish_id = :dishid AND status_id = 1) = 0
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('dishid', $_GET['dishid'], PDO::PARAM_INT);
            $statement->execute();
            $deleted_dish = $statement->fetch(PDO::FETCH_ASSOC);
            if (!$deleted_dish) throw new Exception('
                Wybrana potrawa nie istnieje bądź istnieje przynajmniej jedno aktywne zamówienie posiadające tą potrawę.
            ');

            $query = "DELETE FROM dishes WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['dishid']));
            
            $email_request_vars = array(
                'dish_id' => $_GET['dishid'],
                'user_full_name' => $deleted_dish['full_name'],
                'delete_reason' => $additional_comment,
            );
            $subject = 'Usunięcie potrawy z ID #' . $_GET['dishid'];
            $this->smtp_client->send_message($deleted_dish['email'], $subject, 'remove-dish', $email_request_vars);

            if (file_exists($deleted_dish['photo_url'])) unlink($deleted_dish['photo_url']);
            $this->_banner_message = '
                Pomyślnie usunięto potrawę <strong>' . $deleted_dish['d_name'] . '</strong> z restauracji <strong>' . 
                $deleted_dish['r_name'] . '</strong> oraz wysłano na adres email właściciela restauracji informację o usunięciu potrawy.
            ';
            $statement->closeCursor();
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $this->_banner_error = true;
            $this->_banner_message = $e->getMessage();
        }
        SessionHelper::create_session_banner(SessionHelper::ADMIN_RESTAURANT_DETAILS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
        return $_GET['resid'];
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda odpowiadająca za usuwanie zdjęcia potrawy, na podstawie id potrawy w parametrze GET dishid
     */
    public function delete_dish_image()
    {
        $additional_comment = $_POST['delete-dish-image-comment'] ?? 'brak komentarza';
        try
        {
            $this->dbh->beginTransaction();
            $this->check_if_dish_is_valid();

            $query = "
                SELECT d.photo_url, CONCAT(u.first_name, ' ', u.last_name) AS full_name, u.email FROM ((dishes AS d
                INNER JOIN restaurants AS r ON r.id = d.restaurant_id)
                INNER JOIN users AS u ON r.user_id = u.id) WHERE d.id = ?
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['dishid']));
            $data = $statement->fetch(PDO::FETCH_ASSOC);
            if (empty($data)) throw new Exception('Wybrana potrawa nie posiada żadnego zdjęcia.');

            $query = "UPDATE dishes SET photo_url = NULL WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['dishid']));

            $email_request_vars = array(
                'dish_id' => $_GET['dishid'],
                'user_full_name' => $data['full_name'],
                'delete_reason' => $additional_comment,
            );
            $subject = 'Usunięcie grafiki potrawy z ID #' . $_GET['dishid'];
            $this->smtp_client->send_message($data['email'], $subject, 'remove-dish-image', $email_request_vars);

            if (file_exists($data['photo_url'])) unlink($data['photo_url']);
            $this->_banner_message = 'Pomyślnie usunięto zdjęcie wybranej potrawy oraz wysłano wiadomość do właściciela restauracji.';
            $statement->closeCursor();
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->_banner_error = true;
            $this->_banner_message = $e->getMessage();
            $this->dbh->rollback();
        }
        SessionHelper::create_session_banner(SessionHelper::ADMIN_DISH_DETAILS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
        return 'admin/dishes/dish-details?resid=' . $_GET['resid'] . '&dishid=' . $_GET['dishid'];
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda sprawdzająca, czy potrawa na podstawie id pobieranego z parametrów GET zapytania istnieje.
     */
    private function check_if_dish_is_valid()
    {
        if (!isset($_GET['resid'])) header('Location:' . __URL_INIT_DIR__ . 'admin/restaurants', true, 301);

        $query = "SELECT COUNT(*) FROM restaurants WHERE id = :id AND accept = 1";
        $statement = $this->dbh->prepare($query);
        $statement->bindValue('id', $_GET['resid'], PDO::PARAM_INT);
        $statement->execute();
        if ($statement->fetchColumn() == 0)
        {
            $message = 'Wybrana restauracja nie istnieje lub nie została jeszcze aktywowana.';
            $this->dbh->rollback();
            $statement->closeCursor();
            SessionHelper::create_session_banner(SessionHelper::ADMIN_RESTAURANTS_PAGE_BANNER, $message, true);
            header('Location:' . __URL_INIT_DIR__ . 'admin/restaurants', true, 301);
            die;
        }

        if (!isset($_GET['dishid']) && isset($_GET['resid']))
        {
            header('Location:' . __URL_INIT_DIR__ . 'admin/restaurants/restaurant-details?id=' . $_GET['resid'], true, 301);
        }
        $query = "SELECT COUNT(*) FROM dishes WHERE id = :id";
        $statement = $this->dbh->prepare($query);
        $statement->bindValue('id', $_GET['dishid'], PDO::PARAM_INT);
        $statement->execute();
        if ($statement->fetchColumn() == 0)
        {
            $message = 'Wybrana potrawa nie istnieje w systemie.';
            $this->dbh->rollback();
            $statement->closeCursor();
            SessionHelper::create_session_banner(SessionHelper::ADMIN_RESTAURANT_DETAILS_PAGE_BANNER, $message, true);
            header('Location:' . __URL_INIT_DIR__ . 'admin/restaurants/restaurant-details?id=' . $_GET['resid'], true, 301);
            die;
        }
        $statement->closeCursor();
    }
}
