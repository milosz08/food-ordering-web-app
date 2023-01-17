<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: DiscountService.php                            *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-12, 23:35:16                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-13 07:55:46                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Order\Services;

use PDO;
use Exception;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Helpers\CookieHelper;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\ValidationHelper;

ResourceLoader::load_service_helper('CookieHelper');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('ValidationHelper');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class DiscountService extends MvcService
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
     * Metoda umożliwiająca dodanie kodu rabatowego do podsumowania zamówienia. Sprawdza, czy przekazywane ID w parametrze GET odpowiada
     * parametrowi zapisanemu w koszyku pobieranego z pliku cookie. Jeśli nie jest zgodne, przekierowanie
     */
    public function add_discount()
    {
        try
        {
            if (!isset($_GET['resid'])) header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);

            // Obsługa dodawania kodu rabatowego do plików cookies
            $cookie = $_COOKIE[CookieHelper::get_shopping_cart_name($_GET['resid'])] ?? null;
            if (isset($_POST['discount-button']) && isset($cookie))
            {
                $discount = ValidationHelper::validate_field_regex('discount', '/^[\w]+$/');
                // Pobranie ID kodu promocyjnego jeżeli istnieje
                $query = "
                    SELECT CONCAT(REPLACE(CAST(percentage_discount AS DECIMAL(10,2)), '.', ','), '%') AS percentage_discount 
                    FROM discounts WHERE code = :code AND restaurant_id = :resid 
                    AND ((expired_date > NOW() OR expired_date IS NULL) AND (usages < max_usages OR max_usages IS NULL))
                ";
                $statement = $this->dbh->prepare($query);
                $statement->bindValue('code', $discount['value']);
                $statement->bindValue('resid', $_GET['resid'], PDO::PARAM_INT);
                $statement->execute();
                $percentage_discount = $statement->fetchColumn();
                if (!$percentage_discount) throw new Exception('
                    Podany kod rabatowy nie istnieje, nie jest przypisany do podanej restauracji bądź uległ już wygaśnięciu.
                ');

                $temp_array = json_decode($cookie, true);
                $temp_array['code'] = $discount['value'];
                CookieHelper::set_non_expired_cookie(CookieHelper::get_shopping_cart_name($_GET['resid']), json_encode($temp_array));

                $this->_banner_message = '
                    Poprawnie dodano kod rabatowy <strong>' . $discount['value'] . '</strong> obniżający wartość zamówienia o <strong>' 
                    . $percentage_discount . '</strong>.
                ';
                SessionHelper::create_session_banner(SessionHelper::ORDER_SUMMARY_PAGE_BANNER, $this->_banner_message, false);
            }
        }
        catch (Exception $e)
        {
            SessionHelper::create_session_banner(SessionHelper::ORDER_SUMMARY_PAGE_BANNER, $e->getMessage(), true);
        }
        return $_GET['resid'];
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda usuwająca kod rabatowy z podsumowania zamówienia. Walidacja jak w metodzie wyżej.
     */
    public function delete_discount()
    {
        $cookie = $_COOKIE[CookieHelper::get_shopping_cart_name($_GET['resid'])] ?? null;
        if (!isset($_GET['resid']) || !isset($cookie)) header('Location:' . __URL_INIT_DIR__ . 'restaurants', true, 301);

        $temp_array = json_decode($cookie, true);
        $temp_array['code'] = '';
        CookieHelper::set_non_expired_cookie(CookieHelper::get_shopping_cart_name($_GET['resid']), json_encode($temp_array));

        $this->_banner_message = 'Pomyślnie usunięto kod rabatowy z zamówienia.';
        SessionHelper::create_session_banner(SessionHelper::ORDER_SUMMARY_PAGE_BANNER, $this->_banner_message, false);
        return $_GET['resid'];
    }
}
