/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2022 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: common.js                                      *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2022-11-10, 18:29:31                       *
 * Autor: Milosz08                                             *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-16 11:55:47                   *
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

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function showHidePassword() {
    $('.password-input-toggler').toArray().forEach(function (el) {
        const input = $(el).parent().find('>:first-child');
        const buttonIcon = $(el).find('>:first-child');
        $(el).on('click', function () {
            if (input.val() === '') return;
            buttonIcon.toggleClass('bi-eye-fill bi-eye-slash-fill');
            if (buttonIcon.hasClass('bi-eye-slash-fill')) {
                input.attr('type', 'text');
            } else {
                input.attr('type', 'password');
            }
        });
        input.on('input', function() {
            if (input.val() !== '') return;
            input.attr('type', 'password');
            buttonIcon.toggleClass('bi-eye-fill bi-eye-slash-fill');
        });
    });
};

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function disableInputsOnCheckedCheckbox() {
    $('.disable-checkbox').each(function (_, el) {
        const toggleDisabledInputs = function (checkbox) {
            const timeInputs = $(checkbox).parent().parent().parent().find('.control-dis');
            timeInputs.each(function (_, input) {
                $(input).attr('disabled', $(checkbox).is(":checked"));
            });
        };
        toggleDisabledInputs(el);
        $(el).on('change', function () { toggleDisabledInputs(this); });
    });
};

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function imagePreview() {
    $('.image-preview-container').toArray().forEach(function (el) {
        const imageContainer = $(el).find('.upload-file-preview');
        const removeButton = $(el).find('button.remove-photo');
        const image = $(el).find('.preview-image-src');
        const editPreview = el.dataset.imgPreviewSrc;
        if (editPreview) {
            image.css('background-image', 'url(public/' + editPreview + ')');
            imageContainer.css('display', 'block');
            removeButton.on('click', function() {
                imageContainer.css('display', 'none');
            });
            return;
        }
        const input = $(el).find('input[type="file"]');
        removeButton.on('click', function() {
            imageContainer.css('display', 'none');
            input.val('');
        });
        input.on('change', function() {
            const file = this.files[0];
            console.log(input);
            if (!file) return;
            if (URL) image.css('background-image', 'url(' + URL.createObjectURL(file) + ')');
            else if (FileReader) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    image.css('background-image', 'url(' + e.target.result + ')');
                }
                reader.readAsDataURL(file);
            }
            imageContainer.css('display', 'block');
        });
    });
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function showModal() {
    const modal = document.getElementById('logout-modal');
    if (modal !== null) new bootstrap.Modal(modal, {}).show();
};

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function chooseSelectedDisabled(select, text, area) {
    function disableTextarea() {
        const is_custom_type = $(select + ' option:selected').text() === text;
        $(area).prop('disabled', !is_custom_type);
        if (!is_custom_type) $(area).prop('value', '');
    };
    disableTextarea();
    $(select).on('change', disableTextarea);
};

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function lightDarkFaviconIcon() {
    const lightSchemeIcon = $('link#light-scheme-icon');
    const darkSchemeIcon = $('link#dark-scheme-icon');
    const onUpdate = function(isDark) {
        if (isDark) {
            lightSchemeIcon.remove();
            $('head').append(darkSchemeIcon);
        } else {
            $('head').append(lightSchemeIcon);
            darkSchemeIcon.remove();
        }
    };
    const matcher = window.matchMedia('(prefers-color-scheme: dark)');
    matcher.addEventListener('change', function(e) { changeColorTheme(e.matches); });
    onUpdate(matcher.matches);
};

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function onLoad() {
    showModal();
    showHidePassword();
    imagePreview();
    disableInputsOnCheckedCheckbox();
    lightDarkFaviconIcon();
    chooseSelectedDisabled('#form-type-dish', 'Niestandardowy typ potrawy', '#form-new-type-dish');
    
    $('.js-close').click(function () { $('#newsHeading').parent().fadeOut(); });
    $('.chb').on('change', function() { $('.chb').not(this).prop('checked', false); });
    $('.res').on('change', function() { $('.res').not(this).prop('checked', false); });
    $('[data-bs-toggle="tooltip"]').toArray().forEach(function(el) {
        new bootstrap.Tooltip(el);
    });
};

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$(window).on('load', onLoad);
