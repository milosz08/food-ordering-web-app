<?php

namespace App\Services\Admin;

use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\Rating\AdminPendingToDeleteRatingModel;
use App\Models\Rating\OwnerRatingModel;
use App\Services\Helpers\PaginationHelper;
use App\Services\Helpers\SessionHelper;
use Exception;
use PDO;

ResourceLoader::load_model('DishOrderModel', 'Dish');
ResourceLoader::load_model('OwnerRatingModel', 'Rating');
ResourceLoader::load_model('AdminPendingToDeleteRatingModel', 'Rating');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('PaginationHelper');

class RatingsService extends MvcService
{
  private $_banner_message = '';
  private $_banner_error = false;

  protected function __construct()
  {
    parent::__construct();
  }

  /**
   * Metoda zwracająca wszystkie oceny ze wszystkich restauracji, z możliwością ich natychmiastowego usunięcia.
   */
  public function get_all_ratings_from_all_restaurants(): array
  {
    $filter_ratings = $_GET['restaurant'] ?? 'all';
    $pagination = array();
    $pages_nav = array();
    $select_restaurants = array();
    $restaurants_grades = array();
    $notifs_grade_delete_types = array();
    try {
      $this->dbh->beginTransaction();

      $curr_page = $_GET['page'] ?? 1;
      $page = ($curr_page - 1) * 10;
      $total_per_page = $_GET['total'] ?? 10;

      $redirect_url = 'admin/ratings?restaurant=' . $filter_ratings . '&';
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
        AS delivery_restaurant, date_order, finish_order, TIMEDIFF(finish_order, date_order) AS date_diff, r.id AS res_id
        FROM (((restaurants_grades AS g
        INNER JOIN orders AS o ON g.order_id = o.id)
        INNER JOIN restaurants AS r ON o.restaurant_id = r.id)
        INNER JOIN users AS u ON o.user_id = u.id)
        WHERE (r.id = :resData OR :resData = 'all') AND accept = 1
        ORDER BY give_on DESC LIMIT :total OFFSET :page
      ";
      $statement = $this->dbh->prepare($query);
      $statement->bindValue('resData', $filter_ratings);
      $statement->bindValue('total', $total_per_page, PDO::PARAM_INT);
      $statement->bindValue('page', $page, PDO::PARAM_INT);
      $statement->execute();
      while ($row = $statement->fetchObject(OwnerRatingModel::class)) {
        $restaurants_grades[] = $row;
      }
      $statement->closeCursor();
      $query = "
        SELECT count(*) FROM ((restaurants_grades AS g
        INNER JOIN orders AS o ON g.order_id = o.id) INNER JOIN restaurants AS r ON o.restaurant_id = r.id)
        WHERE accept = 1 AND (r.id = :resData OR :resData = 'all')
      ";
      $statement = $this->dbh->prepare($query);
      $statement->bindValue('resData', $filter_ratings);
      $statement->execute();
      $total_records = $statement->fetchColumn();

      $total_pages = ceil($total_records / $total_per_page);
      for ($i = 1; $i <= $total_pages; $i++) {
        $pagination[] = array(
          'it' => $i,
          'url' => $redirect_url . 'page=' . $i . '&total=' . $total_per_page,
          'selected' => $curr_page == $i ? 'active' : '',
        );
      }
      PaginationHelper::check_if_page_is_greater_than($redirect_url, $total_pages);
      $pages_nav = PaginationHelper::get_pagination_nav($curr_page, $total_per_page, $total_pages, $total_records, $redirect_url);
      $statement->closeCursor();
      if ($this->dbh->inTransaction()) {
        $this->dbh->commit();
      }
    } catch (Exception $e) {
      $this->dbh->rollback();
      SessionHelper::create_session_banner(SessionHelper::ADMIN_RATINGS_PAGE_BANNER, $e->getMessage(), true);
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

  /**
   * Metoda zwracająca wszystkie restauracje.
   */
  private function get_restaurants_list($filter_ratings): array
  {
    $query = "
      SELECT id, CONCAT(name, ' (ul.', street, ' ', building_locale_nr, ', ', post_code, ' ', city, ')') AS name,
      IF(r.id = :rid, 'selected', '') AS selected FROM restaurants AS r WHERE accept = 1
    ";
    $statement = $this->dbh->prepare($query);
    $statement->bindValue('rid', $filter_ratings);
    $statement->execute();
    $select_restaurants = $statement->fetchAll(PDO::FETCH_ASSOC);
    $statement->closeCursor();
    return $select_restaurants;
  }

  /**
   * Metoda zwraca wszystkie oceny ze wszystkich restauracji z możliwością akceptowania lub odrzucenia ich usunięcia przez zgłoszenie
   * przychodzące od właściciela restauracji.
   */
  public function get_all_rating_from_pending_to_delete(): array
  {
    $filter_ratings = $_GET['restaurant'] ?? 'all';
    $pagination = array();
    $pages_nav = array();
    $select_restaurants = array();
    $pending_to_delete = array();
    try {
      $this->dbh->beginTransaction();
      $curr_page = $_GET['page'] ?? 1;
      $page = ($curr_page - 1) * 10;
      $total_per_page = $_GET['total'] ?? 10;

      $redirect_url = 'admin/ratings/pending-to-delete?restaurant=' . $filter_ratings . '&';
      PaginationHelper::check_parameters($redirect_url);

      $select_restaurants = $this->get_restaurants_list($filter_ratings);
      $query = "
        SELECT ROW_NUMBER() OVER(ORDER BY give_on DESC) AS it, grd.id, IFNULL(grd.description, '<i>brak opisu</i>') AS description_s,
        rg.description AS description, CONCAT(ur.first_name, ' ', ur.last_name) AS sender,
        IF(anonymously = 1, '<i>Anonimowy</i>', CONCAT(u.first_name, ' ', u.last_name)) AS signature, t.name AS type, send_date,
        CONCAT('<strong>', r.name, '</strong>', ', ul. ', r.street, ' ', r.building_locale_nr, ', ', r.post_code, ' ', r.city)
        AS delivery_restaurant, restaurant_grade, date_order, finish_order, TIMEDIFF(finish_order, date_order) AS date_diff,
        REPLACE(CAST(((delivery_grade + restaurant_grade) / 2) AS DECIMAL(10,1)), '.', ',') AS avg_grade, delivery_grade, give_on
        FROM ((((((notifs_grades_to_delete AS grd
        INNER JOIN notifs_grade_delete_types AS t ON grd.type_id = t.id)
        INNER JOIN restaurants_grades AS rg ON grd.grade_id = rg.id)
        INNER JOIN orders AS o ON rg.order_id = o.id)
        INNER JOIN restaurants AS r ON o.restaurant_id = r.id)
        INNER JOIN users AS u ON o.user_id = u.id)
        INNER JOIN users AS ur ON r.user_id = ur.id)
        WHERE (r.id = :resData OR :resData = 'all') AND accept = 1
        ORDER BY give_on DESC LIMIT :total OFFSET :page
      ";
      $statement = $this->dbh->prepare($query);
      $statement->bindValue('resData', $filter_ratings);
      $statement->bindValue('total', $total_per_page, PDO::PARAM_INT);
      $statement->bindValue('page', $page, PDO::PARAM_INT);
      $statement->execute();
      while ($row = $statement->fetchObject(AdminPendingToDeleteRatingModel::class)) {
        $pending_to_delete[] = $row;
      }
      $statement->closeCursor();
      $query = "
        SELECT count(*) FROM (((notifs_grades_to_delete AS grd
        INNER JOIN restaurants_grades AS rg ON grd.grade_id = rg.id)
        INNER JOIN orders AS o ON rg.order_id = o.id)
        INNER JOIN restaurants AS r ON o.restaurant_id = r.id)
        WHERE (r.id = :resData OR :resData = 'all') AND accept = 1 AND r.user_id = :userResId
      ";
      $statement = $this->dbh->prepare($query);
      $statement->bindValue('resData', $filter_ratings);
      $statement->bindValue('userResId', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
      $statement->execute();
      $total_records = $statement->fetchColumn();

      $total_pages = ceil($total_records / $total_per_page);
      for ($i = 1; $i <= $total_pages; $i++) {
        $pagination[] = array(
          'it' => $i,
          'url' => $redirect_url . 'page=' . $i . '&total=' . $total_per_page,
          'selected' => $curr_page == $i ? 'active' : '',
        );
      }
      PaginationHelper::check_if_page_is_greater_than($redirect_url, $total_pages);
      $pages_nav = PaginationHelper::get_pagination_nav($curr_page, $total_per_page, $total_pages, $total_records, $redirect_url);
      if ($this->dbh->inTransaction()) {
        $this->dbh->commit();
      }
    } catch (Exception $e) {
      $this->dbh->rollback();
      SessionHelper::create_session_banner(SessionHelper::ADMIN_RATINGS_PENDING_TO_DELETE, $e->getMessage(), true);
    }
    return array(
      'select_res' => $select_restaurants,
      'pagination_url' => $redirect_url,
      'pagination' => $pagination,
      'total_per_page' => $total_per_page,
      'pages_nav' => $pages_nav,
      'pending_to_delete' => $pending_to_delete,
      'not_empty' => count($pending_to_delete),
    );
  }

  /**
   * Metoda umożliwiająca usunięcie opinii.
   */
  public function delete_rating()
  {
    if (!isset($_GET['id'])) {
      return;
    }
    try {
      $this->dbh->beginTransaction();
      $query = "SELECT COUNT(*) FROM restaurants_grades WHERE id = ?";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['id']));
      if ($statement->fetchColumn() == 0) {
        throw new Exception('Ocena z podanym ID nie istnieje bądź została już usunięta.');
      }
      $query = "DELETE FROM restaurants_grades WHERE id = ?";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['id']));

      $this->_banner_message = 'Ocena z ID <strong>#' . $_GET['id'] . '</strong> została pomyślnie usunięta z systemu.';
      if ($this->dbh->inTransaction()) {
        $this->dbh->commit();
      }
    } catch (Exception $e) {
      $this->dbh->rollback();
      $this->_banner_error = true;
      $this->_banner_message = $e->getMessage();
    }
    SessionHelper::create_session_banner(SessionHelper::ADMIN_RATINGS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
  }

  /**
   * Metoda akceptująca zgłoszenie o usunięcie opinii wysłane przez właściciela restauracji i usuwająca tą opinię z wybranej restauracji.
   */
  public function accept_pending_delete_rating()
  {
    if (!isset($_GET['id'])) {
      return;
    }
    try {
      $this->dbh->beginTransaction();
      $query = "SELECT grade_id FROM notifs_grades_to_delete WHERE id = ?";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['id']));
      $grade_id = $statement->fetchColumn();
      if (!$grade_id) {
        throw new Exception('Zgłoszenie z podanym ID nie istnieje bądź zostało już rozwiązane.');
      }
      $query = "DELETE FROM notifs_grades_to_delete WHERE id = ?";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['id']));
      $query = "DELETE FROM restaurants_grades WHERE id = ?";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($grade_id));

      $query = "
        SELECT CONCAT(first_name, ' ', last_name) AS full_name, email FROM (((users AS u
        INNER JOIN restaurants AS r ON r.user_id = u.id)
        INNER JOIN orders AS o ON o.restaurant_id = r.id)
        INNER JOIN restaurants_grades AS rg ON o.id = rg.order_id)
        WHERE rg.id = ?
      ";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['id']));
      $mail_data = $statement->fetch(PDO::FETCH_ASSOC);

      $email_request_vars = array(
        'grade_id' => $_GET['id'],
        'user_full_name' => $mail_data['full_name'],
      );
      $subject = 'Zaakceptowanie usunięcia opinii z ID #' . $_GET['id'];
      $this->smtp_client->send_message($mail_data['email'], $subject, 'accept-delete-grade', $email_request_vars);

      $this->_banner_message = '
                Ocena z ID <strong>#' . $_GET['id'] . '</strong> została pomyślnie usunięta z systemu oraz została wysłana wiadomość
                email do zgłaszającego właściciela restauracji.
            ';
      if ($this->dbh->inTransaction()) {
        $this->dbh->commit();
      }
    } catch (Exception $e) {
      $this->dbh->rollback();
      $this->_banner_error = true;
      $this->_banner_message = $e->getMessage();
    }
    SessionHelper::create_session_banner(SessionHelper::ADMIN_RATINGS_PENDING_TO_DELETE, $this->_banner_message, $this->_banner_error);
  }

  /**
   * Metoda odrzucająca zgłoszenie o usunięcie opinii wysłanej przez właściciela restauracji i wysyłająca na adres email informację z
   * dodatkowym powodem nieusunięcia.
   */
  public function reject_pending_delete_rating()
  {
    if (!isset($_GET['id'])) {
      return;
    }
    $additional_comment = $_POST['rating-reject-reason'];
    try {
      $this->dbh->beginTransaction();
      $query = "SELECT COUNT(*) FROM notifs_grades_to_delete WHERE id = ?";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['id']));
      if ($statement->fetchColumn() == 0) {
        throw new Exception('Zgłoszenie z podanym ID nie istnieje bądź zostało już rozwiązane.');
      }
      $query = "
        SELECT CONCAT(first_name, ' ', last_name) AS full_name, email FROM (((users AS u
        INNER JOIN restaurants AS r ON r.user_id = u.id)
        INNER JOIN orders AS o ON o.restaurant_id = r.id)
        INNER JOIN restaurants_grades AS rg ON o.id = rg.order_id)
        WHERE rg.id = ?
      ";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['id']));
      $mail_data = $statement->fetch(PDO::FETCH_ASSOC);

      $email_request_vars = array(
        'grade_id' => $_GET['id'],
        'user_full_name' => $mail_data['full_name'],
        'delete_reason' => $additional_comment,
      );
      $subject = 'Odrzucenie usunięcia opinii z ID #' . $_GET['id'];
      $this->smtp_client->send_message($mail_data['email'], $subject, 'reject-delete-grade', $email_request_vars);

      $query = "DELETE FROM notifs_grades_to_delete WHERE id = ?";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['id']));

      $this->_banner_message = '
        Usunięcie oceny z ID <strong>#' . $_GET['id'] . '</strong> zostało pomyślnie odrzucone oraz została wysłana wiadomość
        email do zgłaszającego właściciela restauracji.
      ';
      if ($this->dbh->inTransaction()) {
        $this->dbh->commit();
      }
    } catch (Exception $e) {
      $this->dbh->rollback();
      $this->_banner_error = true;
      $this->_banner_message = $e->getMessage();
    }
    SessionHelper::create_session_banner(SessionHelper::ADMIN_RATINGS_PENDING_TO_DELETE, $this->_banner_message, $this->_banner_error);
  }
}
