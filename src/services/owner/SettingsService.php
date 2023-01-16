<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: SettingsService.php                            *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-02, 22:32:16                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-16 15:41:29                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Owner\Services;

use PDO;
use Exception;
use App\Core\Config;
use App\Core\MvcService;
use App\Core\ResourceLoader;
use App\Models\EditUserPersonalDataModel;
use App\Services\Helpers\ImagesHelper;
use App\Services\Helpers\SessionHelper;
use App\Services\Helpers\ValidationHelper;

ResourceLoader::load_model('EditUserPersonalDataModel', 'user');
ResourceLoader::load_service_helper('ImagesHelper');
ResourceLoader::load_service_helper('SessionHelper');
ResourceLoader::load_service_helper('ValidationHelper');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class SettingsService extends MvcService
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
     * Metoda zwracająca dane osobowe właściciela restauracji, przy czym pozwalając na ich edycję przy użyciu formularza. ID użytkownika
     * brane jest z sesji.
     */
    public function get_and_modify_user_personal_data()
    {
        $user = new EditUserPersonalDataModel;
        try
        {
            $this->dbh->beginTransaction();
            $query = "
                SELECT u.id, u.first_name, u.last_name, u.login, u.email, a.building_nr AS building_no, a.locale_nr AS locale_no, a.street,
                a.post_code, a.city, u.photo_url AS profile_url,
                CONCAT(SUBSTRING(phone_number, 1, 3), ' ', SUBSTRING(phone_number, 3, 3), ' ', SUBSTRING(phone_number, 6, 3)) AS phone_number
                FROM users AS u
                INNER JOIN user_address AS a ON a.user_id = u.id
                WHERE u.id = ?
            ";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));
            $user = $statement->fetchObject(EditUserPersonalDataModel::class);
            $profile_photo = $user->profile_url['value'];

            if (isset($_POST['user-edit-data']))
            {
                $user->first_name = ValidationHelper::validate_field_regex('user-first-name', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{2,50}$/');
                $user->last_name = ValidationHelper::validate_field_regex('user-last-name', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ \-]{2,50}$/');
                $user->login = ValidationHelper::validate_field_regex('user-login', Config::get('__REGEX_LOGIN__'));
                $user->email = ValidationHelper::validate_email_field('user-email');
                $user->building_no = ValidationHelper::validate_field_regex('user-building-no', Config::get('__REGEX_BUILDING_NO__'));
                if (!empty($_POST['user-locale-no']))
                    $user->locale_no = ValidationHelper::validate_field_regex('user-locale-no', Config::get('__REGEX_BUILDING_NO__'));
                else $user->locale_no = array('value' => $_POST['user-locale-no'], 'invl' => false, 'bts_class' => '');
                $user->post_code = ValidationHelper::validate_field_regex('user-post-code', Config::get('__REGEX_POSTCODE__'));
                $user->city = ValidationHelper::validate_field_regex('user-city', Config::get('__REGEX_CITY__'));
                $user->street = ValidationHelper::validate_field_regex('user-street', Config::get('__REGEX_STREET__'));
                $user->phone_number = ValidationHelper::validate_field_regex('user-phone-number', Config::get('__REGEX_PHONE_PL__'));
                $user->profile_url = ValidationHelper::validate_image_regex('user-profile');
                if ($user->all_is_valid())
                {
                    $query = "SELECT COUNT(*) FROM users WHERE (login = ? OR email = ?) AND id <> ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $user->login['value'], $user->email['value'], $_SESSION['logged_user']['user_id'],
                    ));
                    if ($statement->fetchColumn() > 0) throw new Exception('
                        Użytkownik z podanymi loginem lub adresem email istnieje już w systemie. Spróbuj wpisać inny login. 
                    ');
                    $query = "SELECT COUNT(*) FROM users WHERE phone_number = REPLACE(?, ' ', '') AND id <> ?";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array($user->phone_number['value'], $_SESSION['logged_user']['user_id']));
                    if ($statement->fetchColumn() > 0) throw new Exception('
                        Podany numer telefonu jest już przypisany do innego użytkownika. Spróbuj ponownie podając inny numer telefonu. 
                    ');
                    
                    $profile_url = ImagesHelper::upload_user_profile_image(
                        $user->profile_url, $_SESSION['logged_user']['user_id'], $profile_photo,
                    );
                    // Sekcja zapytań dodająca wprowadzone dane do tabel users i user_address
                    $query = "
                        UPDATE users SET first_name = ?, last_name = ?, login = ?, email = ?, phone_number = REPLACE(?, ' ', ''),
                        photo_url = ? WHERE id = ?
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $user->first_name['value'], $user->last_name['value'], $user->login['value'], $user->email['value'],
                        $user->phone_number['value'], $profile_url, $_SESSION['logged_user']['user_id'],
                    ));
                    $query = "
                        UPDATE user_address SET street = ?, building_nr = ?, locale_nr = NULLIF(?,''), post_code = ?, city = ?
                        WHERE user_id = ? AND is_prime = 1
                    ";
                    $statement = $this->dbh->prepare($query);
                    $statement->execute(array(
                        $user->street['value'], $user->building_no['value'], $user->locale_no['value'], $user->post_code['value'],
                        $user->city['value'], $_SESSION['logged_user']['user_id'],
                    ));

                    $user->profile_url['value'] = $profile_url;
                    $_SESSION['logged_user']['user_full_name'] = $user->first_name['value'] . ' ' . $user->last_name['value'];
                    $_SESSION['logged_user']['user_profile_image'] = $profile_url;
                    $this->_banner_message = 'Pomyślnie zaktualizowano ustawienia użytkownika.';
                    $statement->closeCursor();
                    if ($this->dbh->inTransaction()) $this->dbh->commit();
                    SessionHelper::create_session_banner(SessionHelper::OWNER_PROFILE_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
                    header('Refresh:0; url=' . __URL_INIT_DIR__ . 'owner/profile', true, 301);
                    die;
                }
                else $user->profile_url['value'] = $profile_photo;
            }
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            SessionHelper::create_session_banner(SessionHelper::OWNER_SETTINGS_PAGE_BANNER, $e->getMessage(), true);
        }
        return array(
            'user' => $user,
            'has_profile' => !empty($user->profile_url['value']),
            'hide_profile_preview_class' => $user->profile_url['invl'] ? 'display-none' : '',
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda odpowiadająca za usuwanie zdjęcia profilowego użytkownika, który jest zalogowany do systemu.
     */
    public function delete_profile_image()
    {
        try
        {
            $this->dbh->beginTransaction();
            $query = "SELECT IFNULL(photo_url, 0) FROM users WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));
            $photo_url = $statement->fetchColumn();
            if ($photo_url == 0) throw new Exception('Wybrany użytkownik nie posiada zdjęcia profilowego.');
            
            $query = "UPDATE users SET photo_url = NULL WHERE id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));

            $this->_banner_message = 'Twoje zdjęcie profilowe zostało pomyślnie usunięte z konta.';
            $_SESSION['logged_user']['user_profile_image'] = 'static/images/default-profile-image.jpg';
            if (file_exists($photo_url)) unlink($photo_url);
            if ($this->dbh->inTransaction()) $this->dbh->commit();
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            $this->_banner_error = true;
            $this->_banner_message = $e->getMessage();
        }
        SessionHelper::create_session_banner(SessionHelper::OWNER_SETTINGS_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Metoda umożliwiająca usunięcie konta z systemu.
     */
    public function delete_account()
    {
        $password_confirmation = $_POST['password-confirmation'] ?? '';
        try
        {
            $this->dbh->beginTransaction();
            $query = "
                SELECT password FROM users AS u INNER JOIN restaurants AS r ON u.id = r.user_id WHERE u.id = :userid AND
                (SELECT COUNT(*) FROM orders AS o INNER JOIN restaurants AS r ON o.restaurant_id = r.id
                WHERE r.user_id = :userid AND status_id = 1) = 0
            ";
            $statement = $this->dbh->prepare($query);
            $statement->bindValue('userid', $_SESSION['logged_user']['user_id'], PDO::PARAM_INT);
            $statement->execute();
            $user_password = $statement->fetchColumn();
            if (!$user_password) throw new Exception('
                Usunięcie konta z co najmniej jednym aktywnym zamówieniem jest niemożliwe.
            ');
            if (!password_verify($password_confirmation, $user_password)) throw new Exception('
                Nieprawidłowe hasło. Spróbuj ponownie wprowadzając inne hasło.
            ');

            $query = "DELETE FROM users WHERE user_id = ?";
            $statement = $this->dbh->prepare($query);
            $statement->execute(array($_SESSION['logged_user']['user_id']));

            rmdir('uploads/users/' . $_SESSION['logged_user']['user_id']);
            unset($_SESSION['logged_user']);
            $this->_banner_message = 'Twoje konto i wszystkie zasoby z nim powiązane zostało pomyślnie usunięte z systemu.';
            SessionHelper::create_session_banner(SessionHelper::LOGIN_PAGE_BANNER, $this->_banner_message, $this->_banner_error);
            if ($this->dbh->inTransaction()) $this->dbh->commit();
            header('Location:' . __URL_INIT_DIR__ . 'auth/login', true, 301);
        }
        catch (Exception $e)
        {
            $this->dbh->rollback();
            var_dump($e->getMessage());
            SessionHelper::create_session_banner(SessionHelper::OWNER_SETTINGS_PAGE_BANNER, $e->getMessage(), true);
        }
    }
}
