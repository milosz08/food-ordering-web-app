<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: ProfileService.php                             *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 21:12:35                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-07 02:02:00                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\User\Services;

use PDO;
use Exception;
use App\Core\Config;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\ValidationHelper;

ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('ValidationHelper');
 
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class ProfileService extends MvcService
{
    private $_banner_message = '';
    private $_banner_error = false;

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    protected function __construct()
    {
        parent::__construct();
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /*
     * Metoda odpowiadająca edycji profilu zalogowanego użytkownika.
     */
    public function edit_user_profile()
    {
        try
        {
            $local_nr = "";
            $this->dbh->beginTransaction();
            
            $query = "
                SELECT first_name, last_name, email, street, post_code, city, building_locale_nr
                FROM users INNER JOIN user_address ON users.id = user_address.user_id
                WHERE users.id = ?
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));
            $user = $statement->fetchAll(PDO::FETCH_ASSOC);

            $v_name = array('value' => $user[0]['first_name'], 'invl' => false, 'bts_class' => '');
            $v_surname = array('value' => $user[0]['last_name'], 'invl' => false, 'bts_class' => '');
            $v_email = array('value' => $user[0]['email'], 'invl' => false, 'bts_class' => '');
            $v_post_code = array('value' => $user[0]['post_code'], 'invl' => false, 'bts_class' => '');
            $v_city = array('value' => $user[0]['city'], 'invl' => false, 'bts_class' => '');
            $v_street = array('value' => $user[0]['street'], 'invl' => false, 'bts_class' => '');
            $building_local_nr = $user[0]['building_locale_nr'];

            $arr = explode("/", $building_local_nr, 2); // Pobranie samego numeru budynku ze stringa

            // Pobranie tylko i wyłączenie numeru lokalu ze stringa
            if ($pos = strpos($building_local_nr, "/")) $local_nr = substr($building_local_nr, $pos + 1);

            $v_building_no = array('value' => $arr[0], 'invl' => false, 'bts_class' => '');
            $v_locale_no = array('value' => $local_nr, 'invl' => false, 'bts_class' => '');

            if (isset($_POST['save-changes-button']))
            {
                $v_name = ValidationHelper::validate_field_regex('name', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{2,50}$/');
                $v_surname = ValidationHelper::validate_field_regex('surname', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ \-]{2,50}$/');
                $v_email = ValidationHelper::validate_email_field('email');
                $v_building_no = ValidationHelper::validate_field_regex('building-number', Config::get('__REGEX_BUILDING_NO__'));
                if (!empty($_POST['local-number']))
                    $v_locale_no = ValidationHelper::validate_field_regex('local-number', Config::get('__REGEX_BUILDING_NO__'));
                else
                    $v_locale_no = array('value' => $_POST['local-number'], 'invl' => false, 'bts_class' => '');
                $v_post_code = ValidationHelper::validate_field_regex('post-code', Config::get('__REGEX_POSTCODE__'));
                $v_city = ValidationHelper::validate_field_regex('city', Config::get('__REGEX_CITY__'));
                $v_street = ValidationHelper::validate_field_regex('street', Config::get('__REGEX_STREET__'));

                if (!($v_name['invl'] || $v_surname['invl'] || $v_email['invl'] || $v_building_no['invl'] || $v_locale_no['invl'] || 
                    $v_post_code['invl'] || $v_city['invl'] || $v_street['invl']))
                {
                    // Zapytanie zwracające liczbę istniejących już kont o podanym loginie i/lub emailu
                    $query = "SELECT COUNT(id) FROM users WHERE email = ? AND NOT id = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($v_email['value'], $_SESSION['logged_user']['user_id']));

                    if ($statement->fetchColumn() > 0) throw new Exception('Podany email istnieje już w systemie.');

                    // Sekcja zapytań dodająca wprowadzone dane do tabel users i user_address
                    $query = "UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $v_name['value'],
                        $v_surname['value'],
                        $v_email['value'],
                        $_SESSION['logged_user']['user_id']
                    ));

                    $query = "UPDATE user_address SET street = ?, building_locale_nr = ?, post_code = ?, city = ? WHERE user_id = ?"; 
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $v_street['value'],
                        empty($v_locale_no['value']) ? $v_building_no['value'] : $v_building_no['value'] . '/' . $v_locale_no['value'],
                        $v_post_code['value'],
                        $v_city['value'],
                        $_SESSION['logged_user']['user_id']
                    ));
                    $statement->closeCursor();

                    $this->_banner_message = 'Dane zostały pomyślnie zmienione';
                    SessionHelper::create_session_banner(SessionHelper::EDIT_USER_PROFILE_PAGE, $this->_banner_message, $this->_banner_error);
                    header('Location:' . __URL_INIT_DIR__ . 'user/edit-profile', true, 301);
                }
                $this->dbh->commit();
            }
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $this->_banner_message = $e->getMessage();
            $this->_banner_error = true;
        }
        SessionHelper::create_session_banner(SessionHelper::EDIT_USER_PROFILE_PAGE, $this->_banner_message, $this->_banner_error);
        return array(
            'v_name' => $v_name,
            'v_surname' => $v_surname,
            'v_email' => $v_email,
            'v_building_no' => $v_building_no,
            'v_locale_no' => $v_locale_no,
            'v_post_code' => $v_post_code,
            'v_city' => $v_city,
            'v_street' => $v_street,
        );
    }
}
