<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: DishTypesService.php                           *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-15, 03:55:02                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:58:36                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Admin\Services;

use PDO;
use Exception;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\PaginationHelper;
use App\Services\Helpers\ValidationHelper;

ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('PaginationHelper');
ResourceLoader::load_service_helper('ValidationHelper');

class DishTypesService extends MvcService
{
    private $_banner_message = '';
    private $_banner_error = false;

    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * Metoda zwraca wszystkie typy dań przypisane na stałe do systemu (takie, których użytkownik nie może usunąć ani stworzyć).
     */
    public function get_all_default_dish_types()
    {
        $pagination = array();
        $dish_types = array();
        $pages_nav = array();
        try
        {
            $this->dbh->beginTransaction();

            $curr_page = $_GET['page'] ?? 1; // pobranie indeksu paginacji
            $page = ($curr_page - 1) * 10;
            $total_per_page = $_GET['total'] ?? 10;
            $search_text = SessionHelper::persist_search_text('search-dish-type-name', SessionHelper::ADMIN_DISH_TYPES_SEARCH);
            
            $redirect_url = 'admin/dish-types';
            PaginationHelper::check_parameters($redirect_url);

            $query = "
                SELECT ROW_NUMBER() OVER(ORDER BY id) as it, id, name FROM dish_types
                WHERE user_id IS NULL AND name LIKE :search LIMIT :total OFFSET :page
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('search', '%' . $search_text . '%');
            $statement->bindValue('total', $total_per_page, PDO::PARAM_INT);
            $statement->bindValue('page', $page, PDO::PARAM_INT);
            $statement->execute();
            $dish_types = $statement->fetchAll(PDO::FETCH_ASSOC);
            $statement->closeCursor();

            $query = "SELECT count(*) FROM dish_types WHERE user_id IS NULL AND name LIKE :search";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('search', '%' . $search_text . '%');
            $statement->execute();
            $total_records = $statement->fetchColumn();

            $total_pages = ceil($total_records / $total_per_page);
            for ($i = 1; $i <= $total_pages; $i++) array_push($pagination, array(
                'it' => $i,
                'url' => $redirect_url . '?page=' . $i . '&total=' . $total_per_page,
                'selected' => $curr_page == $i ? 'active' : '',
            ));

            $statement->closeCursor();
            PaginationHelper::check_if_page_is_greaten_than($redirect_url, $total_pages);
            $pages_nav = PaginationHelper::get_pagination_nav($curr_page, $total_per_page, $total_pages, $total_records, $redirect_url);
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::ADMIN_DISH_TYPES_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'total_per_page' => $total_per_page,
            'pagination_url' => $redirect_url . '?',
            'pagination' => $pagination,
            'pages_nav' => $pages_nav,
            'dish_types' => $dish_types,
            'search_text' => $search_text,
            'not_empty' => count($dish_types),
            'redir_admin_link' => array('redir_link' => 'admin'),
        );
    }

    /**
     * Metoda umożliwiająca dodanie nowego typu dania do systemu.
     */
    public function add_dish_type()
    {
        try
        {
            $this->dbh->beginTransaction();
            $v_name = ValidationHelper::validate_field_regex('dish-type-name', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ ]{2,50}$/');
            if ($v_name['invl']) throw new Exception('
                Nieprawidłowa nazwa potrawy. Nazwa potrawy może zawierać podstawowe znaki diakrytyczne minimum 2, maksimum 50.
            ');
            $query = "SELECT COUNT(*) FROM dish_types WHERE LOWER(name) = LOWER(?)";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($v_name['value']));
            if ($statement->fetchColumn() != 0) throw new Exception('
                Podana nazwa potrawy istnieje już w systemie. Spróbuj wprowadzić inną nazwę potrawy i zatwierdź zmiany.
            ');

            $query = "INSERT INTO dish_types (name) VALUES (LOWER(?))";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($v_name['value']));

            $this->_banner_message = '
                Nowy typ potrawy o nazwie <strong>' . $v_name['value'] . '</strong> został pomyślnie dodany do systemu.
            ';
            $statement->closeCursor();
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $this->_banner_error = true;
            $this->_banner_message = $e->getMessage();
        }
        SessionHelper::create_session_banner(SessionHelper::ADMIN_DISH_TYPES_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
    }

    /**
     * Metoda umożliwiająca edycję istniejącego typu dania systemu.
     */
    public function edit_dish_type()
    {
        if (!isset($_GET['id'])) header('Location:' . __URL_INIT_DIR__ . 'admin/dish-types', true, 301);
        try
        {
            $this->dbh->beginTransaction();
            $v_name = ValidationHelper::validate_field_regex('dish-type-name', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ ]{2,50}$/');
            if ($v_name['invl']) throw new Exception('
                Nieprawidłowa nazwa potrawy. Nazwa potrawy może zawierać podstawowe znaki diakrytyczne minimum 2, maksimum 50.
            ');
            $query = "SELECT name FROM dish_types WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));
            $prev_name = $statement->fetchColumn();
            if (!$prev_name) throw new Exception('Szukany typ potrawy na podstawie ID nie istnieje w systemie.');

            $query = "SELECT COUNT(*) FROM dish_types WHERE LOWER(name) = LOWER(?) AND id <> ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($v_name['value'], $_GET['id']));
            if ($statement->fetchColumn() != 0) throw new Exception(
                'Podana nazwa potrawy istnieje już w systemie. Spróbuj wprowadzić inną nazwę potrawy i zatwierdź zmiany.
            ');

            $query = "UPDATE dish_types SET name = LOWER(?) WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($v_name['value'], $_GET['id']));

            $this->_banner_message = '
                Typ potrawy o nazwie <strong>' . $prev_name . '</strong> został pomyślnie zmieniony na <strong>' . $v_name['value'] . '
                </strong> i zaktualizowany w systemie.
            ';
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $this->_banner_error = true;
            $this->_banner_message = $e->getMessage();
        }
        SessionHelper::create_session_banner(SessionHelper::ADMIN_DISH_TYPES_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
    }

    /**
     * Metoda umożliwiająca usunięcie typu dania na stałe przypisanego do systemu. Typ dania można usunąć tylko wówczas, jeżeli żadne danie
     * nie jest do niego przypisane.
     */
    public function delete_dish_type()
    {
        if (!isset($_GET['id'])) header('Location:' . __URL_INIT_DIR__ . 'admin/dish-types', true, 301);
        try
        {
            $this->dbh->beginTransaction();
            $query = "SELECT name FROM dish_types WHERE id = ? AND NOT id IN((SELECT dish_type_id FROM dishes))";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));
            $name = $statement->fetchColumn();
            if (!$name) throw new Exception('
                Szukany typ potrawy na podstawie ID nie istnieje w systemie lub nie jest możliwe jego usunięcie (przynajmniej jedna
                potrawa posiada ten typ).
            ');

            $query = "DELETE FROM dish_types WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_GET['id']));
            
            $this->_banner_message = 'Typ potrawy o nazwie <strong>' . $name . '</strong> został pomyślnie usunięty z systemu.';
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $this->_banner_error = true;
            $this->_banner_message = $e->getMessage();
        }
        SessionHelper::create_session_banner(SessionHelper::ADMIN_DISH_TYPES_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
    }
}
