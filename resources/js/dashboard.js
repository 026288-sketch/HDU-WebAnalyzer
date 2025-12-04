document.addEventListener('DOMContentLoaded', function () {

    // 🟢 Chart and statistics initialization
    // Check for element existence to avoid errors on other pages
    if (document.getElementById('parsingChart')) {
        initCharts();
    }

    // 🟢 Service status check
    // ADDED: Check if the status element exists on the page.
    // If the element is missing (we are not on the dashboard), do not start the check and interval.
    const statusElement = document.getElementById('puppeteer-status-badge');
    
    if (statusElement) {
        // Run immediately on load
        checkServicesStatus();
        
        // And set a timer for every 10 seconds
        setInterval(checkServicesStatus, 10000);
    }
});

/**
 * Chart.js initialization and update
 */
function initCharts() {
    const chartData = window.chartData;
    
    // If no data, exit
    if (!chartData) return;

    let currentGroup = 'daily';
    let currentPeriod = { days: 30, dateFrom: null, dateTo: null };

    // --- Date initialization ---
    const today = new Date();
    const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));

    const dateFromEl = document.getElementById('dateFrom');
    const dateToEl = document.getElementById('dateTo');
    const exportDateFromEl = document.getElementById('exportDateFrom');
    const exportDateToEl = document.getElementById('exportDateTo');

    if (dateFromEl) dateFromEl.valueAsDate = thirtyDaysAgo;
    if (dateToEl) dateToEl.valueAsDate = today;
    if (exportDateFromEl) exportDateFromEl.valueAsDate = thirtyDaysAgo;
    if (exportDateToEl) exportDateToEl.valueAsDate = today;

    // --- Chart creation ---
    
    const parsingChart = new Chart(document.getElementById('parsingChart'), {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Оброблено статей', // Label kept as is for UI consistency, or change to English if needed
                data: chartData.nodesParsed,
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: chartOptions()
    });

    const sentimentChart = new Chart(document.getElementById('sentimentChart'), {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: 'Позитивні',
                    data: chartData.sentimentPositive,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Негативні',
                    data: chartData.sentimentNegative,
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4
                }
            ]
        },
        options: chartOptions()
    });

    const emotionsChart = new Chart(document.getElementById('emotionsChart'), {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [
                { label: 'Гнів', data: chartData.emotions?.anger || [], borderColor: 'rgb(220, 38, 38)', backgroundColor: 'rgba(220,38,38,0.1)', tension: 0.4 },
                { label: 'Радість', data: chartData.emotions?.joy || [], borderColor: 'rgb(250, 204, 21)', backgroundColor: 'rgba(250,204,21,0.1)', tension: 0.4 },
                { label: 'Страх', data: chartData.emotions?.fear || [], borderColor: 'rgb(139, 92, 246)', backgroundColor: 'rgba(139,92,246,0.1)', tension: 0.4 },
                { label: 'Сум', data: chartData.emotions?.sadness || [], borderColor: 'rgb(59, 130, 246)', backgroundColor: 'rgba(59,130,246,0.1)', tension: 0.4 },
                { label: 'Огида', data: chartData.emotions?.disgust || [], borderColor: 'rgb(34, 197, 94)', backgroundColor: 'rgba(34,197,94,0.1)', tension: 0.4 },
                { label: 'Здивування', data: chartData.emotions?.surprise || [], borderColor: 'rgb(236, 72, 153)', backgroundColor: 'rgba(236,72,153,0.1)', tension: 0.4 },
                { label: 'Нейтральна', data: chartData.emotions?.neutral || [], borderColor: 'rgb(156, 163, 175)', backgroundColor: 'rgba(156,163,175,0.1)', tension: 0.4 }
            ]
        },
        options: chartOptions()
    });

    const errorsChart = new Chart(document.getElementById('errorsChart'), {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [{ label: 'Помилки', data: chartData.errors, backgroundColor: 'rgba(239,68,68,0.5)', borderColor: 'rgb(239,68,68)', borderWidth: 1 }]
        },
        options: chartOptions()
    });

    const duplicatesChart = new Chart(document.getElementById('duplicatesChart'), {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [{ label: 'Дублікати', data: chartData.duplicates || [], backgroundColor: 'rgba(251,191,36,0.5)', borderColor: 'rgb(251,191,36)', borderWidth: 1 }]
        },
        options: chartOptions()
    });

    const consoleCommandsChart = new Chart(document.getElementById('consoleCommandsChart'), {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [{ label: 'Виконано команд', data: chartData.consoleCommands || [], backgroundColor: 'rgba(59,130,246,0.5)', borderColor: 'rgb(59,130,246)', borderWidth: 1 }]
        },
        options: chartOptions()
    });

    // --- Chart helper functions ---

    function chartOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels: { color: '#9ca3af' } } },
            scales: {
                y: { beginAtZero: true, ticks: { color: '#9ca3af' }, grid: { color: '#374151' } },
                x: { ticks: { color: '#9ca3af' }, grid: { color: '#374151' } }
            }
        };
    }

    function updateCharts(data) {
        parsingChart.data.labels = data.labels;
        parsingChart.data.datasets[0].data = data.nodesParsed;
        parsingChart.update();

        sentimentChart.data.labels = data.labels;
        sentimentChart.data.datasets[0].data = data.sentimentPositive;
        sentimentChart.data.datasets[1].data = data.sentimentNegative;
        sentimentChart.update();

        emotionsChart.data.labels = data.labels;
        emotionsChart.data.datasets.forEach((ds, i) => {
            const keys = ['anger', 'joy', 'fear', 'sadness', 'disgust', 'surprise', 'neutral'];
            ds.data = data.emotions?.[keys[i]] || [];
        });
        emotionsChart.update();

        errorsChart.data.labels = data.labels;
        errorsChart.data.datasets[0].data = data.errors;
        errorsChart.update();

        duplicatesChart.data.labels = data.labels;
        duplicatesChart.data.datasets[0].data = data.duplicates || [];
        duplicatesChart.update();

        consoleCommandsChart.data.labels = data.labels;
        consoleCommandsChart.data.datasets[0].data = data.consoleCommands || [];
        consoleCommandsChart.update();
    }

    function fetchChartData() {
        let url = `/dashboard/chart-data?group=${currentGroup}`;
        if (currentPeriod.days) url += `&days=${currentPeriod.days}`;
        if (currentPeriod.dateFrom && currentPeriod.dateTo) url += `&date_from=${currentPeriod.dateFrom}&date_to=${currentPeriod.dateTo}`;

        fetch(url)
            .then(res => res.json())
            .then(data => updateCharts(data))
            .catch(error => console.error('Error:', error));
    }

    // --- Filter button handlers ---

    document.querySelectorAll('.chart-period-btn').forEach(button => {
        button.addEventListener('click', function () {
            const days = this.dataset.days;
            currentPeriod = { days, dateFrom: null, dateTo: null };
            document.querySelectorAll('.chart-period-btn').forEach(btn => btn.classList.remove('ring-2', 'ring-indigo-300'));
            this.classList.add('ring-2', 'ring-indigo-300');
            fetchChartData();
        });
    });

    const applyCustomPeriodBtn = document.getElementById('applyCustomPeriod');
    if (applyCustomPeriodBtn) {
        applyCustomPeriodBtn.addEventListener('click', function () {
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            if (!dateFrom || !dateTo) return alert('Please select both dates');
            if (dateFrom > dateTo) return alert('Date "From" cannot be later than date "To"');

            currentPeriod = { days: null, dateFrom, dateTo };
            document.querySelectorAll('.chart-period-btn').forEach(btn => btn.classList.remove('ring-2', 'ring-indigo-300'));
            fetchChartData();
        });
    }

    document.querySelectorAll('.chart-group-btn').forEach(button => {
        button.addEventListener('click', function () {
            currentGroup = this.dataset.group;
            document.querySelectorAll('.chart-group-btn').forEach(btn => btn.classList.remove('ring-2', 'ring-indigo-400'));
            this.classList.add('ring-2', 'ring-indigo-400');
            fetchChartData();
        });
    });

    // Auto-update charts every 30 seconds
    setInterval(fetchChartData, 30000);
}

// --- SERVICE MONITORING ---

async function checkServicesStatus() {
    try {
        const response = await fetch('/services/status');
        const data = await response.json();

        updateServiceStatus('puppeteer', data.puppeteer);
        updateServiceStatus('python', data.python);

    } catch (error) {
        console.error('Error checking services:', error);
    }
}

function updateServiceStatus(service, status) {
    const badge = document.getElementById(`${service}-status-badge`);
    const message = document.getElementById(`${service}-message`);
    const responseTime = document.getElementById(`${service}-response-time`);

    if (!badge) return; 

    // Reset classes
    badge.className = 'px-3 py-1 rounded-full text-sm font-semibold';

    if (status.status === 'online') {
        badge.classList.add('bg-green-100', 'text-green-700');
        badge.textContent = '✅ Online';
    } else if (status.status === 'error') {
        badge.classList.add('bg-yellow-100', 'text-yellow-700');
        badge.textContent = '⚠️ Error';
    } else {
        badge.classList.add('bg-red-100', 'text-red-700');
        badge.textContent = '❌ Offline';
    }

    if (message) message.textContent = status.message || '';
    if (responseTime && status.response_time) {
        responseTime.textContent = `${status.response_time} ms`;
    }
}

// Export function if you want to check status manually from console
window.checkServicesStatus = checkServicesStatus;