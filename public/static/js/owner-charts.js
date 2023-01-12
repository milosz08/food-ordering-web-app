/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: owner-charts.js                                *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-06, 04:16:09                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-12 03:27:03                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function generateOwnerCharData() {
    const baseDir = document.getElementById('server-dir');
    $.ajax({
        url: baseDir.dataset.contentDir + 'owner/dashboard/graph',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            new Chart('restaurantDashbaordChart', {
                type: 'bar',
                data: {
                    labels: res.map(v => new Date(v.day).toLocaleDateString('pl', { weekday: 'long' })).reverse(),
                    datasets: [
                        {
                            backgroundColor: [ '#03045e', '#0077b6', '#00b4d8', '#90e0ef', '#b5d1e2' , '#caf0f8', '#ffffff' ],
                            data: res.map(v => v.amount).reverse()
                        },
                    ],
                },
                options: {
                    legend: { display: false },
                    title: { display: true, fontSize: 18, text: 'Dane statystyczne zamówień' },
                    responsive: true,
                    maintainAspectRatio: false,
                },
            });
        }
    });
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function onLoad() {
    generateOwnerCharData();
};

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$(window).on('load', onLoad);
