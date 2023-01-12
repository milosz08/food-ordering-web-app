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
 * Ostatnia modyfikacja: 2023-01-12 21:10:40                   *
 * Modyfikowany przez: patrick012016                           *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Admin\Services;

use PDO;
use Exception;
use App\Core\MvcService;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class DashboardService extends MvcService
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
            SELECT code AS Name, usages AS Uses FROM discounts ORDER BY code DESC
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute();
            $data_graph = $statement->fetchall(PDO::FETCH_ASSOC);
            $result_two = $data_graph;
            $statement->closeCursor();
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->_banner_error = true;
            $this->_banner_message = $e->getMessage();
            $this->dbh->rollback();
        }
        for ($i = 0; $i < 7; $i++) 
        {
            $date_1 = strtotime("-" . $i . " day", time());
            $time_1 = date("Y-m-d", $date_1);
            try
            {
                $this->dbh->beginTransaction();
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
                $statement->closeCursor();
                $this->dbh->commit();
            }
            catch (Exception $e)
            {
                $this->_banner_error = true;
                $this->_banner_message = $e->getMessage();
                $this->dbh->rollback();
            }
        }
        return json_encode(array("orders" =>$result_one, "coupons" =>$result_two));
    }
}
