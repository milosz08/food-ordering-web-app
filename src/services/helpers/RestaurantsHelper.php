<?php

namespace App\Services\Helpers;

use Exception;
use PDO;

class RestaurantsHelper
{
  /**
   * Metoda sprawdzająca, czy restauracja istnieje w systemie i jest przypisana do aktualnie zalogowanego użytkownika. Jeśli istnieje,
   * zwraca wartość z kolumny przekazywanej w parametrze. Domyślnie zwraca liczbę wierszy.
   */
  public static function check_if_restaurant_exist($dbh, $res_attr = 'id', $is_accept = ' AND accept = 1', $result_column = 'COUNT(*)')
  {
    $query = "SELECT $result_column FROM restaurants WHERE id = ? AND user_id = ? $is_accept";
    $statement = $dbh->prepare($query);
    $statement->execute(array($_GET[$res_attr], $_SESSION['logged_user']['user_id']));
    $result = $statement->fetchColumn();
    if (empty($result)) {
      if (!empty($is_accept)) {
        $exception_message = '
          Podana restauracja nie istnieje w systemie, została wcześniej usunięta, nie została jeszcze aktywowana przez
          administratora systemu lub nie jest przypisana do Twojego konta.
        ';
      } else {
        $exception_message = '
          Podana restauracja nie istnieje w systemie, została wcześniej usunięta lub nie jest przypisana do Twojego konta.
        ';
      }
      throw new Exception($exception_message);
    }
    $statement->closeCursor();
    return $result;
  }

  /**
   * Metoda zwracająca maksymalną liczbę restauracji na podstawie ID użytkownika, statusu zaakceptowania oraz wyszukiwanej nazwy
   * restauracji. Zwraca tablicę za danymi paginacji oraz nawigacji po elementach paginacji renderowanych w osobnym widoku.
   */
  public static function get_total_res_pages($dbh, $search_text, $total_per_page, $curr_page, $redirect_url): array
  {
    $pagination = array();
    $query = "SELECT count(*) FROM restaurants WHERE accept = 1 AND user_id = :userId AND name LIKE :search";
    $statement = $dbh->prepare($query);
    $statement->bindValue('userId', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
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
    return array(
      'pagination' => $pagination,
      'pages_nav' => $pages_nav,
    );
  }
}
