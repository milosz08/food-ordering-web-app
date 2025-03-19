<?php

namespace App\Services\Owner;

use App\Core\Config;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\Discount\AddEditDiscountModel;
use App\Models\Discount\DiscountRowModel;
use App\Models\Discount\ResDiscountsRowModel;
use App\Services\Helpers\AuthHelper;
use App\Services\Helpers\PaginationHelper;
use App\Services\Helpers\RestaurantsHelper;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\ValidationHelper;
use Exception;
use PDO;

ResourceLoader::load_service_helper('AuthHelper');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('PaginationHelper');
ResourceLoader::load_service_helper('RestaurantsHelper');
ResourceLoader::load_service_helper('ValidationHelper');
ResourceLoader::load_model('DiscountRowModel', 'Discount');
ResourceLoader::load_model('ResDiscountsRowModel', 'Discount');
ResourceLoader::load_model('AddEditDiscountModel', 'Discount');

class DiscountsService extends MvcService
{
  private $_banner_message = '';
  private $_banner_error = false;

  protected function __construct()
  {
    parent::__construct();
  }

  /**
   * Metoda odpowiedzialna za zwracanie wszystkich kodów rabatowych stworzonych przez zalogowanego właściciela restauracji.
   */
  public function get_all_discounts_codes(): array
  {
    $discount_codes = array();
    $pages_nav = array();
    $pagination = array();
    try {
      $curr_page = $_GET['page'] ?? 1;
      $page = ($curr_page - 1) * 10;
      $total_per_page = $_GET['total'] ?? 10;
      $search_text = SessionHelper::persist_search_text('search-discount-code', SessionHelper::DISCOUNT_SEARCH);

      $redirect_url = 'owner/discounts';
      PaginationHelper::check_parameters($redirect_url);

      $this->dbh->beginTransaction();
      $query = "
        SELECT ROW_NUMBER() OVER(ORDER BY id) as it, ds.id, code, ds.description,
        CONCAT(REPLACE(CAST(percentage_discount as DECIMAL(10,2)), '.', ','), '%') AS percentage_discount,
        CONCAT(usages, '/', IFNULL(max_usages, '∞')) AS total_usages,
        IF(max_usages, '', 'disabled') AS increase_usages_active, IF(expired_date, '', 'disabled') AS increase_time_active,
        IFNULL(expired_date, '∞') AS expired_date, r.name AS res_name, r.id AS res_id,
        CONCAT(SUBSTRING(code, 1, 3), '*******') AS hide_code,
        IF((SELECT COUNT(*) > 0 FROM discounts WHERE restaurant_id = r.id AND ((expired_date > NOW() OR
        expired_date IS NULL) AND (usages < max_usages OR max_usages IS NULL))), 'aktywny', 'wygasły') AS status,
        IF((SELECT COUNT(*) > 0 FROM discounts WHERE restaurant_id = r.id AND ((expired_date > NOW() OR
        expired_date IS NULL) AND (usages < max_usages OR max_usages IS NULL))), 'text-success', 'text-danger') AS expired_bts_class
        FROM discounts AS ds INNER JOIN restaurants AS r ON ds.restaurant_id = r.id
        WHERE r.accept = 1 AND r.user_id = :userid AND ds.code LIKE :search LIMIT :total OFFSET :page
      ";
      $statement = $this->dbh->prepare($query);
      $statement->bindValue('userid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
      $statement->bindValue('search', '%' . $search_text . '%');
      $statement->bindValue('total', $total_per_page, PDO::PARAM_INT);
      $statement->bindValue('page', $page, PDO::PARAM_INT);
      $statement->execute();
      while ($row = $statement->fetchObject(DiscountRowModel::class)) {
        $discount_codes[] = $row;
      }
      $query = "
        SELECT count(*) FROM discounts AS d INNER JOIN restaurants AS r ON d.restaurant_id = r.id
        WHERE r.accept = 1 AND user_id = :userid AND name LIKE :search
      ";
      $statement = $this->dbh->prepare($query);
      $statement->bindValue('userid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
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
      PaginationHelper::check_if_page_is_greater_than($redirect_url, $total_pages);
      $pages_nav = PaginationHelper::get_pagination_nav($curr_page, $total_per_page, $total_pages, $total_records, $redirect_url);
      $statement->closeCursor();
      if ($this->dbh->inTransaction()) {
        $this->dbh->commit();
      }
    } catch (Exception $e) {
      $this->dbh->rollback();
      SessionHelper::create_session_banner(SessionHelper::DISCOUNTS_PAGE_BANNER, $e->getMessage(), true);
    }
    return array(
      'discounts' => $discount_codes,
      'total_per_page' => $total_per_page,
      'pagination_url' => $redirect_url . '?',
      'pagination' => $pagination,
      'pagination_visible' => true,
      'pages_nav' => $pages_nav,
      'search_text' => $search_text,
      'not_empty' => count($discount_codes),
      'redirect' => '&redirect=' . $redirect_url,
    );
  }

  /**
   * Metoda odpowiedzialna za wyświetlanie zaakceptowanych restauracji z możliwością dodania kodu rabatowego do nich.
   */
  public function get_restaurants_with_discounts_codes(): array
  {
    $res_discount_codes = array();
    $pagination_data = array('pagination' => array(), 'pages_nav' => array());
    try {
      $curr_page = $_GET['page'] ?? 1;
      $page = ($curr_page - 1) * 10;
      $total_per_page = $_GET['total'] ?? 10;
      $search_text = SessionHelper::persist_search_text('search-discount-code', SessionHelper::DISCOUNT_RES_SEARCH);

      $redirect_url = 'owner/discounts/discounts-with-restaurants';
      PaginationHelper::check_parameters($redirect_url);

      $this->dbh->beginTransaction();
      $query = "
        SELECT ROW_NUMBER() OVER(ORDER BY r.id) as it, id, name,
        (SELECT COUNT(*) FROM discounts WHERE restaurant_id = r.id) AS all_discounts,
        (SELECT COUNT(*) FROM discounts WHERE restaurant_id = r.id AND ((expired_date > NOW() AND expired_date IS NOT NULL)
        OR (usages < max_usages AND max_usages IS NOT NULL))) AS count_of_active_discounts,
        CONCAT('ul. ', street, ' ', building_locale_nr, ', ', post_code, ' ', city) AS address
        FROM restaurants AS r
        WHERE user_id = :userid AND accept = 1 AND name LIKE :search LIMIT :total OFFSET :page
      ";
      $statement = $this->dbh->prepare($query);
      $statement->bindValue('userid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
      $statement->bindValue('search', '%' . $search_text . '%');
      $statement->bindValue('total', $total_per_page, PDO::PARAM_INT);
      $statement->bindValue('page', $page, PDO::PARAM_INT);
      $statement->execute();
      while ($row = $statement->fetchObject(ResDiscountsRowModel::class)) {
        $res_discount_codes[] = $row;
      }
      $pagination_data = RestaurantsHelper::get_total_res_pages($this->dbh, $search_text, $total_per_page, $curr_page, $redirect_url);
      $statement->closeCursor();
      if ($this->dbh->inTransaction()) {
        $this->dbh->commit();
      }
    } catch (Exception $e) {
      $this->dbh->rollback();
      SessionHelper::create_session_banner(SessionHelper::DISCOUNTS_RES_PAGE_BANNER, $e->getMessage(), true);
    }
    return array(
      'res_discounts' => $res_discount_codes,
      'total_per_page' => $total_per_page,
      'pagination_url' => $redirect_url . '?',
      'pagination' => $pagination_data['pagination'],
      'pagination_visible' => true,
      'pages_nav' => $pagination_data['pages_nav'],
      'search_text' => $search_text,
      'not_empty' => count($res_discount_codes),
    );
  }

  /**
   * Metoda odpowiedzialna za dodawanie kody rabatowego do restauracji na podstawie przekazywanego w parametrach GET id restauracji.
   */
  public function add_discount_code()
  {
    if (!isset($_GET['resid'])) {
      header('Location:' . __URL_INIT_DIR__ . 'discounts/discounts-with-restaurants', true, 301);
    }
    $disc = new AddEditDiscountModel;
    try {
      $this->dbh->beginTransaction();
      RestaurantsHelper::check_if_restaurant_exist($this->dbh, 'resid');

      if (isset($_POST['add-edit-discount-code'])) {
        $disc->code = ValidationHelper::check_optional('disc-code', 'disc-auto-generated', '/^[a-zA-Z0-9]{5,20}$/');
        $disc->description['value'] = $_POST['disc-description'];
        $disc->percentage_discount = ValidationHelper::validate_field_regex('disc-percentage', Config::get('__REGEX_PRICE__'));
        $disc->max_usages = ValidationHelper::check_optional('disc-max-usages', 'disc-no-max-usages', '/^[0-9]{1,7}$/');
        $disc->expired_date = ValidationHelper::check_optional('disc-expired-date', 'disc-no-expired-date', '/^[0-9]{1,7}$/');
        if ($disc->all_is_valid()) {
          $query = "
            SELECT COUNT(*) FROM discounts AS d INNER JOIN restaurants AS r ON d.restaurant_id = r.id
            WHERE code = ? AND r.user_id = ?
          ";
          $statement = $this->dbh->prepare($query);
          $statement->execute(array($disc->code['value'], $_SESSION['logged_user']['user_id']));
          if ($statement->fetchColumn()) {
            throw new Exception(
              'Kod z podaną nazwą istnieje już w systemie. Wpisz inną nazwę lub wygeneruj kod automatycznie przez system.'
            );
          }
          if (isset($_POST['disc-auto-generated'])) {
            $disc->code['value'] = AuthHelper::generate_random_seq();
          }
          $query = "
            INSERT INTO discounts (code, description, percentage_discount, max_usages, expired_date, restaurant_id)
            VALUES (?,NULLIF(?,''),CAST(REPLACE(?, ',', '.') AS DECIMAL(10,2)),NULLIF(?,''),NULLIF(?,''),?)
          ";
          $statement = $this->dbh->prepare($query);
          $statement->execute(array(
            $disc->code['value'], $disc->description['value'], $disc->percentage_discount['value'], $disc->max_usages['value'],
            $disc->expired_date['value'], $_GET['resid']
          ));
          $this->_banner_message = 'Pomyślnie dodano nowy kod rabatowy do wybranej restauracji.';
          $this->dbh->commit();
          SessionHelper::create_session_banner(SessionHelper::RESTAURANT_DETAILS_PAGE_BANNER, $this->_banner_message,
            $this->_banner_error);
          header('Location:' . __URL_INIT_DIR__ . 'owner/restaurants/restaurant-details?id=' . $_GET['resid'], true, 301);
          die;
        }
      }
      if ($this->dbh->inTransaction()) {
        $this->dbh->commit();
      }
    } catch (Exception $e) {
      $this->dbh->rollback();
      SessionHelper::create_session_banner(SessionHelper::DISCOUNT_ADD_EDIT_PAGE_BANNER, $e->getMessage(), true);
    }
    return array(
      'disc' => $disc,
      'res_id' => $_GET['resid'],
      'no_max_usages' => isset($_POST['disc-no-max-usages']) ? 'checked' : '',
      'no_time_expired' => isset($_POST['disc-no-expired-date']) ? 'checked' : '',
      'code_is_auto_generated' => isset($_POST['disc-auto-generated']) ? 'checked' : '',
    );
  }

  /**
   * Metoda odpowiedzialna za edytowanie kody rabatowego do restauracji na podstawie przekazywanego w parametrach GET id restauracji oraz
   * id już stworzonego kodu rabatowego.
   */
  public function edit_discount_code()
  {
    if (!isset($_GET['id']) || !isset($_GET['resid'])) {
      header('Location:' . __URL_INIT_DIR__ . 'discounts/discounts-with-restaurants', true, 301);
    }
    $disc = new AddEditDiscountModel;
    try {
      $this->dbh->beginTransaction();
      $discount_data = $this->check_if_discount_is_valid();
      $query = "
        SELECT id, description, REPLACE(CAST(percentage_discount as DECIMAL(10,2)), '.', ',') AS percentage_discount,
        IFNULL(max_usages, 'checked') AS max_usages, IFNULL(expired_date, 'checked') AS expired_date
        FROM discounts WHERE restaurant_id = ? AND id = ?
      ";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['resid'], $_GET['id']));
      $disc = $statement->fetchObject(AddEditDiscountModel::class);

      if (isset($_POST['add-edit-discount-code'])) {
        $disc->description['value'] = $_POST['disc-description'];
        $disc->percentage_discount = ValidationHelper::validate_field_regex('disc-percentage', Config::get('__REGEX_PRICE__'));
        $disc->max_usages = ValidationHelper::check_optional('disc-max-usages', 'disc-no-max-usages', '/^[0-9]{1,7}$/');
        $disc->expired_date = ValidationHelper::check_optional('disc-expired-date', 'disc-no-expired-date', Config::get('__REGEX_DATE__'));
        if ($disc->all_is_valid()) {
          $query = "SELECT COUNT(*) FROM discounts WHERE usages >= :maxUsages AND NOT NULLIF(:maxUsages, 'checked')";
          $statement = $this->dbh->prepare($query);
          $statement->bindValue('maxUsages', $disc->max_usages['value'], PDO::PARAM_INT);
          $statement->execute();
          if ($statement->fetchColumn()) {
            throw new Exception(
              'Liczba reprezentująca maksymalną liczbę wykorzystań kodu musi być większa od obecnej liczby wykorzystania kodu.'
            );
          }
          if (isset($_POST['disc-auto-generated'])) {
            $disc->code['value'] = AuthHelper::generate_random_seq();
          }
          $query = "
            UPDATE discounts SET description = NULLIF(?,''), percentage_discount = CAST(REPLACE(?, ',', '.') AS DECIMAL(10,2)),
            max_usages = NULLIF(?,''), expired_date = NULLIF(?,'') WHERE id = ?
          ";
          $statement = $this->dbh->prepare($query);
          $statement->execute(array(
            $disc->description['value'], $disc->percentage_discount['value'], $disc->max_usages['value'],
            $disc->expired_date['value'], $_GET['id'],
          ));

          $this->_banner_message = '
            Pomyślnie zmodyfikowany kod rabatowy <strong>' . $discount_data['code'] . '</strong> pochodzący z restauracji
            <strong>' . $discount_data['name'] . '</strong>.
          ';
          $this->dbh->commit();
          SessionHelper::create_session_banner(SessionHelper::RESTAURANT_DETAILS_PAGE_BANNER, $this->_banner_message,
            $this->_banner_error);
          header('Location:' . __URL_INIT_DIR__ . 'owner/restaurants/restaurant-details?id=' . $discount_data['resid'], true, 301);
          die;
        }
      }
      if ($this->dbh->inTransaction()) {
        $this->dbh->commit();
      }
    } catch (Exception $e) {
      $this->dbh->rollback();
      SessionHelper::create_session_banner(SessionHelper::DISCOUNT_ADD_EDIT_PAGE_BANNER, $e->getMessage(), true);
    }
    return array(
      'disc' => $disc,
      'res_id' => $_GET['resid'],
      'no_max_usages' => isset($_POST['disc-no-max-usages']) || $disc->max_usages['value'] == 'checked' ? 'checked' : '',
      'no_time_expired' => isset($_POST['disc-no-expired-date']) || $disc->expired_date['value'] == 'checked' ? 'checked' : '',
      'code_is_auto_generated' => isset($_POST['disc-auto-generated']) ? 'checked' : '',
    );
  }

  private function check_if_discount_is_valid()
  {
    $query = "
      SELECT r.name, code, r.id AS resid FROM discounts AS d INNER JOIN restaurants AS r ON d.restaurant_id = r.id
      WHERE d.id = ? AND r.accept = 1 AND r.user_id = ? AND r.id = ?
    ";
    $statement = $this->dbh->prepare($query);
    $statement->execute(array($_GET['id'], $_SESSION['logged_user']['user_id'], $_GET['resid']));
    $discount_data = $statement->fetch(PDO::FETCH_ASSOC);
    $statement->closeCursor();
    if (!$discount_data) {
      $message = '
        Szukany kod rabatowy na podstawie przekazanego identyfikatora nie istnieje lub nie jest przypisany do Twojej restauracji.
      ';
      SessionHelper::create_session_banner(SessionHelper::RESTAURANT_DETAILS_PAGE_BANNER, $message, true);
      header('Location:' . __URL_INIT_DIR__ . 'owner/restaurants/restaurant-details?id=' . $_GET['resid'], true, 301);
      die;
    }
    return $discount_data;
  }

  /**
   * Metoda odpowiedzialna za usuwanie wybranego kodu rabatowego z restauracji na podstawie przekazywanego id kodu rabatowego w
   * parametrach GET. Sprawdza, czy kod rabatowy należy do restauracji, która jest przypisana do zalogowanego właściciela.
   */
  public function delete_discount_code()
  {
    if (!isset($_GET['id']) || !isset($_GET['resid'])) {
      header('Location:' . __URL_INIT_DIR__ . 'owner/discounts/discounts-with-restaurants', true, 301);
    }
    try {
      $this->dbh->beginTransaction();
      $discount_data = $this->check_if_discount_is_valid();

      $query = "DELETE FROM discounts WHERE id = ?";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['id']));

      $this->_banner_message = '
        Pomyślnie usunięto kod rabatowy <strong>' . $discount_data['code'] . '</strong> z restauracji <strong>' .
        $discount_data['name'] . '</strong>.
      ';
      if ($this->dbh->inTransaction()) {
        $this->dbh->commit();
      }
    } catch (Exception $e) {
      $this->dbh->rollback();
      $this->_banner_error = true;
      $this->_banner_message = $e->getMessage();
    }
    $banner_type = isset($_GET['redirect']) ? SessionHelper::DISCOUNTS_PAGE_BANNER : SessionHelper::RESTAURANT_DETAILS_PAGE_BANNER;
    SessionHelper::create_session_banner($banner_type, $this->_banner_message, $this->_banner_error);
    return $_GET['redirect'] ?? 'owner/restaurants/restaurant-details?id=' . $_GET['resid'];
  }

  /**
   * Metoda odpowiadająca za zwiększanie ilości możliwych użyć kodu rabatowego na podstawie parametru v w parametrach GET zapytania.
   */
  public function increase_code_usages_count()
  {
    if (!isset($_GET['id']) || !isset($_GET['resid'])) {
      header('Location:' . __URL_INIT_DIR__ . 'owner/discounts/discounts-with-restaurants', true, 301);
    }
    try {
      $this->dbh->beginTransaction();
      $discount_data = $this->check_if_discount_is_valid();
      $increase = isset($_GET['v']) && $_GET['v'] == '50' || $_GET['v'] == '100' ? $_GET['v'] : 5;

      $query = "SELECT max_usages IS NULL FROM discounts WHERE id = ?";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['id']));
      if ($statement->fetchColumn()) {
        throw new Exception('
          Wybrana operacja dodawania dodatkowej ilości użyć kodu rabatowego jest niedostępna dla kodu posiadającego nieskończoną
          ilość użyć.
        ');
      }
      $query = "UPDATE discounts SET max_usages = max_usages + :increaseUsages WHERE id = :id";
      $statement = $this->dbh->prepare($query);
      $statement->bindValue('increaseUsages', $increase, PDO::PARAM_INT);
      $statement->bindValue('id', $_GET['id'], PDO::PARAM_INT);
      $statement->execute();

      $this->_banner_message = '
        Pomyślnie zwiększono ilość użyć kodu rabatowego <strong>' . $discount_data['code'] . '</strong> z restauracji <strong>' .
        $discount_data['name'] . '</strong> o <strong>' . $_GET['v'] . '</strong> użyć.
      ';
      if ($this->dbh->inTransaction()) {
        $this->dbh->commit();
      }
    } catch (Exception $e) {
      $this->dbh->rollback();
      $this->_banner_error = true;
      $this->_banner_message = $e->getMessage();
    }
    $banner_type = isset($_GET['redirect']) ? SessionHelper::DISCOUNTS_PAGE_BANNER : SessionHelper::RESTAURANT_DETAILS_PAGE_BANNER;
    SessionHelper::create_session_banner($banner_type, $this->_banner_message, $this->_banner_error);
    return $_GET['redirect'] ?? 'owner/restaurants/restaurant-details?id=' . $_GET['resid'];
  }

  /**
   * Metoda odpowiadająca za zwiększanie ilości dni życia kodu rabatowego na podstawie parametru v w parametrach GET zapytania.
   */
  public function increase_code_expiration_time()
  {
    if (!isset($_GET['id']) || !isset($_GET['resid'])) {
      header('Location:' . __URL_INIT_DIR__ . 'discounts/discounts-with-restaurants', true, 301);
    }
    try {
      $this->dbh->beginTransaction();
      $discount_data = $this->check_if_discount_is_valid();
      $increase = isset($_GET['v']) && $_GET['v'] == '5' || $_GET['v'] == '10' ? $_GET['v'] : 5;

      $query = "SELECT expired_date IS NULL FROM discounts WHERE id = ?";
      $statement = $this->dbh->prepare($query);
      $statement->execute(array($_GET['id']));
      if ($statement->fetchColumn()) {
        throw new Exception('
          Wybrana operacja dodawania dodatkowych dni do czasu wygaśnięcia kodu rabatowego jest niedostępna dla kodu który nie ulega
          przedawnieniu w czasie.
        ');
      }
      $query = "UPDATE discounts SET expired_date = DATE_ADD(expired_date, INTERVAL :addDays DAY) WHERE id = :id";
      $statement = $this->dbh->prepare($query);
      $statement->bindValue('addDays', $increase, PDO::PARAM_INT);
      $statement->bindValue('id', $_GET['id'], PDO::PARAM_INT);
      $statement->execute();

      $this->_banner_message = '
        Pomyślnie zwiększono ilość dni o <strong>' . $_GET['v'] . '</strong>, po których kod rabatowy <strong>' .
        $discount_data['code'] . '</strong> z restauracji <strong>' . $discount_data['name'] . '</strong> ulegnie przedawnieniu.
      ';
      if ($this->dbh->inTransaction()) {
        $this->dbh->commit();
      }
    } catch (Exception $e) {
      $this->dbh->rollback();
      $this->_banner_error = true;
      $this->_banner_message = $e->getMessage();
    }
    $banner_type = isset($_GET['redirect']) ? SessionHelper::DISCOUNTS_PAGE_BANNER : SessionHelper::RESTAURANT_DETAILS_PAGE_BANNER;
    SessionHelper::create_session_banner($banner_type, $this->_banner_message, $this->_banner_error);
    return $_GET['redirect'] ?? 'owner/restaurants/restaurant-details?id=' . $_GET['resid'];
  }
}
