<?php

namespace App\Services\Admin;

use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\Dish\DishModel;
use App\Models\Restaurant\RestaurantAdminModel;
use App\Models\Restaurant\RestaurantHourModel;
use App\Models\Restaurant\RestaurantModel;
use App\Services\Helpers\PaginationHelper;
use App\Services\Helpers\SessionHelper;
use Exception;
use PDO;

ResourceLoader::load_model('DishModel', 'dish');
ResourceLoader::load_model('RestaurantModel', 'restaurant');
ResourceLoader::load_model('RestaurantHourModel', 'restaurant');
ResourceLoader::load_model('RestaurantAdminModel', 'restaurant');

ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('PaginationHelper');

class RestaurantsService extends MvcService
{
  private $_banner_message = '';
  private $_banner_error = false;

  protected function __construct()
  {
    parent::__construct();
  }

  /**
   * Metoda odpowiadająca za tworzenie tabeli w zakładce 'Restauracje do usunięcia'.
   * Tabela przechowuje kolejno wszystkie restauracje z bazy danych.
   * Tabela została wzbogacona o funkcję paginacji, wyświetlającej tylko 5 elementów na jednej ze stron.
   */
  public function get_restaurants(): array
  {
    $pagination = array();
    $restaurants = array();
    $pages_nav = array();
    try {
      $this->dbh->beginTransaction();

      $curr_page = $_GET['page'] ?? 1; // pobranie indeksu paginacji
      $page = ($curr_page - 1) * 10;
      $total_per_page = $_GET['total'] ?? 10;
      $search_text = SessionHelper::persist_search_text('search-res-name', SessionHelper::ADMIN_RESTAURANTS_SEARCH);

      $redirect_url = 'admin/restaurants';
      PaginationHelper::check_parameters($redirect_url);

      // zapytanie do bazy danych, które zwróci poszczególne wartości wszystkich restauracji z bazy
      $query = "
        SELECT ROW_NUMBER() OVER(ORDER BY r.id) as it, name, accept, r.id, CONCAT(first_name, ' ', last_name) AS full_name,
        CONCAT('ul. ', street, ' ', building_locale_nr, ', ', post_code, ' ', city) AS address
        FROM restaurants AS r INNER JOIN users AS u ON r.user_id = u.id WHERE name LIKE :search LIMIT :total OFFSET :page
      ";
      $statement = $this->dbh->prepare($query);
      $statement->bindValue('search', '%' . $search_text . '%');
      $statement->bindValue('total', $total_per_page, PDO::PARAM_INT);
      $statement->bindValue('page', $page, PDO::PARAM_INT);
      $statement->execute();

      while ($row = $statement->fetchObject(RestaurantModel::class)) {
        $restaurants[] = $row;
      }
      $query = "SELECT count(*) FROM restaurants WHERE name LIKE :search";
      $statement = $this->dbh->prepare($query);
      $statement->bindValue('search', '%' . $search_text . '%');
      $statement->execute();
      $total_records = $statement->fetchColumn();

      $total_pages = ceil($total_records / $total_per_page);
      for ($i = 1; $i <= $total_pages; $i++) {
        $pagination[] = array(
          'it' => $i,
          'url' => $redirect_url . '?page=' . $i . '&total=' . $total_per_page,
          'selected' => $curr_page == $i ? 'active' : '',
        );
      }
      $statement->closeCursor();
      PaginationHelper::check_if_page_is_greater_than($redirect_url, $total_pages);
      $pages_nav = PaginationHelper::get_pagination_nav($curr_page, $total_per_page, $total_pages, $total_records, $redirect_url);
      if ($this->dbh->inTransaction()) {
        $this->dbh->commit();
      }
    } catch (Exception $e) {
      $this->dbh->rollback();
      SessionHelper::create_session_banner(SessionHelper::ADMIN_RESTAURANTS_PAGE_BANNER, $e->getMessage(), true);
    }
    return array(
      'total_per_page' => $total_per_page,
      'pagination_url' => $redirect_url . '?',
      'pagination' => $pagination,
      'pages_nav' => $pages_nav,
      'user_restaurants' => $restaurants,
      'search_text' => $search_text,
      'not_empty' => count($restaurants),
    );
  }

  /**
   * Metoda odpowiadająca za pobranie szczegółów dań wybranej restauracji z bazy danych i zwrócenie ich do widoku.
   */
  public function get_restaurant_details(): array
  {
    if (!isset($_GET['id'])) {
      header('Location:' . __URL_INIT_DIR__ . 'admin/restaurants', true, 301);
    }
    $restaurant_details = new RestaurantAdminModel;
    $pagination = array();
    $restaurant_dishes = array();
    $res_hours = array();
    $pages_nav = array();
    try {
      $this->dbh->beginTransaction();

      $curr_page = $_GET['page'] ?? 1; // pobranie indeksu paginacji
      $page = ($curr_page - 1) * 10;
      $total_per_page = $_GET['total'] ?? 10;
      $search_text = SessionHelper::persist_search_text('search-dish-name', SessionHelper::ADMIN_RES_DISHES_SEARCH);

      $redirect_url = 'admin/restaurants/restaurant-details?id=' . $_GET['id'];
      PaginationHelper::check_parameters($redirect_url);

      $restaurant_query = "
        SELECT r.id, name, accept, description, building_locale_nr, street, post_code, city,
        IFNULL(r.profile_url, 'static/images/default-profile.jpg') AS profile_url,
        IFNULL(r.banner_url, 'static/images/default-banner.jpg') AS banner_url,
        CONCAT(first_name, ' ', last_name) AS full_name,
        IF(delivery_price, CONCAT(REPLACE(CAST(delivery_price AS DECIMAL(10,2)), '.', ','), ' zł'), 'za darmo') AS delivery_price,
        CONCAT('ul. ', street, ' ', building_locale_nr, ', ', post_code, ' ', city) AS address,
        (SELECT COUNT(*) FROM dishes WHERE restaurant_id = r.id) AS count_of_dishes,
        CONCAT(SUBSTRING(r.phone_number, 1, 3), ' ', SUBSTRING(r.phone_number, 3, 3), ' ', SUBSTRING(r.phone_number, 6, 3)) AS phone_number,
        IF(min_price, CONCAT(REPLACE(CAST(min_price AS DECIMAL(10,2)), '.', ','), ' zł'), 'brak najniższej ceny') AS min_price,
        IFNULL(NULLIF((SELECT COUNT(*) FROM discounts WHERE restaurant_id = r.id), 0), 'brak rabatów') AS discounts_count
        FROM restaurants AS r
        INNER JOIN users AS u ON r.user_id = u.id
        WHERE r.id = :id
      ";
      $statement = $this->dbh->prepare($restaurant_query);
      $statement->bindValue('id', $_GET['id'], PDO::PARAM_INT);
      $statement->execute();
      $restaurant_details = $statement->fetchObject(RestaurantAdminModel::class);
      if (!$restaurant_details) {
        $this->_banner_message = 'Wybrana restauracja nie istnieje.';
        SessionHelper::create_session_banner(SessionHelper::ADMIN_RESTAURANTS_PAGE_BANNER, $this->_banner_message, true);
        $statement->closeCursor();
        $this->dbh->commit();
        header('Location:' . __URL_INIT_DIR__ . 'admin/restaurants');
      }

      // pobieranie danych na podstawie wszystkich dni tygodnia, kiedy restauracja jest czynna (zapytania złożone i podzapytania)
      $hours_query = "
        SELECT w.alias AS alias, w.name AS name, w.name_eng AS identifier,
        IFNULL((SELECT DATE_FORMAT(open_hour, '%H:%i') FROM restaurant_hours WHERE restaurant_id = :resid AND weekday_id = w.id),
        'nieczynne') AS open_hour,
        IFNULL((SELECT DATE_FORMAT(close_hour, '%H:%i') FROM restaurant_hours WHERE restaurant_id = :resid AND weekday_id = w.id),
        'nieczynne') AS close_hour,
        (SELECT NOT COUNT(*) > 0 FROM restaurant_hours WHERE restaurant_id = :resid AND weekday_id = w.id) AS is_closed
        FROM weekdays AS w
        ORDER BY w.id
      ";
      $statement = $this->dbh->prepare($hours_query);
      $statement->bindValue('resid', $_GET['id']);
      $statement->execute();
      while ($row = $statement->fetchObject(RestaurantHourModel::class)) {
        $res_hours[] = $row->format_to_details_view();
      }
      // zapytanie do bazy danych, które zwróci poszczególne dania dla obecnie wybranej restauracji
      $dishes_query = "
        SELECT ROW_NUMBER() OVER(ORDER BY d.id) as it, d.id, d.name, t.name AS type, d.description,
        CONCAT(REPLACE(CAST(d.price AS DECIMAL(10,2)), '.', ','), ' zł') AS price
        FROM dishes AS d
        INNER JOIN dish_types AS t ON dish_type_id = t.id
        WHERE restaurant_id = :id AND d.name LIKE :search LIMIT :total OFFSET :page
      ";
      $statement = $this->dbh->prepare($dishes_query);
      $statement->bindValue('id', $_GET['id']);
      $statement->bindValue('search', '%' . $search_text . '%');
      $statement->bindValue('total', $total_per_page, PDO::PARAM_INT);
      $statement->bindValue('page', $page, PDO::PARAM_INT);
      $statement->execute();
      while ($row = $statement->fetchObject(DishModel::class)) {
        $restaurant_dishes[] = $row;
      }
      $query = "SELECT count(*) FROM dishes WHERE restaurant_id = :id AND name LIKE :search";
      $statement = $this->dbh->prepare($query);
      $statement->bindValue('id', $_GET['id']);
      $statement->bindValue('search', '%' . $search_text . '%');
      $statement->execute();
      $total_records = $statement->fetchColumn();

      $total_pages = ceil($total_records / $total_per_page);
      for ($i = 1; $i <= $total_pages; $i++) {
        $pagination[] = array(
          'it' => $i,
          'url' => $redirect_url . '&page=' . $i . '&total=' . $total_per_page,
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
      SessionHelper::create_session_banner(SessionHelper::RESTAURANT_DETAILS_PAGE_BANNER, $e->getMessage(), true);
    }
    return array(
      'details' => $restaurant_details,
      'res_hours' => $res_hours,
      'res_id' => $_GET['id'],
      'total_per_page' => $total_per_page,
      'pagination_url' => $redirect_url . '&',
      'pagination' => $pagination,
      'pages_nav' => $pages_nav,
      'restaurant_dishes' => $restaurant_dishes,
      'search_text' => $search_text,
      'not_empty' => count($restaurant_dishes),
    );
  }

  /**
   * Metoda usuwająca zdjęcie w tle (baner) lub zdjęcie profilowe restauracji wybranej na podstawie id przekazywanego w parametrze GET
   * zapytania oraz parametrów metody. Ustawia również wartość NULL w kolumnie przechowującej link do grafiki.
   */
  public function delete_restaurant_image($image_column_name, $deleted_type, $additional_comment): string
  {
    $redirect_url = 'admin/restaurants';
    $additional_comment = $_POST['delete-restaurant-' . $additional_comment . '-comment'] ?? 'brak komentarza';
    if (!isset($_GET['id'])) {
      return $redirect_url;
    }
    try {
      $this->dbh->beginTransaction();

      $query = "SELECT COUNT(*) FROM restaurants WHERE id = ? AND accept = 1";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['id']));
      if ($statement->fetchColumn() == 0) {
        throw new Exception('Podana restauracja nie istnieje w systemie, została wcześniej usunięta lub nie została jeszcze aktywowana.');
      }
      $redirect_url .= '/restaurant-details?id=' . $_GET['id'];

      $query = "SELECT $image_column_name FROM restaurants WHERE id = ? AND accept = 1";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['id']));
      $result = $statement->fetchColumn();
      if (!$result) {
        throw new Exception('Podana resturacja nie posiada typu zdjęcia <strong>' . $deleted_type . '</strong>.');
      }
      $statement->closeCursor();

      $query = "
        SELECT CONCAT(first_name, ' ', last_name) AS full_name, email FROM users AS u
        INNER JOIN restaurants AS r ON r.user_id = u.id WHERE r.id = ?
      ";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['id']));
      $mail_data = $statement->fetch(PDO::FETCH_ASSOC);

      $query = "UPDATE restaurants SET $image_column_name = NULL WHERE id = ?";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['id']));

      $email_request_vars = array(
        'res_id' => $_GET['id'],
        'user_full_name' => $mail_data['full_name'],
        'delete_reason' => $additional_comment,
      );
      $subject = 'Usunięcie zdjęcia restauracji z ID #' . $_GET['id'];
      $this->smtp_client->send_message($mail_data['email'], $subject, 'remove-restaurant-image', $email_request_vars);

      if (file_exists($result)) {
        unlink($result);
      }
      $this->_banner_message = 'Pomyślnie usunięto ' . $deleted_type . ' z wybranej restauracji z systemu.';
      $statement->closeCursor();
      if ($this->dbh->inTransaction()) {
        $this->dbh->commit();
      }
    } catch (Exception $e) {
      $this->dbh->rollback();
      $this->_banner_message = $e->getMessage();
      $this->_banner_error = true;
    }
    SessionHelper::create_session_banner(SessionHelper::ADMIN_RESTAURANT_DETAILS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
    return $redirect_url;
  }

  /**
   * Metoda odpowiadająca za usuwanie wybranej restauracji z systemu. Metoda sprawdza, czy nie ma żadnych aktywnych zamówień związanych z
   * tą restauracją. Jeśli nie, administratora może opcjonalnie wysłać wiadomość do właściciela restauracji z powodem usunięcia.
   */
  public function delete_restaurant()
  {
    if (!isset($_GET['id'])) {
      return;
    }
    $additional_comment = $_POST['delete-restaurant-comment'] ?? 'brak komentarza';
    try {
      $this->dbh->beginTransaction();
      $query = "
        SELECT COUNT(*) FROM restaurants WHERE id = :resid AND
        (SELECT COUNT(*) FROM orders WHERE restaurant_id = :resid AND status_id = 1) = 0
      ";
      $statement = $this->dbh->prepare($query);
      $statement->bindValue('resid', $_GET['id'], PDO::PARAM_INT);
      $statement->execute();
      $result = $statement->fetchColumn();
      if (empty($result)) {
        throw new Exception('
          Podana restauracja nie istnieje w systemie, została wcześniej usunięta lub posiada aktywne zamówienia. Tylko restaurację
          które nie posiadają aktywnych zamówień można usunąć z systemu.
        ');
      }
      $query = "
        SELECT CONCAT(first_name, ' ', last_name) AS full_name, email FROM users AS u
        INNER JOIN restaurants AS r ON r.user_id = u.id WHERE r.id = ?
      ";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['id']));
      $mail_data = $statement->fetch(PDO::FETCH_ASSOC);

      $query = "DELETE FROM restaurants WHERE id = ?";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['id']));

      $email_request_vars = array(
        'res_id' => $_GET['id'],
        'user_full_name' => $mail_data['full_name'],
        'delete_reason' => $additional_comment,
      );
      $subject = 'Usunięcie restauracji z ID #' . $_GET['id'];
      $this->smtp_client->send_message($mail_data['email'], $subject, 'remove-restaurant', $email_request_vars);

      rmdir('uploads/restaurants/' . $_GET['id']);
      $this->_banner_message = 'Pomyślnie usunięto wybraną restaurację z systemu.';
      $statement->closeCursor();
      if ($this->dbh->inTransaction()) {
        $this->dbh->commit();
      }
    } catch (Exception $e) {
      $this->dbh->rollback();
      $this->_banner_message = $e->getMessage();
      $this->_banner_error = true;
    }
    SessionHelper::create_session_banner(SessionHelper::ADMIN_RESTAURANTS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
  }
}
