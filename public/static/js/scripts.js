/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: scripts.js                                     *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-11-10, 18:29:31                       *
 * Autor: Milosz08                                             *
 *                                                             *
 * Ostatnia modyfikacja: 2022-12-13 22:23:30                   *
 * Modyfikowany przez: patrick012016                           *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * W tym pliku należy umieszczać wszystkie niestandardowe skrypty JavaScript. Załadowane zostaną do wszystkich podstron projektu.        *
 * Skrypty które mają być wywołane dopiero po załadowaniu się struktury drzewa DOM należy umieszczać w funkcji zwrotnej w której         *
 * widnieje instrukcja console.log('Hello Web!').                                                                                        *
 *                                                                                                                                       *
 * WAŻNE: Jeśli skrypt odnosi się do obrazka lub innego zasobu z serwera, powinien być wewnątrz funkcji window.onload. W innym wypadku   *
 * załaduje się jeszcze przed załadowaniem obrazka i nie znajdzie zasobu na którym ma wykonać akcję.                                     *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Skrypt umoliwiający pokazywanie/chowanie hasła w polu input na przycisk
 */
const passVisibilityUI = {
    pswVisibilityBtn: document.querySelectorAll('.password-input-toggler'),

    pswVisibilityInvoker: function () {
        if (passVisibilityUI.pswVisibilityBtn === null) return;
        const invokeOnClick = function (el) {
            const inputChild = el.parentNode.firstElementChild;
            const buttonIcon = el.parentNode.children[1].firstElementChild;
            el.addEventListener('click', function () {
                if (inputChild.value.length === 0) return;
                inputChild.type = inputChild.type === 'text' ? 'password' : 'text';
                if (buttonIcon.classList.contains('bi-eye-slash-fill')) {
                    buttonIcon.classList.remove('bi-eye-slash-fill');
                    buttonIcon.classList.add('bi-eye-fill');
                } else {
                    buttonIcon.classList.remove('bi-eye-fill');
                    buttonIcon.classList.add('bi-eye-slash-fill');
                }
            });
            inputChild.addEventListener('input', function () {
                if (this.value !== '') return;
                inputChild.type = 'password';
                buttonIcon.classList.remove('bi-eye-slash-fill');
                buttonIcon.classList.add('bi-eye-fill');
            });
        };
        this.pswVisibilityBtn.forEach(invokeOnClick.bind(this), false);
    },
};

/**
 * Skrypt pokazujący na stałe modal odpowiadający za informowanie użytkownika o poprawnym wylogowaniu
 */
const showLogoutModalOnLoad = {
    modalElm: document.getElementById('logout-modal'),

    showModalInvoker: function() {
        if (this.modalElm === null) return;
        const myModal = new bootstrap.Modal(this.modalElm, {});
        myModal.show();
    },
};

/**
 * Skrypt odpowiadający za ładowanie tooltipów z Bootstrapa.
 */
const initializeBtsTooltipsOnLoad = {
    initializeTooltipsInvoker: function() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
        tooltipTriggerList.forEach(function(el) {
            new bootstrap.Tooltip(el);
        });
    }
};

//------------------------------------------------------------------------------------------------------------------------------------------

window.addEventListener('load', function () {
    initializeBtsTooltipsOnLoad.initializeTooltipsInvoker();
    passVisibilityUI.pswVisibilityInvoker();
    showLogoutModalOnLoad.showModalInvoker();
});


//------------------------------------------------------------------------------------------------------------------------------------------

/**
 * Skrypt odpowiadający za generowanie wykresów
 */
var xValues = ["Poniedziałek", "Wtorek", "Środa", "Czwartek", "Piątek", "Sobota"];
var yValues = [16, 22, 16, 21, 25, 30];
var barColors = ["red", "green","blue","orange","purple", "grey",];

new Chart("myChart", {
  type: "bar",
  data: {
    labels: xValues,
    datasets: [{
      backgroundColor: barColors,
      data: yValues
    }]
  },
  options: {
    legend: {display: false},
    title: {
      display: true,
      fontSize: 18,
      text: "Dane statystyczne zamówień"
    }
  }
});
