/*
 * Copyright (c) 2024 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: admin-charts.js                                *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2024-01-16, 13:46:05                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2024-06-08 00:35:39                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 */

function generateAdminCharData() {
    const baseDir = document.getElementById('server-dir');
    $.ajax({
        url: baseDir.dataset.contentDir + 'admin/dashboard/graph',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
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
                    title: { display: true, fontSize: 18, text: 'Dane statystyczne posiadanych kuponów przez daną restaurację' },
                    responsive: true,
                    maintainAspectRatio: false,
                },
            });
            
        }
    });
}

function onLoad() {
    generateAdminCharData();
    generateAdminCharv2Data();
};

$(window).on('load', onLoad);
