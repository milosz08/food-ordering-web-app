/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: owner-charts.js                                *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-06, 04:16:09                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-06 04:51:47                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function generateOwnerCharData() {
    const xValues = [ "Poniedziałek", "Wtorek", "Środa", "Czwartek", "Piątek", "Sobota" ];
    const yValues = [ 30, 22, 43, 21, 25, 30 ];
    const barColors = [ "#03045e", "#0077b6", "#00b4d8", "#90e0ef", "#b5d1e2" , "#caf0f8" ];

    new Chart("restaurantDashbaordChart", {
        type: "bar",
        data: {
            labels: xValues,
            datasets: [
                { backgroundColor: barColors, data: yValues },
            ],
        },
        options: {
            legend: { display: false },
            title: { display: true, fontSize: 18, text: "Dane statystyczne zamówień" },
            responsive: true,
            maintainAspectRatio: false,
        },
    });
};

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function onLoad() {
    generateOwnerCharData();
};

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$(window).on('load', onLoad);
