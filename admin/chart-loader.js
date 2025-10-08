document.addEventListener("DOMContentLoaded", function() {
    if (typeof PathPilotChartData !== "undefined") {
        const ctx = document.getElementById('pp-daily-stats-chart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: PathPilotChartData.dates,
                datasets: [
                    {
                        label: 'Page Views',
                        data: PathPilotChartData.page_views,
                        borderColor: '#36a2eb',
                        backgroundColor: 'rgba(54,162,235,0.1)',
                        yAxisID: 'y',
                    },
                    {
                        label: 'Conversions',
                        data: PathPilotChartData.conversions,
                        borderColor: '#4caf50',
                        backgroundColor: 'rgba(76,175,80,0.1)',
                        yAxisID: 'y',
                    },
                    {
                        label: 'Unique Visitors',
                        data: PathPilotChartData.unique_visitors,
                        borderColor: '#ff9800',
                        backgroundColor: 'rgba(255,152,0,0.1)',
                        yAxisID: 'y',
                    },
                    {
                        label: 'Conversion Rate (%)',
                        data: PathPilotChartData.conversion_rates,
                        borderColor: '#e91e63',
                        backgroundColor: 'rgba(233,30,99,0.1)',
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                stacked: false,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: false }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Count' }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Conversion Rate (%)' },
                        grid: { drawOnChartArea: false },
                        min: 0
                    }
                }
            }
        });
    }
});