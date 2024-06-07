<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: MvcProtector.php                               *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-12-17, 15:54:57                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:45:30                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Core;

use Exception;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Klasa przechowująca metody odpowiedzialne za ochronę ściezek aplikacji. W zalezności od wybranej metody, umozliwi ona przeglądanie    *
 * zasobów dostępnych pod podanym adresem lub wyświetli stronę błędu 401 z informacją o braku poświadczeń do przeglądania zawartości.    *
 * NIE MODYFIKOWAĆ!                                                                                                                      *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class MvcProtector
{
    private static $_singleton_instance;
    private $_renderer;

    public const USER = "klient";
    public const OWNER = "właściciel";
    public const ADMIN = "administrator";

    protected function __construct($renderer)
    {
        $this->_renderer = $renderer;
    }

    /**
     * Wszyscy uzytkownicy zalogowani na konto USER mają dostęp do tych zasobów, w przeciwnym wypadku renderowanie strony błędu 401
     */
    public function protect_only_user()
    {
        $this->redirect_on_role(self::USER);
    }

    /**
     * Wszyscy uzytkownicy zalogowani na konto OWNER mają dostęp do tych zasobów, w przeciwnym wypadku renderowanie strony błędu 401
     */
    public function protect_only_owner()
    {
        $this->redirect_on_role(self::OWNER);
    }

    /**
     * Wszyscy uzytkownicy zalogowani na konto ADMIN mają dostęp do tych zasobów, w przeciwnym wypadku renderowanie strony błędu 401
     */
    public function protect_only_admin()
    {
        $this->redirect_on_role(self::ADMIN);
    }

    /**
     * Metoda renderująca widok błędu 401, jeśli uzytkownic spróbuje odwołać się do zasobu do którego nie ma dostępu z poziomu roli pobranej
     * z sesji, umieszczanej bezpośrednio po zalogowaniu do serwisu.
     */
    private function redirect_on_role($role)
    {
        $logged_user_details = $_SESSION['logged_user'] ?? null;
        if (isset($logged_user_details))
        {
            $logged_user_role = $logged_user_details['user_role'];
            if ($logged_user_role['role_name'] == $role) return;
        } else header('Location:' . __URL_INIT_DIR__ . 'auth/login', true, 301);
        $this->_renderer->render('_forbidden-view');
        die;
    }

    /**
     * Metoda przekierowująca na wybrany adres (panel administratora, panel restauratora, strona główna) w zaleności od roli zalogowanego
     * uzytkownika. Uzywana głównie w kontrolerze auth, zeby zalogowany uzytkownik nie miał dostępu do panelu logowania.
     */
    public function redirect_when_logged()
    {
        $logged_user_details = $_SESSION['logged_user'] ?? null;
        if (isset($logged_user_details))
        {
            $role = $logged_user_details['user_role'];
            if ($role['role_name'] == self::USER) header('Location: ' . __URL_INIT_DIR__, true, 301);
            else if ($role['role_name'] == self::OWNER)
                header('Location: ' . __URL_INIT_DIR__ . 'owner/dashboard', true, 301);
            else if ($role['role_name'] == self::ADMIN) header('Location: ' . __URL_INIT_DIR__ . 'admin/dashboard', true, 301);
        }
    }    

    /**
     * Metoda sprawdzająca, czy zalogowany użytkownik to użytkownik. Jeśli nie, wyrzuci wyjątek który zostanie wyłapany w serwisach.
     */
    public static function check_if_user_is_user()
    {
        $logged_user = $_SESSION['logged_user'] ?? null;
        if (!isset($logged_user) || $logged_user['user_role']['role_name'] != self::USER) throw new Exception('
            Wybrana akcja wymaga zalogowania się na konto użytkownika. Jeśli jeszcze nie posiadasz konta, możesz się zarejestrować
            <a href="' . __URL_INIT_DIR__ . 'auth/register" class="alert-link">pod tym linkiem</a>.
        ');
    }

    /**
     * Metoda tworząca obiekt klasy MvcProtector i zwracająca go. Jedyna metoda która pozwala na uzyskanie instancji klasy MvcProtector.
     * Obiekt tworzony jest tylko wtedy, kiedy pole $_singleton_instance jest nullem (kiedy nie przypisano jeszcze obiektu).
     */
    public static function get_instance($renderer)
    {
        if (!isset(self::$_singleton_instance)) self::$_singleton_instance = new MvcProtector($renderer);
        return self::$_singleton_instance;
    }
}
