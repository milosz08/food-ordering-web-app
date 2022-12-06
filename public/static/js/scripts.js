/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: scripts.js                                     *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-11-10, 18:29:31                       *
 * Autor: Milosz08                                             *
 *                                                             *
 * Ostatnia modyfikacja: 2022-12-07 00:22:29                   *
 * Modyfikowany przez: Miłosz Gilga                            *
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
                buttonIcon.innerText = buttonIcon.innerText === 'visibility_off' ? 'visibility' : 'visibility_off';
            });
            inputChild.addEventListener('input', function () {
                if (this.value !== '') return;
                inputChild.type = 'password';
                buttonIcon.innerText = 'visibility';
            });
        };
        this.pswVisibilityBtn.forEach(invokeOnClick.bind(this), false);
    },
};

const showLogoutModalOnLoad = {
    showModalInvoker: function() {
        const myModal = new bootstrap.Modal(document.getElementById('logout-modal'), {});
        myModal.show();
    },
};

//------------------------------------------------------------------------------------------------------------------------------------------

window.addEventListener('load', function () {
    passVisibilityUI.pswVisibilityInvoker();
    showLogoutModalOnLoad.showModalInvoker();
});
