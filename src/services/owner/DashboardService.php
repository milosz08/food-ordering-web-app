<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: DashboardService.php                           *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 22:32:06                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-12 02:10:44                   *
 * Modyfikowany przez: patrick012016                           *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Owner\Services;

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
     * Metoda zwracająca dane do pliku js aby wygenerować wykres w głównym widoku panelu restauratora.
     */
    public function graph()
    {
        $result_owner = array();
        for ($i = 0; $i < 7; $i++) 
        {
            $date = strtotime("-" . $i . " day", time());
            $time = date("Y-m-d", $date);
            try
            {
                $this->dbh->beginTransaction();
                $query = "
                    SELECT :date AS day, count(*) AS number FROM orders WHERE DATE(date_order) = :date AND user_id = :userid 
                    AND NOT status_id = 3
                    ORDER BY date_order DESC
                ";
                $statement = $this->dbh->prepare($query);
                $statement->bindValue('date', $time, PDO::PARAM_STR);
                $statement->bindValue('userid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
                $statement->execute();
                $test = $statement->fetch(PDO::FETCH_ASSOC);
                $first = array('Day' => $test['day'], 'Amount' => $test['number']);
                array_push($result_owner, $first);
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
        return json_encode($result_owner);
    }
}
