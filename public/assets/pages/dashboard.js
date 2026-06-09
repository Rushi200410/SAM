
(function () {
    'use strict';

    var data = window.dashboardData || {
        weeklyLabels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        weeklyOntime: [0, 0, 0, 0, 0, 0, 0],
        weeklyLate: [0, 0, 0, 0, 0, 0, 0],
        todayOntime: 0,
        todayLate: 0,
        todayAbsent: 0
    };

    if (document.querySelector('#weekly-attendance-chart')) {
        new Chartist.Line('#weekly-attendance-chart', {
            labels: data.weeklyLabels,
            series: [
                data.weeklyOntime,
                data.weeklyLate
            ]
        }, {
            low: 0,
            showArea: true,
            showPoint: true,
            fullWidth: true,
            chartPadding: { top: 10, right: 20, bottom: 0, left: 10 },
            axisY: {
                onlyInteger: true,
                offset: 40
            },
            plugins: [
                Chartist.plugins.tooltip()
            ]
        });
    }

    var breakdownTotal = data.todayOntime + data.todayLate + data.todayAbsent;

    if (document.querySelector('#today-breakdown-chart') && breakdownTotal > 0) {
        new Chartist.Pie('#today-breakdown-chart', {
            series: [data.todayOntime, data.todayLate, data.todayAbsent],
            labels: ['On Time', 'Late', 'Absent']
        }, {
            donut: true,
            donutWidth: 40,
            showLabel: false,
            plugins: [
                Chartist.plugins.tooltip()
            ]
        });
    }
})();
