<?php

namespace App\Core;

use Mustache_Autoloader;
use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

/**
 * Klasa przechowująca metody odpowiedzialne za ładowanie i renderowanie szablonów. Metoda load() uruchamia jest tylko raz podczas
 * uruchamiania aplikacji i ładuje kontekst szablonów Mustache to pamięci programu. Metoda render() natomiast umożliwia renderowanie
 * wybranego szablonu w metodach kontrolerów. NIE MODYFIKOWAĆ!
 */
class MvcRenderer
{
  private static $_singleton_instance; // instancja klasy MvcRenderer jako obiektu singleton
  private static $_mustache; // instancja klasy silnika szablonów Mustache

  private function __construct()
  {
  }

  /**
   * Metoda umożliwiająca załadowanie silnika szablonów Mustache przy starcie aplikacji. Metoda uruchamiana jest tylko jeden raz i musi
   * być uruchomiona po instrukcji require_once '../vendor/autoload.php';
   */
  public static function load()
  {
    Mustache_Autoloader::register(); // zarejestrowanie silnika szablonów mustache
    // wywołanie klasy Mustache_Engine odpowiadającej za ładowanie ścieżek do katalogów widoków i widoków częściowych
    self::$_mustache = new Mustache_Engine(array(
      'pragmas' => array(Mustache_Engine::PRAGMA_BLOCKS),
      'loader' => new Mustache_Loader_FilesystemLoader(__SRC_DIR__ . '/views'), // ładowanie widoków
      'partials_loader' => new Mustache_Loader_FilesystemLoader(__SRC_DIR__ . '/views'), // ładowanie widoków częściowych
    ));
  }

  /**
   * Metoda tworząca obiekt klasy MvcRenderer i zwracająca go. Jedyna metoda która pozwala na uzyskanie instancji klasy MvcRenderer.
   * Obiekt tworzony jest tylko wtedy, kiedy pole $_singleton_instance jest NULL (kiedy nie przypisano jeszcze obiektu).
   */
  public static function get_instance(): MvcRenderer
  {
    if (!isset(self::$_singleton_instance)) {
      self::$_singleton_instance = new MvcRenderer;
    }
    return self::$_singleton_instance;
  }

  /**
   * Metoda umożliwiająca renderowanie widoku zagnieżdżonego na podstawie nazwy szablonu wrapper (owijającego) przekazywanego w
   * parametrze $wrapper_pattern_name oraz nazwy szablonu zagnieżdżonego przekazywanego w parametrze $embed_pattern_name. Metoda dodatkowo
   * przyjmuje parametr $data w postaci tablicy właściwości przekazywanych do szablonu.
   */
  public function render_embed($wrapper_pattern_name, $embed_pattern_name, $data = array())
  {
    $additional_embed_view_data = array_merge($data, array(
      // wstaw funkcję wywołania zwrotnego szablonu renderowanego w szablonie owijającym
      'embed_rendering_section' => function () use ($embed_pattern_name) {
        return '{{> ' . $embed_pattern_name . ' }}';
      },
    ));
    $this->render('_wrapper/' . $wrapper_pattern_name, $additional_embed_view_data); // renderuj widok
  }

  /**
   * Metoda umożliwiająca renderowanie wybranego szablonu mustache. Przyjmuje dwa parametry. Pierwszy to nazwa generowanego szablonu
   * zgodnie z drzewem katalogów, np dla szablonu znajdującego się w:
   *
   *      views/
   *      ├─ home/
   *      │  ├─ home-view.mustache
   *
   * jako parametr $pattern_name należy podać "home/home-view". Drugi parametr to tablica danych przekazywanych do szablonu. Parametr
   * ten jest opcjonalny, domyślnie przypisywana jest pusta tablica. Metoda wstawia wybrany szablon jako obiekt wbudowany w szablon
   * główny: "wrapper-view.mustache". Dzięki temu do szablonu głównego można przesłać dodatkowe dane takie jak:
   *      - tytuł strony (nazwa klucza tablicy asocjacyjnej $data: 'page-title'), domyślnie __PAGE_TITLE__ (patrz config.php)
   */
  public function render($pattern_name, $data = array())
  {
    $template = self::$_mustache->loadTemplate('_wrapper/wrapper-view'); // załaduj główny szablon owijający pozostałe szablony
    $extended_data = array_merge($data, array(
      // wstaw funkcję wywołania zwrotnego szablonu renderowanego w szablonie owijającym
      'embed_rendering_content' => function () use ($pattern_name) {
        return '{{> ' . $pattern_name . ' }}';
      },
      // suffix tytuły strony internetowej (patrz config.php)
      'suffix_page_title' => Config::get('__SUFFIX_PAGE_TITLE__'),
      // jeśli użytkownik ustawił niestandardowy tytuł, przypisz do embed_page_title, w przeciwnym razie przypisz wartość
      // __PAGE_TITLE__ (patrz plik config.php)
      'embed_page_title' => $data['page_title'] ?? Config::get('__PAGE_TITLE__'),
      'logged_user' => $_SESSION['logged_user'] ?? '', // dane zalogowanego użytkownika przekazywane do widoku
      'base_dir' => __URL_INIT_DIR__, // bazowy katalog projektu, do linków
      'curr_date' => date("Y"), // aktualna data (używana do stopki)
      'is_logged_and_normal_user' => isset($_SESSION['logged_user']) && $_SESSION['logged_user']['is_normal_user'],
      'init_path' => Config::get('__DEF_APP_HOST__'),
    ));
    echo $template->render($extended_data); // wyrenderuj, sprasuj i wyświetl szablon
  }
}
