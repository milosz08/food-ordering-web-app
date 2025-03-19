function generateOwnerCharData() {
  const baseDir = document.getElementById('server-dir');
  $.ajax({
    url: baseDir.dataset.contentDir + 'owner/dashboard/graph',
    type: 'GET',
    dataType: 'json',
    success: function (res) {
      new Chart('restaurantDashboardChart', {
        type: 'bar',
        data: {
          labels: res['orders'].map(v => new Date(v["Day"]).toLocaleDateString('pl', {weekday: 'long'})).reverse(),
          datasets: [
            {
              backgroundColor: ['#03045e', '#0077b6', '#00b4d8', '#90e0ef', '#b5d1e2', '#caf0f8', '#27ffef'],
              data: res['orders'].map(v => v["Amount"]).reverse()
            },
          ],
        },
        options: {
          legend: {display: false},
          title: {
            display: true,
            fontSize: 18,
            text: 'Dane statystyczne wszystkich zamówień posiadanych restauracji',
          },
          responsive: true,
          maintainAspectRatio: false,
        },
      });
    }
  });
}

function generateOwnerV2CharData() {
  const barColors = ['#03045e', '#0077b6', '#00b4d8', '#90e0ef', '#b5d1e2', '#caf0f8', '#27ffef', '#03045e', '#0077b6', '#00b4d8',];
  const baseDir = document.getElementById('server-dir');
  $.ajax({
    url: baseDir.dataset.contentDir + 'owner/dashboard/graph',
    type: 'GET',
    dataType: 'json',
    success: function (res) {
      new Chart('restaurantDashboardV2Chart', {
        type: 'bar',
        data: {
          labels: res['coupons'].map(v => v.Name),
          datasets: [
            {
              backgroundColor: barColors,
              data: res['coupons'].map(v => v["Uses"])
            },
          ],
        },
        options: {
          legend: {display: false},
          title: {
            display: true,
            fontSize: 18,
            text: 'Dane statystyczne użyć poszczególnych kuponów',
          },
          responsive: true,
          maintainAspectRatio: false,
        },
      });
    }
  });
}

function onLoad() {
  generateOwnerCharData();
  generateOwnerV2CharData();
}

$(window).on('load', onLoad);
