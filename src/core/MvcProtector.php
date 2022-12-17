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
 * Ostatnia modyfikacja: 2022-12-17 16:53:39                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Core;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Klasa przechowująca metody odpowiedzialne za ochronę ściezek aplikacji. W zalezności od wybranej metody, umozliwi ona przeglądanie    *
 * zasobów dostępnych pod podanym adresem lub wyświetli stronę błędu 401 z informacją o braku poświadczeń do przeglądania zawartości.    *
 * NIE MODYFIKOWAĆ!                                                                                                                      *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class MvcProtector
{
    private static $_singleton_instance;
    private $_renderer;

    public const USER = "user";
    public const RESTAURATOR = "restaurator";
    public const ADMIN = "administrator";

    //--------------------------------------------------------------------------------------------------------------------------------------

    protected function __construct($renderer)
    {
        $this->_renderer = $renderer;
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Wszyscy uzytkownicy zalogowani na konto USER mają dostęp do tych zasobów, w przeciwnym wypadku renderowanie strony błędu 401
     */
    public function protect_only_user()
    {
        $this->redirect_on_role(self::USER);
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Wszyscy uzytkownicy zalogowani na konto RESTAURATOR mają dostęp do tych zasobów, w przeciwnym wypadku renderowanie strony błędu 401
     */
    public function protect_only_restaurator()
    {
        $this->redirect_on_role(self::RESTAURATOR);
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Wszyscy uzytkownicy zalogowani na konto ADMIN mają dostęp do tych zasobów, w przeciwnym wypadku renderowanie strony błędu 401
     */
    public function protect_only_admin()
    {
        $this->redirect_on_role(self::ADMIN);
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

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

    //--------------------------------------------------------------------------------------------------------------------------------------
    
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
            else if ($role['role_name'] == self::RESTAURATOR)
                header('Location: ' . __URL_INIT_DIR__ . 'restaurant/panel/dashboard', true, 301);
            else if ($role['role_name'] == self::ADMIN) header('Location: ' . __URL_INIT_DIR__ . 'admin/panel/dashboard', true, 301);
        }
    }    

    //--------------------------------------------------------------------------------------------------------------------------------------

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
