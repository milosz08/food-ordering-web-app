<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: SmtpMail.php                                   *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-12-01, 15:08:51                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2022-12-02 00:46:24                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Core;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Klasa przechowująca instancję klasy PHPMailer z biblioteki umożliwiającą obsługę wiadomości email przez kod PHP. Klasa jest tworem    *
 * singleton, toteż nie ma potrzeby tworzenia manualnych instancji w kodzie. Do klasy można odwołać się poprzez metodę get_instance().   *
 * Parametry połączenia z serwerem zaciągane są z pliku .env. Klasa przechowuje jedną publiczną metodę umożliwiającą wysłanie wiadomości *
 * na wskazany adres poprzez szablon HTML pobierany z zewnętrznego pliku w folderze ~/templates.                                         *
 *                                                                                                                                       *
 * Po więcej szczegółów na temat biblioteki, przedź pod adres: https://github.com/PHPMailer/PHPMailer                                    *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class SmtpMail
{
    private static $_instance; // instancja klasy SmtpMail jako obiektu singleton
    private $_smtpClient; // klient serwera SMTP

    //--------------------------------------------------------------------------------------------------------------------------------------
    
    /**
     * Inicjalizacja zasobów biblioteki, serwera i stworzenie podstawowej instancji głównej klasy. Jeśli nie powiedzie się nawiązanie
     * połączenia z serwerem SMTP, wyrzuci wyjątek i zakończy wykonywanie skryptu
     */
    private function __construct()
    {
        $this->_smtpClient = new PHPMailer(true); // ustawienie flagi true umożliwia wyrzucanie wyjątków
        $this->_smtpClient->isSMTP(); // ustawienie usługi email jako SMTP (Simple Mail Transfer Protocol)
        $this->_smtpClient->Host = Config::get('__SMTP_HOST__'); // adres domeny serwera SMTP
        $this->_smtpClient->SMTPAuth = true; // uruchomienie możliwości uwierzytelniania serwera SMTP
        $this->_smtpClient->Username = Config::get('__SMTP_USERNAME__'); // adres email z którego serwer SMTP będzie wysyłał wiadomości
        $this->_smtpClient->Password = Config::get('__SMTP_PASSWORD__'); // hasło do konta email z serwera SMTP 
        $this->_smtpClient->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // ustawienie szyfrowania na SMTPS (Secured)
        $this->_smtpClient->Port = 465; // standardowy port połączenia szyfrowanego dla połączenia protokołem SMTPS
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda umożliwiająca wysłanie wiadomości email to wskazanego klienta. Parametr $sendTo określa adres email klienta, parametr
     * $subject temat wyświetlany w nagłówku wiadomości a parametr $body_template_path nazwę szablonu HTML na podstawie którego będzie
     * generowana wiadomość. Przykładowo, jeśli szablon znajduje się:
     * 
     *      src/
     *      ├─ templates/
     *      ├──── test-email.template.html
     * 
     * To należy przekazać parametr $body_template_path jako "test-email". WAŻNE! Każdy plik szablonu musi kończyć się nazwą .template.html,
     * patrz przykład wyżej. Dodatkowo szablon przyjmuje zmienne. Zmienne definiowane są poprzez {{zmienna}}, gdzie zmienna to nazwa tej
     * zmiennej (podobnie jak w szablonach Mustache). Opcjonalnie można przekazać tablicę załączników w formie linków określających ścieżkę
     * do pliku na serwerze.
     * 
     * Jako że instancja tej klasy jest wstrzykiwana w klasie MvcService, każdy serwis otrzymuje dostęp do obiektu tej klasy i uruchomienie
     * metody send_message(). Przykładowe użycie w serwisie aplikacji:
     * 
     *      $variables = array('user_name' => 'Adam Nowak'); // <- tablica zmiennych (klucz) pod które będą podstawione wartości
     *      $this->smpt_client->send_message('example@email.com', 'To jest testowy temat', 'test-email', $variables);
     * 
     * UWAGA! Funkcja po każdym uruchomieniu (odświeżeniu strony) dokona próby wysłania wiadomości email na podany adres. Funkcję wysyłania
     * wiadomości należy umieścić w instrukcji if i uruchamiać tylko wtedy, gdy to konieczne.
     * 
     * Po wysłaniu wiadomości metoda zwraca tablicę asocjacyjną, w której właściwość status_error jest ustawiana na true, jeśli doszło do
     * błędu w trakcie wysyłania wiadomości (jeśli bez błędów, ustawiona jest na false) oraz właściwość status_message zawierającą
     * podstawową wiadomość o wysłanej wiadomości email.
     */
    public function send_message($sendTo, $subject, $body_template_path, $template_vars = array(), $attachments_path = array())
    {
        $send_status_error = false;
        $send_status_message = '';
        try
        {
            $this->_smtpClient->setFrom(Config::get('__SMTP_USERNAME__'), 'Dobre.pl'); // ustaw adres serwera SMTP
            $this->_smtpClient->addAddress($sendTo); // ustaw adres email odbiorcy do którego ma trafić wiadomość email
            
            // ustawienie adresu email na który dojdzie wiadomość, jeśli klient odpowie na email automatycznie wysłany przez serwer
            $this->_smtpClient->addReplyTo(Config::get('__SMTP_AUTO_REPLY__'), 'Informacja');
            
            // przypisz wszystkie ścieżki do załączników do wysyłanej wiadomości email, jeśli przekazano jakieś załączniki
            foreach ($attachments_path as $attachement_path) $this->_smtpClient->addAttachment($attachement_path);
                
            $this->_smtpClient->CharSet = 'UTF-8'; // wymuszenie kodowania znaków na UTF-8
            $this->_smtpClient->isHTML(true); // ustawienie wysyłanego kontentu jako text/html
            $this->_smtpClient->Subject = $subject; // przypisanie tytułu otrzymywanego z parametru do wiadomości email
            
            // sparsuj wiadomość email w formie dokumentu html do ciągu znaków (zmiennej string)
            $body_content = file_get_contents(__SRC_DIR__ . 'templates' . __SEP__ . $body_template_path .'.template.html');
            if (!$body_content) throw new Exception(); // jeśli nie znajdzie pliku, wyrzuć wyjątek

            // funkcja zamieniająca wszystkie literały szablonowe {{zmienna}} na podstawie wartości z tablicy w zmiennej $template_vars
            foreach($template_vars as $template => $value)
            {
                $body_content = str_replace('{{' . $template . '}}', $value, $body_content);
            }

            $this->_smtpClient->Body = $body_content; // przypisz sparsowany szablon HTML jako wiadomość email
            $this->_smtpClient->send(); // wysłanie wiadomości
            $send_status_message = 'Wiadomość została pomyślnie wysłana na adres: ' . $sendTo . '.';
        }
        catch (Exception $e) // jeśli nie zdoła wysłać wiadomości email na jeden z podanych adresów, przechwyć wyjątek
        {
            $send_status_error = true;
            $send_status_message = 'Nieudane wysłanie wiadomości email na adres: ' . $sendTo . '. ' . $this->_smtpClient->ErrorInfo;
        }
        // zwróć tablicę asocjacyjną zawierającą error status (true jeśli błąd, false jeśli wszystko ok) oraz wiadomość po wysłaniu
        return array(
            'status_error' => $send_status_error,
            'status_message' => $send_status_message,
        );
    }

    //--------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Metoda statyczna umożliwiająca instantancję klasy SmtpMail. Uruchomić można ją tylko raz (tylko raz dojdzie do stworzenia 
     * obiektu). Jeśli obiekt będzie już istniał, pobierze referencję do niego z pamięci.
     */
    public static function get_instance()
    {
        if (self::$_instance == null) self::$_instance = new SmtpMail;
        return self::$_instance;
    }
}
