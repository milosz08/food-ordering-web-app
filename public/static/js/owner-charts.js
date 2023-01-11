/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: owner-charts.js                                *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-06, 04:16:09                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-11 21:13:56                   *
 * Modyfikowany przez: patrick012016                           *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function generateOwnerCharData() {
    var dataDayName = [];
    const options = { weekday: 'long' };
    for (let i = 0; i < 6; i++) 
    {
        const input = new Date(jArray[i]["Day"]);
        dataDayName[i] = input.toLocaleDateString(undefined, options);
    }

    const xValues = [ dataDayName[5], dataDayName[4], dataDayName[3], dataDayName[2], dataDayName[1], dataDayName[0] ];
    const yValues = [ jArray[5]["Amount"], jArray[4]["Amount"], jArray[3]["Amount"], jArray[2]["Amount"], jArray[1]["Amount"], jArray[0]["Amount"] ];
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
