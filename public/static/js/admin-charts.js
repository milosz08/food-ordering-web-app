/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: admin-charts.js                                *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-06, 04:16:09                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-12 21:09:38                   *
 * Modyfikowany przez: patrick012016                           *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function generateAdminCharData() {
    const baseDir = document.getElementById('server-dir');
    $.ajax({
        url: baseDir.dataset.contentDir + 'admin/dashboard/graph',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            console.log(res);
        
            new Chart('adminDashbaordChart', {
                type: 'bar',
                data: {
                    labels: res['orders'].map(v => new Date(v.Day).toLocaleDateString('pl', { weekday: 'long' })).reverse(),
                    datasets: [
                        {
                            backgroundColor: [ '#03045e', '#0077b6', '#00b4d8', '#90e0ef', '#b5d1e2' , '#caf0f8', '#27ffef' ],
                            data: res['orders'].map(v => v.Amount).reverse()
                        },
                    ],
                },
                options: {
                    legend: { display: false },
                    title: { display: true, fontSize: 18, text: 'Dane statystyczne zamówień wszystkich restauracji' },
                    responsive: true,
                    maintainAspectRatio: false,
                },
            });
            
        }
    });
}

function generateAdminCharv2Data() {
    var barColors = ['#03045e', '#0077b6', '#00b4d8', '#90e0ef', '#b5d1e2' , '#caf0f8', '#27ffef', '#03045e', '#0077b6', '#00b4d8',];
    const baseDir = document.getElementById('server-dir');
    $.ajax({
        url: baseDir.dataset.contentDir + 'admin/dashboard/graph',
        type: 'GET',
        dataType: 'json',
        success: function(res) {      
            new Chart('adminDashbaordv2Chart', {
                type: 'bar',
                data: {
                    labels: res['coupons'].map(v => v.Name),
                    datasets: [
                        {
                            backgroundColor: barColors,
                            data: res['coupons'].map(v => v.Uses)
                        },
                    ],
                },
                options: {
                    legend: { display: false },
                    title: { display: true, fontSize: 18, text: 'Dane statystyczne użyć poszczególnych kuponów wszystkich restauracji' },
                    responsive: true,
                    maintainAspectRatio: false,
                },
            });
            
        }
    });
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function onLoad() {
    generateAdminCharData();
    generateAdminCharv2Data();
};

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$(window).on('load', onLoad);