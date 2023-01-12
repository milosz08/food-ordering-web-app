<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
* Copyright (c) 2023 by multiple authors                      *
* Politechnika Śląska | Silesian University of Technology     *
*                                                             *
* Nazwa pliku: DeleteRestaurantsService.php                   *
* Projekt: restaurant-project-php-si                          *
* Data utworzenia: 2023-01-12, 17:39:46                       *
* Autor: BubbleWaffle                                         *
*                                                             *
* Ostatnia modyfikacja: 2023-01-12 18:53:28                   *
* Modyfikowany przez: BubbleWaffle                            *
* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
namespace App\Admin\Services;
use PDO;
use Exception;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\RestaurantModel;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\PaginationHelper;
use App\Services\Helpers\AdminHelper;

ResourceLoader::load_model('RestaurantModel', 'restaurant');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('PaginationHelper');
ResourceLoader::load_service_helper('AdminHelper');
  
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class DeleteRestaurantsService extends MvcService
{
    private $_banner_message = '';
    private $_banner_error = false;

     protected function __construct()
     {
         parent::__construct();
     }

    public function get_restaurants()
    {
        $pagination = array(); // tablica przechowująca liczby przekazywane do dynamicznego tworzenia elementów paginacji
        $restaurants = array();
        $pages_nav = array();
        $pagination_visible = true; // widoczność paginacji
        $not_empty = false;
        try {
            $this->dbh->beginTransaction();

            $curr_page = $_GET['page'] ?? 1; // pobranie indeksu paginacji
            $page = ($curr_page - 1) * 5;
            $total_per_page = $_GET['total'] ?? 5;
            $search_text = SessionHelper::persist_search_text('search-res-name', SessionHelper::OWNER_RES_SEARCH);

            $redirect_url = 'admin/delete-restaurants';
            PaginationHelper::check_parameters('admin/delete-restaurants');

            // zapytanie do bazy danych, które zwróci poszczególne wartości wszystkich restauracji z bazy
            $query = "
                SELECT ROW_NUMBER() OVER(ORDER BY id) as it, name, accept, id,
                CONCAT('ul. ', street, ' ', building_locale_nr, ', ', post_code, ' ', city) AS address
                FROM restaurants WHERE name LIKE :search LIMIT :total OFFSET :page
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('search', '%' . $search_text . '%');
            $statement->bindValue('total', $total_per_page, PDO::PARAM_INT);
            $statement->bindValue('page', $page, PDO::PARAM_INT);
            $statement->execute();

            while ($row = $statement->fetchObject(RestaurantModel::class))
                array_push($restaurants, $row);
            $not_empty = count($restaurants);

            // zapytanie zliczające wszystkie restauracje z bazy
            $query = "SELECT count(*) FROM restaurants WHERE name LIKE :search";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('search', '%' . $search_text . '%');
            $statement->execute();
            $total_records = $statement->fetchColumn();

            $total_pages = ceil($total_records / $total_per_page);
            for ($i = 1; $i <= $total_pages; $i++)
                array_push($pagination, array(
                    'it' => $i,
                    'url' => $redirect_url . '?page=' . $i . '&total=' . $total_per_page,
                    'selected' => $curr_page == $i ? 'active' : '',
                )
                );

            $statement->closeCursor();
            PaginationHelper::check_if_page_is_greaten_than($redirect_url, $total_pages);
            $pages_nav = PaginationHelper::get_pagination_nav($curr_page, $total_per_page, $total_pages, $total_records, $redirect_url);
            $this->dbh->commit();
        } catch (Exception $e) {
            $this->dbh->rollback();
            $pagination_visible = false;
            SessionHelper::create_session_banner(SessionHelper::RESTAURANTS_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'total_per_page' => $total_per_page,
            'pagination_url' => 'admin/delete-restaurants?',
            'pagination' => $pagination,
            'pagination_visible' => $pagination_visible,
            'pages_nav' => $pages_nav,
            'user_restaurants' => $restaurants,
            'search_text' => $search_text,
            'not_empty' => $not_empty,
        );
    }

    public function delete_restaurant()
    {
        if (!isset($_GET['id'])) return;
        try
        {
            $this->dbh->beginTransaction();
            AdminHelper::check_if_restaurant_exist_admin($this->dbh, 'id', '');

            $query = "DELETE FROM restaurants WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));

            // wysyłanie wiadomości email do tego co usunął restaurację i do administratorów systemu z informacją o usunięciu restauracji
            // i jej aktualnym statusie (aktywna/w oczekiwaniu)

            rmdir('uploads/restaurants/' . $_GET['id']);
            $this->_banner_message = 'Pomyślnie usunięto wybraną restaurację z systemu.';
            $statement->closeCursor();
            $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $this->_banner_message = $e->getMessage();
            $this->_banner_error = true;
        }
        SessionHelper::create_session_banner(SessionHelper::RESTAURANTS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
    }
}
