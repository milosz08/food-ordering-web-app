<?php

namespace App\Services\Owner;

use App\Core\MvcService;
use Exception;
use PDO;

class DashboardService extends MvcService
{
  protected function __construct()
  {
    parent::__construct();
  }

  /**
   * Metoda zwracająca dane do pliku js aby wygenerować wykresy w głównym widoku panelu właściciela restauracji.
   */
  public function graph()
  {
    $result_one = array();
    $result_two = array();
    try {
      $this->dbh->beginTransaction();
      $query = "
        SELECT d.code AS Name, d.usages AS Uses FROM
        ((discounts d INNER JOIN restaurants r ON d.restaurant_id = r.id)
        INNER JOIN users u ON r.user_id = u.id) WHERE u.id = :userid ORDER BY d.id DESC LIMIT 7
      ";
      $statement = $this->dbh->prepare($query);
      $statement->bindValue('userid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
      $statement->execute();
      $data_graph = $statement->fetchall(PDO::FETCH_ASSOC);
      $result_two = $data_graph;

      for ($i = 0; $i < 7; $i++) {
        $date = strtotime("-" . $i . " day", time());
        $time = date("Y-m-d", $date);

        $query = "
          SELECT :date AS day, count(*) AS number FROM
          ((orders o INNER JOIN restaurants r ON o.restaurant_id = r.id)
          INNER JOIN users u ON r.user_id = u.id) WHERE DATE(o.date_order) = :date AND u.id = :userid
          AND NOT o.status_id = 3 ORDER BY o.date_order DESC
        ";
        $statement = $this->dbh->prepare($query);
        $statement->bindValue('date', $time);
        $statement->bindValue('userid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
        $statement->execute();
        $data_graph = $statement->fetch(PDO::FETCH_ASSOC);
        $first = array('Day' => $data_graph['day'], 'Amount' => $data_graph['number']);
        $result_one[] = $first;
      }
      $statement->closeCursor();
      if ($this->dbh->inTransaction()) {
        $this->dbh->commit();
      }
    } catch (Exception $e) {
      $this->dbh->rollback();
    }
    return json_encode(array("orders" => $result_one, "coupons" => $result_two));
  }
}
