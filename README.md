# Projekt Serwisy Internetowe (SI).

## UWAGA!
Commity należy wrzucać tylko i wyłącznie na branch `CPSIP-2-Development`. Branch `CPSIP-1-Initial-project-boilerplate` ma zostać nienaruszony, aby
móc w razie czego wrócić do bazowej konfiguracji projektu. Na branchu `master` znajduje się jedynie przetestowany, zweryfikowany i działający kod
(tyczy się to również formatowania i komentarzy).

## Klonowanie repo
```
$ git clone https://github.com/Milosz08/SUoT_SI_Project_PHP restaurant-project-php-si
```

## Przed uruchomieniem
Przed uruchomieniem projektu należy zainstalować PHP Composer na komputerze przez link: https://getcomposer.org/Composer-Setup.exe<br>

Przed instalacją PHP Composer należy sprawdzić zainstalowaną wersję PHP w CMD poprzez komendę: `php -v`.<br>

Wersja musi być zgodna z wersją PHP 7.4.30.<br>

Jeśli nie umie wyszukać ścieżki, należy dodać do zmiennej systemowej `PATH` ścieżkę do php, np. `C:\xampp\php`.<br>

Po instalacji PHP Composer należy sprawdzić, czy poprawnie się zainstalował poprzez komendę w CMD: `composer --version`.<br>

Po otworzeniu projektu należy otworzyć terminal i wpisać `composer install` aby zainstalować zależności.

## Struktura katalogów
```
restaurant-project-php-si/
├─ .vscode/                  1
├─ public/                   2
│  ├─ static/                3
│  │  ├─ css/                4
│  │  ├─ images/             5
│  │  ├─ js/                 6
│  ├─ index.php              7
│  ├─ uploads/               8
├─ src/                      9
│  ├─ controllers/           10
│  ├─ core/                  11
│  ├─ models/                12
│  ├─ scss/                  13
│  ├─ services/              14
│  ├─ utils/                 15
│  ├─ views/                 16
│  │  ├─ partials/           17
├─ vendor/                   18
├─ .htaccess                 19
├─ composer.json             20
├─ composer.lock             21
```

### Legenda:
1. folder z ustawieniami edytora Visual Studio Code
2. folder z zasobami udostępnionymi dla użytkownika (zdjęcia, skrypty, style itp.)
3. folder z zasobami statycznymi (które nie ulegają zmianie przez cykl życia aplikacji)
4. folder ze stylami (automatycznie skompilowane pliki scss i załadowany bootstrap), NIE MODYFIKOWAĆ!
5. folder z statycznymi obrazkami (logo, ikony itp) które nie zostaną przesłane przez serwer
6. folder ze skryptami JavaScript oraz załadowane skrypty Bootstrap
7. główny plik rozruchowy i ładujący aplikację, NIE MODYFIKOWAĆ!
8. plik z zasobami które zarządzane są przez serwer PHP, NIE MODYFIKOWAĆ!
9. folder z kodem źródłowym aplikacji, niewidoczny dla użytkownika końcowego
10. tutaj należy umieszczać wszystkie kontrolery aplikacji
11. folder z rdzeniem aplikacji i plikami rozruchowymi
12. tutaj należy umieszczać modele (klasy PHP) używane w aplikacji
13. tutaj należy umieszczać pliki styli w SCSS (które są automatycznie kompilowane do CSS)
14. tutaj należy umieszczać klasy serwisów (logika biznesowa dla kontrolerów)
15. dodatkowe klasy pomocnicze (używane w całej aplikacji)
16. tutaj należy umieszczać widoki (pliki .mustache, .html)
17. tutaj należy umieszczać częściowe widoki możliwe do implementacji w pełnych widokach
18. folder managera zasobów PHP Composer, NIE MODYFIKOWAĆ!
19. plik przekierowujący użytkownika do pliku index.php w folderze /public, NIE MODYFIKOWAĆ!
20. plik konfiguracyjny aplikacji, NIE MODYFIKOWAĆ!
21. plik wersjonowania zależności zarządzalny przez PHP Composer, NIE MODYFIKOWAĆ!

## Linki:
Dokumentacja PHP: https://www.php.net/manual/en/<br>
Dokumentacja Mustache: https://media.readthedocs.org/pdf/phly_mustache/latest/phly_mustache.pdf<br>
PDO: https://www.php.net/manual/en/book.pdo.php
