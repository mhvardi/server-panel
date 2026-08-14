import './bootstrap';
import Chart from 'chart.js/auto';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const chartEl = document.getElementById('resourceChart');
    if (!chartEl) {
        return;
    }

    const initialMetrics = JSON.parse(chartEl.dataset.metrics || '[]');
    const updateUrl = chartEl.dataset.updateUrl;

    const labels = initialMetrics.map(m => new Date(m.timestamp * 1000).toLocaleTimeString());
    const cpuData = initialMetrics.map(m => m.cpu);
    const memData = initialMetrics.map(m => m.mem);
    const diskData = initialMetrics.map(m => m.disk);

    const ctx = chartEl.getContext('2d');
    const resourceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'CPU (%)',
                    data: cpuData,
                    borderColor: 'rgba(78, 115, 223, 1)',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    tension: 0.3,
                    fill: true,
                },
                {
                    label: 'Memory (%)',
                    data: memData,
                    borderColor: 'rgba(28, 200, 138, 1)',
                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
                    tension: 0.3,
                    fill: true,
                },
                {
                    label: 'Disk (%)',
                    data: diskData,
                    borderColor: 'rgba(54, 185, 204, 1)',
                    backgroundColor: 'rgba(54, 185, 204, 0.1)',
                    tension: 0.3,
                    fill: true,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, max: 100 }
            },
            plugins: {
                legend: { display: true }
            }
        }
    });

    const updateStats = async () => {
        try {
            const response = await window.axios.get(updateUrl);
            const data = response.data;

            // Update stat cards
            document.getElementById('cpu_usage_value').innerText = data.cpu_usage;
            document.getElementById('memory_usage_value').innerText = data.memory_usage;
            document.getElementById('disk_usage_value').innerText = data.disk_usage;
            document.getElementById('uptime_value').innerText = data.uptime;

            // Update progress bars
            document.getElementById('cpu_usage_bar').style.width = data.cpu_usage + '%';
            document.getElementById('memory_usage_bar').style.width = data.memory_usage + '%';
            document.getElementById('disk_usage_bar').style.width = data.disk_usage + '%';

            // Update detailed usage text
            const memory_used_gb = document.getElementById('memory_used_gb');
            if(memory_used_gb) memory_used_gb.innerText = data.memory_used_gb ?? 0;
            const memory_total_gb = document.getElementById('memory_total_gb');
            if(memory_total_gb) memory_total_gb.innerText = data.memory_total_gb ?? 0;
            const disk_used_gb = document.getElementById('disk_used_gb');
            if(disk_used_gb) disk_used_gb.innerText = data.disk_used_gb ?? 0;
            const disk_total_gb = document.getElementById('disk_total_gb');
            if(disk_total_gb) disk_total_gb.innerText = data.disk_total_gb ?? 0;


            // Update resource chart
            if (data.metrics_history) {
                const m = data.metrics_history;
                resourceChart.data.labels = m.map(d => new Date(d.timestamp * 1000).toLocaleTimeString());
                resourceChart.data.datasets[0].data = m.map(d => d.cpu);
                resourceChart.data.datasets[1].data = m.map(d => d.mem);
                resourceChart.data.datasets[2].data = m.map(d => d.disk);
                resourceChart.update('none');
            }
        } catch (error) {
            console.error('Failed to update stats:', error);
        }
    };

    if (updateUrl) {
        updateStats();
        setInterval(updateStats, 5000);
    }
});
