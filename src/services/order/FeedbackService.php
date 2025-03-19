<?php

namespace App\Services\Order;

use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\Rating\AddEditRestaurantGradeModel;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\ValidationHelper;
use Exception;

ResourceLoader::load_model('AddEditRestaurantGradeModel', 'Rating');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('ValidationHelper');

class FeedbackService extends MvcService
{
  private $_banner_message = '';
  private $_banner_error = false;

  protected function __construct()
  {
    parent::__construct();
  }

  /**
   * Metoda umożliwiająca dodanie oceny do restauracji po dokonanym zamówieniu.
   */
  public function give_a_mark_for_restaurant()
  {
    if (!isset($_GET['id'])) {
      header('Location:' . __URL_INIT_DIR__ . 'user/orders', true, 301);
    }
    $grade = new AddEditRestaurantGradeModel;
    try {
      $this->dbh->beginTransaction();
      $query = "
        SELECT COUNT(*) FROM orders AS o WHERE id = ? AND status_id = 2 AND user_id = ?
        AND (SELECT COUNT(*) FROM restaurants_grades WHERE order_id = o.id) = 0
      ";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['id'], $_SESSION['logged_user']['user_id']));
      if ($statement->fetchColumn() == 0) {
        $message = 'Niewłaściwe ID zamówienia. Zamówienie nie spełnia wymagań zamówienia możliwego do oceny.';
        SessionHelper::create_session_banner(SessionHelper::USER_ORDERS_PAGE_BANNER, $message, true);
        if ($this->dbh->inTransaction()) {
          $this->dbh->commit();
        }
        header('Location:' . __URL_INIT_DIR__ . 'user/orders', true, 301);
        die;
      }
      $grade->res_grade_stars['data'] = array_reverse($grade->res_grade_stars['data']);
      $grade->delivery_grade_stars['data'] = array_reverse($grade->delivery_grade_stars['data']);
      if (isset($_POST['add-edit-mark'])) {
        $grade->description = ValidationHelper::validate_field_regex('grade-description', '/^.{10,350}$/');
        if (!isset($_POST['res-grade-stars'])) {
          $grade->res_grade_stars['invalid'] = true;
        }
        if (!isset($_POST['delivery-grade-stars'])) {
          $grade->delivery_grade_stars['invalid'] = true;
        }
        $grade->anonymous_is_checked = isset($_POST['grade-is-anonymous']) ? 'checked' : '';
        if ($grade->all_is_valid()) {
          $query = "
            INSERT INTO restaurants_grades (restaurant_grade, delivery_grade, description, anonymously, order_id)
            VALUES (?,?,NULLIF(?, ''),?,?)
          ";
          $statement = $this->dbh->prepare($query);
          $statement->execute(array(
            $_POST['res-grade-stars'][0], $_POST['delivery-grade-stars'][0], $grade->description['value'],
            isset($_POST['grade-is-anonymous']), $_GET['id'],
          ));
          $statement->closeCursor();
          if ($this->dbh->inTransaction()) {
            $this->dbh->commit();
          }
          $this->_banner_message = 'Ocena restauracji została pomyślnie dodana.';
          SessionHelper::create_session_banner(SessionHelper::USER_ORDER_DETAILS_PAGE_BANNER, $this->_banner_message, false);
          header('Location:' . __URL_INIT_DIR__ . 'user/orders/order-details?id=' . $_GET['id'], true, 301);
        }
      }
      if ($this->dbh->inTransaction()) {
        $this->dbh->commit();
      }
    } catch (Exception $e) {
      $this->dbh->rollback();
      SessionHelper::create_session_banner(SessionHelper::FEEDBACK_GIVE_FEEDBACK_PAGE_BANNER, $e->getMessage(), true);
    }
    return array(
      'order_id' => $_GET['id'],
      'grade' => $grade,
    );
  }

  /**
   * Metoda umożliwiająca edytowanie ówcześnie dodanej oceny do restauracji po dokonanym zamówieniu.
   */
  public function edit_mark_for_restaurant()
  {
    if (!isset($_GET['id'])) {
      header('Location:' . __URL_INIT_DIR__ . 'user/orders', true, 301);
    }
    $grade = new AddEditRestaurantGradeModel;
    try {
      $this->dbh->beginTransaction();
      $query = "
        SELECT rg.id, restaurant_grade, delivery_grade, description, anonymously, order_id
        FROM restaurants_grades AS rg
        INNER JOIN orders AS o ON rg.order_id = o.id
        WHERE user_id = ? AND rg.id = ?
      ";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_SESSION['logged_user']['user_id'], $_GET['id']));
      $grade = $statement->fetchObject(AddEditRestaurantGradeModel::class);
      if (!$grade) {
        header('Location:' . __URL_INIT_DIR__ . 'user/orders', true, 301);
        die;
      }
      for ($i = 0; $i < count($grade->res_grade_stars['data']); $i++) {
        $is_checked = '';
        if ($i + 1 <= $grade->restaurant_grade) {
          $is_checked = 'checked';
        }
        $grade->res_grade_stars['data'][$i]['checked'] = $is_checked;
      }
      for ($i = 0; $i < count($grade->delivery_grade_stars['data']); $i++) {
        $is_checked = '';
        if ($i + 1 <= $grade->delivery_grade) {
          $is_checked = 'checked';
        }
        $grade->delivery_grade_stars['data'][$i]['checked'] = $is_checked;
      }
      $grade->res_grade_stars['data'] = array_reverse($grade->res_grade_stars['data']);
      $grade->delivery_grade_stars['data'] = array_reverse($grade->delivery_grade_stars['data']);
      $grade->anonymous_is_checked = $grade->anonymously ? 'checked' : '';
      if (isset($_POST['add-edit-mark'])) {
        $grade->description = ValidationHelper::validate_field_regex('grade-description', '/^.{10,350}$/');
        if (!isset($_POST['res-grade-stars'])) {
          $grade->res_grade_stars['invalid'] = true;
        }
        if (!isset($_POST['delivery-grade-stars'])) {
          $grade->delivery_grade_stars['invalid'] = true;
        }
        $grade->anonymous_is_checked = isset($_POST['grade-is-anonymous']) ? 'checked' : '';
        if ($grade->all_is_valid()) {
          $query = "
            UPDATE restaurants_grades SET restaurant_grade = ?, delivery_grade = ?, description = NULLIF(?, ''),
            anonymously = ? WHERE id = ?
          ";
          $statement = $this->dbh->prepare($query);
          $statement->execute(array(
            $_POST['res-grade-stars'][0], $_POST['delivery-grade-stars'][0], $grade->description['value'],
            isset($_POST['grade-is-anonymous']), $_GET['id'],
          ));
          $statement->closeCursor();
          if ($this->dbh->inTransaction()) {
            $this->dbh->commit();
          }
          $this->_banner_message = 'Ocena restauracji została pomyślnie zmieniona.';
          SessionHelper::create_session_banner(SessionHelper::USER_ORDER_DETAILS_PAGE_BANNER, $this->_banner_message, false);
          header('Location:' . __URL_INIT_DIR__ . 'user/orders/order-details?id=' . $grade->order_id, true, 301);
        }
      }
      $statement->closeCursor();
      if ($this->dbh->inTransaction()) {
        $this->dbh->commit();
      }
    } catch (Exception $e) {
      $this->dbh->rollback();
      SessionHelper::create_session_banner(SessionHelper::FEEDBACK_EDIT_FEEDBACK_PAGE_BANNER, $e->getMessage(), true);
    }
    return array(
      'order_id' => $grade->order_id,
      'grade' => $grade,
    );
  }

  /**
   * Metoda umożliwiająca usunięcie ówcześnie dodanej oceny do restauracji po dokonanym zamówieniu.
   */
  public function delete_mark_from_restaurant()
  {
    $order_id = null;
    if (!isset($_GET['id'])) {
      header('Location:' . __URL_INIT_DIR__ . 'user/orders', true, 301);
    }
    try {
      $this->dbh->beginTransaction();

      $query = "
        SELECT order_id FROM restaurants_grades AS rg INNER JOIN orders AS o ON rg.order_id = o.id
        WHERE user_id = ? AND rg.id = ?
      ";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_SESSION['logged_user']['user_id'], $_GET['id']));
      $order_id = $statement->fetchColumn();
      if (!$order_id) {
        header('Location:' . __URL_INIT_DIR__ . 'user/orders', true, 301);
        die;
      }
      $query = "DELETE FROM restaurants_grades WHERE id = ?";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['id']));

      $this->_banner_message = 'Ocena restauracji została pomyślnie usunięta.';
      if ($this->dbh->inTransaction()) {
        $this->dbh->commit();
      }
    } catch (Exception $e) {
      $this->dbh->rollback();
      $this->_banner_error = true;
      $this->_banner_message = $e->getMessage();
    }
    SessionHelper::create_session_banner(SessionHelper::USER_ORDER_DETAILS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
    return $order_id;
  }
}
