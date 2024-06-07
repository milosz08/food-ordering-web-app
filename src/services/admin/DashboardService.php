<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: DashboardService.php                           *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 22:31:39                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:58:04                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Admin\Services;

use PDO;
use Exception;
use App\Core\MvcService;

class DashboardService extends MvcService
{
    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * Metoda zwracająca dane do pliku js aby wygenerować wykresy w głównym widoku panelu administratora.
     */
    public function graph()
    {
        $result_one = array();
        $result_two = array();
        try
        {
            $this->dbh->beginTransaction();
            $query = "
                SELECT r.name AS Name, count(d.restaurant_id) AS Uses FROM discounts d
                INNER JOIN restaurants r ON d.restaurant_id = r.id GROUP BY d.restaurant_id ORDER BY d.id DESC LIMIT 7
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute();
            $data_graph = $statement->fetchall(PDO::FETCH_ASSOC);
            $result_two = $data_graph;
            for ($i = 0; $i < 7; $i++)
            {
                $date_1 = strtotime("-" . $i . " day", time());
                $time_1 = date("Y-m-d", $date_1);
                $query = "
                    SELECT :date AS day, count(*) AS number FROM orders WHERE DATE(date_order) = :date 
                    AND NOT status_id = 3 ORDER BY date_order DESC
                ";
                $statement = $this->dbh->prepare($query);
                $statement->bindValue('date', $time_1, PDO::PARAM_STR);
                $statement->execute();
                $data_graph = $statement->fetch(PDO::FETCH_ASSOC);
                $first = array('Day' => $data_graph['day'], 'Amount' => $data_graph['number']);
                array_push($result_one, $first);
            }
            $statement->closeCursor();
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
        }
        return json_encode(array("orders" =>$result_one, "coupons" =>$result_two));
    }
}
