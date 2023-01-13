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
 * Ostatnia modyfikacja: 2023-01-13 00:55:31                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\User\Services;

use Exception;
use App\Core\Config;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\EditUserProfileModel;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\ValidationHelper;

ResourceLoader::load_model('EditUserProfileModel', 'user');
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
        $user = new EditUserProfileModel;
        try
        {
            $this->dbh->beginTransaction();
            
            $query = "
                SELECT first_name, last_name, email, street, post_code, city, building_nr, IFNULL(locale_nr, '') AS locale_nr
                FROM users INNER JOIN user_address ON users.id = user_address.user_id
                WHERE users.id = ?
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));
            $user = $statement->fetchObject(EditUserProfileModel::class);

            if (isset($_POST['save-changes-button']))
            {
                $user->first_name = ValidationHelper::validate_field_regex('name', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{2,50}$/');
                $user->last_name = ValidationHelper::validate_field_regex('surname', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ \-]{2,50}$/');
                $user->email = ValidationHelper::validate_email_field('email');
                $user->building_nr = ValidationHelper::validate_field_regex('building-number', Config::get('__REGEX_BUILDING_NO__'));
                if (!empty($_POST['local-number']))
                    $user->locale_nr = ValidationHelper::validate_field_regex('local-number', Config::get('__REGEX_BUILDING_NO__'));
                else
                    $user->locale_nr = array('value' => $_POST['local-number'], 'invl' => false, 'bts_class' => '');
                $user->post_code = ValidationHelper::validate_field_regex('post-code', Config::get('__REGEX_POSTCODE__'));
                $user->city = ValidationHelper::validate_field_regex('city', Config::get('__REGEX_CITY__'));
                $user->street = ValidationHelper::validate_field_regex('street', Config::get('__REGEX_STREET__'));

                if ($user->all_is_valid())
                {
                    // Zapytanie zwracające liczbę istniejących już kont o podanym loginie i/lub emailu
                    $query = "SELECT COUNT(id) FROM users WHERE email = ? AND NOT id = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($user->email['value'], $_SESSION['logged_user']['user_id']));
                    if ($statement->fetchColumn() > 0) throw new Exception('Podany email istnieje już w systemie.');

                    // Sekcja zapytań dodająca wprowadzone dane do tabel users i user_address
                    $query = "UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $user->first_name['value'],
                        $user->last_name['value'],
                        $user->email['value'],
                        $_SESSION['logged_user']['user_id'],
                    ));

                    $query = "
                        UPDATE user_address SET street = ?, building_nr = ?, locale_nr = NULLIF(?,''), post_code = ?, city = ?
                        WHERE user_id = ?
                    "; 
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $user->street['value'],
                        $user->building_nr['value'],
                        $user->locale_nr['value'],
                        $user->post_code['value'],
                        $user->city['value'],
                        $_SESSION['logged_user']['user_id'],
                    ));

                    $statement->closeCursor();
                    $this->dbh->commit();
                    $this->_banner_message = 'Twoje dane profilu zostały pomyślnie zmienione.';
                    SessionHelper::create_session_banner(SessionHelper::USER_PROFILE_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
                    header('Location:' . __URL_INIT_DIR__ . 'user/profile', true, 301);
                    die;
                }
                if ($this->dbh->inTransaction()) $this->dbh->commit();
            }
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::EDIT_USER_PROFILE_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'user' => $user,
        );
    }
}
