<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: RatingsService.php                             *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-14, 07:46:03                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-16 18:40:43                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Owner\Services;

use PDO;
use Exception;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\DishOrderModel;
use App\Models\OwnerRatingModel;
use App\Models\OwnerPendingToDeleteRatingModel;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\PaginationHelper;

ResourceLoader::load_model('DishOrderModel', 'dish');
ResourceLoader::load_model('OwnerRatingModel', 'rating');
ResourceLoader::load_model('OwnerPendingToDeleteRatingModel', 'rating');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('PaginationHelper');
ResourceLoader::load_service_helper('PaginationHelper');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class RatingsService extends MvcService
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
     * Metoda zwracająca oceny restauracji od użytkowników oraz filtrująca te oceny na podstawie najświeższych oraz po id restauracji, 
     * odwoływanie się parametrach GET.
     */
    public function get_restaurants_ratings()
    {
        $filter_ratings = $_GET['restaurant'] ?? 'all';
        $pagination = array();
        $pages_nav = array();
        $select_restaurants = array();
        $restaurants_grades = array();
        $notifs_grade_delete_types = array();
        try
        {
            $this->dbh->beginTransaction();

            $curr_page = $_GET['page'] ?? 1;
            $page = ($curr_page - 1) * 10;
            $total_per_page = $_GET['total'] ?? 10;

            $redirect_url = 'owner/ratings?restaurant=' . $filter_ratings . '&';
            PaginationHelper::check_parameters($redirect_url);

            $query = "SELECT id, name FROM notifs_grade_delete_types ORDER BY id";
            $statement = $this->dbh->prepare($query);
            $statement->execute();
            $notifs_grade_delete_types = $statement->fetchAll(PDO::FETCH_ASSOC);
            $statement->closeCursor();

            $select_restaurants = $this->get_restaurants_list($filter_ratings);
            $statement->closeCursor();
            $query = "
                SELECT g.id, ROW_NUMBER() OVER(ORDER BY give_on DESC) AS it, delivery_grade, g.description, give_on, order_id,
                IF(anonymously = 1, '<i>Anonimowy</i>', CONCAT(u.first_name, ' ', u.last_name)) AS signature, restaurant_grade,
                REPLACE(CAST(((delivery_grade + restaurant_grade) / 2) AS DECIMAL(10,1)), '.', ',') AS avg_grade,
                CONCAT('<strong>', r.name, '</strong>', ', ul. ', r.street, ' ', r.building_locale_nr, ', ', r.post_code, ' ', r.city) 
                AS delivery_restaurant, date_order, finish_order, TIMEDIFF(finish_order, date_order) AS date_diff, r.id AS res_id,
                IF(g.id NOT IN((SELECT rating_id FROM notifs_grades_to_delete)), 'widoczna', 'do usunięcia') AS status,
                IF(g.id NOT IN((SELECT rating_id FROM notifs_grades_to_delete)), 'text-success', 'text-danger') AS status_bts_class
                FROM (((restaurants_grades AS g
                INNER JOIN orders AS o ON g.order_id = o.id)
                INNER JOIN restaurants AS r ON o.restaurant_id = r.id)
                INNER JOIN users AS u ON o.user_id = u.id)
                WHERE (r.id = :resdata OR :resdata = 'all') AND accept = 1 AND r.user_id = :userresid
                ORDER BY give_on DESC LIMIT :total OFFSET :page
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('resdata', $filter_ratings);
            $statement->bindValue('userresid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
            $statement->bindValue('total', $total_per_page, PDO::PARAM_INT);
            $statement->bindValue('page', $page, PDO::PARAM_INT);
            $statement->execute();
            while ($row = $statement->fetchObject(OwnerRatingModel::class)) array_push($restaurants_grades, $row); 
            $statement->closeCursor();

            if (!empty($restaurants_grades))
            {
                $flatted_res = array_unique(array_map(function($res_grades) { return $res_grades->res_id; }, $restaurants_grades));
                $flatted_res = implode(", ", $flatted_res);
                $query = "
                    SELECT COUNT(d.id) AS dishes_count, o.restaurant_id AS res_id, d.name, d.id, d.description,
                    CONCAT(REPLACE(CAST(d.price AS DECIMAL(10,2)), '.', ','), ' zł') AS price,
                    CONCAT(CAST(SUM(d.price) AS DECIMAL(10,2)), ' zł') AS total_dish_cost
                    FROM ((orders_with_dishes AS od
                    INNER JOIN dishes AS d ON od.dish_id = d.id)
                    INNER JOIN orders AS o ON od.order_id = o.id)
                    GROUP BY order_id
                    HAVING o.restaurant_id IN (" . $flatted_res . ")
                ";
                $statement = $this->dbh->prepare($query);
                $statement->execute();
                $temp_dishes = array();
                while ($row = $statement->fetchObject(DishOrderModel::class)) array_push($temp_dishes, $row); 
                $statement->closeCursor();
                foreach ($temp_dishes as $dish)
                    foreach ($restaurants_grades as $res)
                        if ($dish->res_id = $res->res_id) array_push($res->order_dishes, array('dish' => $dish));
            }
            $query = "
                SELECT count(*) FROM ((restaurants_grades AS g
                INNER JOIN orders AS o ON g.order_id = o.id) INNER JOIN restaurants AS r ON o.restaurant_id = r.id)
                WHERE accept = 1 AND (r.id = :resdata OR :resdata = 'all') AND r.user_id = :userresid
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('resdata', $filter_ratings);
            $statement->bindValue('userresid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
            $statement->execute();
            $total_records = $statement->fetchColumn();

            $total_pages = ceil($total_records / $total_per_page);
            for ($i = 1; $i <= $total_pages; $i++) array_push($pagination, array(
                'it' => $i,
                'url' => $redirect_url . 'page=' . $i . '&total=' . $total_per_page,
                'selected' => $curr_page == $i ? 'active' : '',
            ));
            PaginationHelper::check_if_page_is_greaten_than($redirect_url, $total_pages);
            $pages_nav = PaginationHelper::get_pagination_nav($curr_page, $total_per_page, $total_pages, $total_records, $redirect_url);
            $statement->closeCursor();
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            SessionHelper::create_session_banner(SessionHelper::OWNER_RATINGS_PAGE_BANNER, $e->getMessage(), true);
            $this->dbh->rollback();
        }
        return array(
            'select_res' => $select_restaurants,
            'pagination_url' => $redirect_url,
            'pagination' => $pagination,
            'total_per_page' => $total_per_page,
            'pages_nav' => $pages_nav,
            'res_grades' => $restaurants_grades,
            'not_empty' => count($restaurants_grades),
            'delete_types' => $notifs_grade_delete_types,
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda umożliwiająca wysłanie żądania usunięcia oceny przez administratora systemu
     */
    public function send_request_to_delete_rating()
    {
        if (!isset($_GET['id'])) header('Location:' . __URL_INIT_DIR__ . 'owner/ratings', true, 301);
        try
        {
            $this->dbh->beginTransaction();
            $query = "
                SELECT pending_to_delete FROM ((restaurants_grades AS rg
                INNER JOIN orders AS o ON rg.order_id = o.id)
                INNER JOIN restaurants AS r ON o.restaurant_id = r.id)
                WHERE rg.id = ? AND r.user_id = ?
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id'], $_SESSION['logged_user']['user_id']));
            if ($statement->fetchColumn() == 1) throw new Exception('
                Dla podanej recenzji zostało wysłana już zgłoszenie do administratora systemu z prośbą o usunięcie lub podana recenzja
                została już usunięta.
            ');
            $query = "SELECT COUNT(*) > 0 FROM notifs_grade_delete_types WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_POST['rating-delete-reason']));
            if (!$statement->fetchColumn()) throw new Exception('
                Podany parametr ID typu zgłoszenia nie istnieje w systemie. Podaj inny parammetr.
            ');
            $query = "INSERT INTO notifs_grades_to_delete (description, type_id, rating_id) VALUES (NULLIF(?, ''),?,?)";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_POST['rating-own-delete-reason'], $_POST['rating-delete-reason'], $_GET['id']));

            $this->_banner_message = '
                Zostało wysłane zgłoszenie do administratorów systemu z prośbą o usunięcie recencji # ' . $_GET['id'] . '. Powierdzenie
                zostało wysłane również na Twój adres email. Aby przejść do wszystkich oczekujących na usunięcię recenzji
                <a href="' . __URL_INIT_DIR__ . 'owner/ratings/pending-to-delete" class="alert-link">przejdź po ten link</a>.
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
        SessionHelper::create_session_banner(SessionHelper::OWNER_RATINGS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda zwraca wszystkie opinie do usunięcia (wysłane zgłoszenia do administratorów)
     */
    public function get_pending_to_delete_ratings()
    {
        $filter_ratings = $_GET['restaurant'] ?? 'all';
        $pagination = array();
        $pages_nav = array();
        $select_restaurants = array();
        $pendings_to_delete = array();
        try
        {
            $this->dbh->beginTransaction();
            $curr_page = $_GET['page'] ?? 1;
            $page = ($curr_page - 1) * 10;
            $total_per_page = $_GET['total'] ?? 10;

            $redirect_url = 'owner/ratings/pending-to-delete?restaurant=' . $filter_ratings . '&';
            PaginationHelper::check_parameters($redirect_url);

            $select_restaurants = $this->get_restaurants_list($filter_ratings);
            $query = "
                SELECT ROW_NUMBER() OVER(ORDER BY give_on DESC) AS it, grd.id, IFNULL(grd.description, '<i>brak opisu</i>') AS description, 
                IF(anonymously = 1, '<i>Anonimowy</i>', CONCAT(u.first_name, ' ', u.last_name)) AS signature, t.name AS type, send_date
                FROM (((((notifs_grades_to_delete AS grd
                INNER JOIN notifs_grade_delete_types AS t ON grd.type_id = t.id)
                INNER JOIN restaurants_grades AS rg ON grd.rating_id = rg.id)
                INNER JOIN orders AS o ON rg.order_id = o.id)
                INNER JOIN restaurants AS r ON o.restaurant_id = r.id)
                INNER JOIN users AS u ON o.user_id = u.id)
                WHERE (r.id = :resdata OR :resdata = 'all') AND accept = 1 AND r.user_id = :userresid
                ORDER BY give_on DESC LIMIT :total OFFSET :page
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('resdata', $filter_ratings);
            $statement->bindValue('userresid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
            $statement->bindValue('total', $total_per_page, PDO::PARAM_INT);
            $statement->bindValue('page', $page, PDO::PARAM_INT);
            $statement->execute();
            while ($row = $statement->fetchObject(OwnerPendingToDeleteRatingModel::class)) array_push($pendings_to_delete, $row); 
            $statement->closeCursor();
            $query = "
                SELECT count(*) FROM (((notifs_grades_to_delete AS grd
                INNER JOIN restaurants_grades AS rg ON grd.rating_id = rg.id)
                INNER JOIN orders AS o ON rg.order_id = o.id)
                INNER JOIN restaurants AS r ON o.restaurant_id = r.id)
                WHERE (r.id = :resdata OR :resdata = 'all') AND accept = 1 AND r.user_id = :userresid
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('resdata', $filter_ratings);
            $statement->bindValue('userresid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
            $statement->execute();
            $total_records = $statement->fetchColumn();
            
            $total_pages = ceil($total_records / $total_per_page);
            for ($i = 1; $i <= $total_pages; $i++) array_push($pagination, array(
                'it' => $i,
                'url' => $redirect_url . 'page=' . $i . '&total=' . $total_per_page,
                'selected' => $curr_page == $i ? 'active' : '',
            ));
            PaginationHelper::check_if_page_is_greaten_than($redirect_url, $total_pages);
            $pages_nav = PaginationHelper::get_pagination_nav($curr_page, $total_per_page, $total_pages, $total_records, $redirect_url);
            $statement->closeCursor();
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::OWNER_RATINGS_PENDING_TO_DELETE, $e->getMessage(), true);
        }
        return array(
            'select_res' => $select_restaurants,
            'pagination_url' => $redirect_url,
            'pagination' => $pagination,
            'total_per_page' => $total_per_page,
            'pages_nav' => $pages_nav,
            'pendings_to_delete' => $pendings_to_delete,
            'not_empty' => count($pendings_to_delete),
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda usuwająca zgłoszenie do administratora z prośbą o usunięcie oceny.
     */
    public function delete_pending()
    {
        if (!isset($_GET['id'])) header('Location:' . __URL_INIT_DIR__ . 'owner/ratings/pending-to-delete', true, 301);
        try
        {
            $this->dbh->beginTransaction();
            $query = "
                SELECT gtd.rating_id FROM (((notifs_grades_to_delete AS gtd
                INNER JOIN restaurants_grades AS rg ON rg.id = gtd.rating_id)
                INNER JOIN orders AS o ON rg.order_id = o.id)
                INNER JOIN restaurants AS r ON o.restaurant_id = r.id)
                WHERE gtd.id = ? AND r.user_id = ?
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id'], $_SESSION['logged_user']['user_id']));
            if (!$statement->fetchColumn()) throw new Exception('
                Podane zgłoszenie z prośbą o usunięcie recencji zostało już zaakceptowane, zostało odrzucone, bądź nie istnieje w systemie.
            ');
            $query = "DELETE FROM notifs_grades_to_delete WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));

            $query = "UPDATE restaurants_grades SET pending_to_delete = 0 WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));

            $this->_banner_message = '
                Zgłoszenie <strong>#' . $_GET['id'] . '</strong> do administratorów systemu z prośbą o usunięcie opinii zostało anulowane.
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
        SessionHelper::create_session_banner(SessionHelper::OWNER_RATINGS_PENDING_TO_DELETE, $this->_banner_message, $this->_banner_error);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda zwracająca wszystkie restauracje przypisane do zalogowanego właściciela restauracji.
     */
    private function get_restaurants_list($filter_ratings)
    {
        $query = "
            SELECT id, CONCAT(name, ' (ul.', street, ' ', building_locale_nr, ', ', post_code, ' ', city, ')') AS name,
            IF(r.id = :rid, 'selected', '') AS selected FROM restaurants AS r WHERE user_id = :userid AND accept = 1
        ";
        $statement = $this->dbh->prepare($query);
        $statement->bindValue('rid', $filter_ratings);
        $statement->bindValue('userid', $_SESSION['logged_user']['user_id']);
        $statement->execute();
        $select_restaurants = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement->closeCursor();
        return $select_restaurants;
    }
}
