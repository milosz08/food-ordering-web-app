<?php

namespace App\Core;

/**
 * Klasa abstrakcyjna MvcController. Każdy kontroler aplikacji znajdujący się w katalogu /controllers musi rozszerzać tą klasę. Klasa
 * zapewnia w klasach pochodnych instancję MvcRenderer, który umożliwia generowanie szablonów Mustache. Klasa posiada jedną metodę
 * abstrakcyjną index() która musi być zaimplementowana w klasach potomnych (metoda wywoływana, jeśli poda się w parametrach zapytania
 * jedynie nazwę kontrolera, np. index.php?action=home). NIE MODYFIKOWAĆ!
 */
abstract class MvcController
{
  protected $renderer; // instancja klasy MvcRenderer służącej do renderowania szablonów
  protected $protector; // instancja klasy MvcProtector służącej do ochrony adresu URL

  protected function __construct()
  {
    $this->renderer = MvcRenderer::get_instance(); // pobranie instancji klasy MvcRenderer i przypisanie jej do pola
    $this->protector = MvcProtector::get_instance($this->renderer); // pobranie instancji klasy MvcProtector i przypisanie jej do pola
  }

  /**
   * Metoda abstrakcyjna, która musi być zaimplementowana w klasach dziedziczących po MvcController. Odwołanie do niej następuje wówczas,
   * kiedy nie sprecyzowano metody kontrolera, w przypadku ścieżki np.
   *      index.php?action=home
   * Dobrym sposobem jest zostawienie tej metody pustej i przekierowanie do faktycznej metody np. show poprzez funkcję header().
   */
  public abstract function index();
}
