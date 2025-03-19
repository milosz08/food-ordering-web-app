<?php

use App\Core\Config;

/**
 * W tym pliku należy umieszczać wartości konfiguracyjne, stałe i inne elementy konfiguracji PHP. Zmienną konfiguracyjną deklaruje się
 * poprzez odwołanie do klasy Config i wywołanie na niej statycznej metody set. Metoda nie pozwoli na stworzenie dwóch wartości
 * konfiguracyjnych z tym samym kluczem. Przykładowe zadeklarowanie wartości konfiguracyjnej:
 *
 * Config::set('__KLUCZ__', 'wartość');
 *
 * Aby odwołać się do tej zmiennej w programie należy użyć metody statycznej get. Metoda zwróci wartość znajdującą się pod wybranym
 * kluczem. Jeśli klucz nie będzie istniał wyrzuci wyjątek. Przykład użycia:
 *
 * Config::get('__KLUCZ__');
 */

Config::set('__MVC_DEF_METHOD__', 'index'); // domyślna metoda kontrolera uruchamiana w przypadku braku parametru action w zapytaniu
Config::set('__MVC_CONTROLLER_SUFFIX__', 'Controller'); // domyślny sufiks plików kontrolerów (np. Home>Controller<, Example>Controller<) itp.
Config::set('__MVC_CONTROLLER_DIR__', Config::build_path(__DIR__, 'controllers')); // domyślny katalog przechowywania kontrolerów aplikacji
Config::set('__MVC_CONTROLLER_NAMESPACE__', 'App\Controllers'); // domyślna przestrzeń nazw dla kontrolerów

Config::set('__PAGE_TITLE__', 'Start'); // domyślny tytuł (prefix, po złączeniu z sufiksem da) Start | Dobre.pl
Config::set('__SUFFIX_PAGE_TITLE__', ' | Dobre.pl'); // suffix tytułu (pojawia się po dynamiczne generowanym tytule strony)

Config::set('__DB_DSN__', $_ENV['DB_DSN']); // data source name do bazy danych
Config::set('__DB_USERNAME__', $_ENV['DB_USERNAME']); // nazwa użytkownika bazy danych
Config::set('__DB_PASSWORD__', $_ENV['DB_PASSWORD']); // hasło użytkownika bazy danych
Config::set('__DB_INIT_COMMANDS__', array(PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES "UTF8"')); // wymuszenie kodowania znaków UTF-8

Config::set('__SMTP_HOST__', $_ENV['SMTP_HOST']); // adres domenowy serwera SMTP
Config::set('__SMTP_PORT__', $_ENV['SMTP_PORT']); // port serwera SMTP
Config::set('__SMTP_USERNAME__', $_ENV['SMTP_USERNAME']); // nazwa użytkownika serwera SMTP
Config::set('__SMTP_PASSWORD__', $_ENV['SMTP_PASSWORD']); // hasło do konta z którego serwer będzie wysyłał wiadomości
Config::set('__SMTP_SENDER__', $_ENV['SMTP_SENDER']); // email używany do wysyłania wiadomości przez serwer
Config::set('__SMTP_AUTO_REPLY__', $_ENV['SMTP_LOOPBACK']); // email alternatywny, używany do odpowiadania na wiadomości serwera
Config::set('__SMTP_ENCRYPTION_TYPE__', $_ENV['SMTP_ENCRYPTION_TYPE']); // ''/tls/ssl, pusty string to brak szyfrowania

Config::set('__DEF_APP_HOST__', __PROTO__ . $_SERVER['HTTP_HOST'] . __URL_INIT_DIR__); // domyślny host serwera

// regexy do walidacji pól formularzy
Config::set('__REGEX_CITY__', '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ\- ]{2,60}$/');
Config::set('__REGEX_STREET__', '/^[a-zA-Z0-9ąćęłńóśźżĄĆĘŁŃÓŚŹŻ\- ]{2,100}$/');
Config::set('__REGEX_POSTCODE__', '/^[0-9]{2}-[0-9]{3}$/');
Config::set('__REGEX_BUILDING_NO__', '/^([0-9]+(?:[a-z]{0,1})){1,5}$/');
Config::set('__REGEX_PASSWORD__', '/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*]).{8,}$/');
Config::set('__REGEX_LOGIN__', '/^[a-zA-Z0-9]{5,30}$/');
Config::set('__REGEX_LOGIN_EMAIL__', '/^[a-zA-Z0-9@.]{5,100}$/');
Config::set('__REGEX_PRICE__', '/^[1-9]{1}(?:[0-9])?(?:[\.\,][0-9]{1,2})?$/');
Config::set('__REGEX_OTA__', '/^[0-9A-Za-z]{10,}$/');
Config::set('__REGEX_DESCRIPTION__', '/^.{10,600}$/');
Config::set('__REGEX_PHONE_PL__', '/^[0-9]{3}( |)[0-9]{3}( |)[0-9]{3}$/');
Config::set('__REGEX_DATE__', '/^((?:19|20)\\d\\d)-(0?[1-9]|1[012])-([12][0-9]|3[01]|0?[1-9])$/');
