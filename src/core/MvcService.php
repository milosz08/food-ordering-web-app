<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: MvcService.php                                 *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-11-10, 22:46:32                       *
 * Autor: Milosz08                                             *
 *                                                             *
 * Ostatnia modyfikacja: 2022-12-07 00:54:18                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Core;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Klasa abstrakcyjna MvcService. Każda klasa serwisu znajdująca się w folderze /services musi rozszerzać tą klasę. Klasa zapewnia       *
 * instancje klasy PdoDbContext oraz handler do bazy danych w celu łatwiejszego użytkowania w klasach pochodnych. NIE MODYFIKOWAĆ!       *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

abstract class MvcService
{
    private static $_singleton_instance;
    protected $pdo; // instancja klasy PdoDbContext w celu wykonywania operacji na bazie danych
    protected $dbh; // handler do bazy danych w celu wykonywania operacji (przede wszystkim zapytania SQL)
    protected $smtp_client; // instancja klasy SmtpMail umożliwiającej wysyłanie wiadomości email na wskazany adres

    //--------------------------------------------------------------------------------------------------------------------------------------

    protected function __construct()
    {
        $this->pdo = PdoDbContext::get_instance(); // pobranie instancji klasy PdoDbContext i przypisanie jej do pola
        $this->dbh = $this->pdo->get_handler(); // pobranie uchwytu do bazy danych z obiekty klasy PdoDbContext
        $this->smtp_client = SmtpMail::get_instance(); // pobranie instancji klasy SmtpMail i przypisanie jej do pola
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda odpowiedzialna za haszowanie hasła poprzez funkcję Bcrypt. Zawiera również sól.
     */
    protected function passwd_hash($value)
    {
        return password_hash($value, PASSWORD_BCRYPT);
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda tworząca obiekt klasy pochodnej po MvcService i zwracająca go. Jedyna metoda która pozwala na uzyskanie instancji klasy
     * MvcRenderer. Obiekt tworzony jest tylko wtedy, kiedy pole $_singleton_instance jest nullem (kiedy nie przypisano jeszcze obiektu).
     * Metoda przyjmuje parametr $service_clazz który jest nazwą klasy, jakiej obiekt metoda ma zainicjować, np. HomeService::class.
     */
    public static function get_instance($service_clazz)
    {
        if (!isset(self::$_singleton_instance)) self::$_singleton_instance = new $service_clazz;
        return self::$_singleton_instance;
    }
}
