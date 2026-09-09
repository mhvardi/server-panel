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

    const setText = (id, val) => {
        const el = document.getElementById(id);
        if (el && val !== undefined && val !== null) {
            el.innerText = val;
        }
    };

    const setGauge = (id, percent, normalColorClass) => {
        const el = document.getElementById(id);
        if (!el || percent === undefined || percent === null) return;
        el.style.width = Math.min(100, Math.max(0, percent)) + '%';
        
        // Dynamic threshold colors
        el.classList.remove('bg-blue-500', 'bg-emerald-500', 'bg-indigo-500', 'bg-amber-500', 'bg-rose-500');
        if (percent >= 85) {
            el.classList.add('bg-rose-500');
        } else if (percent >= 75) {
            el.classList.add('bg-amber-500');
        } else {
            el.classList.add(normalColorClass);
        }
    };

    const updateStats = async () => {
        const response = await window.axios.get(updateUrl);
        const data = response.data;

        // Update stat cards
        setText('cpu_usage_value', data.cpu_usage);
        setText('memory_usage_value', data.memory_usage);
        setText('disk_usage_value', data.disk_usage);
        setText('uptime_value', data.uptime);
        setText('uptime_summary', data.uptime);

        // Update progress bars with dynamic thresholds
        setGauge('cpu_usage_bar', data.cpu_usage, 'bg-blue-500');
        setGauge('memory_usage_bar', data.memory_usage, 'bg-emerald-500');
        setGauge('disk_usage_bar', data.disk_usage, 'bg-indigo-500');

        // Update detailed usage text
        setText('memory_used_gb', data.memory_used_gb ?? 0);
        setText('memory_total_gb', data.memory_total_gb ?? 0);
        setText('disk_used_gb', data.disk_used_gb ?? 0);
        setText('disk_total_gb', data.disk_total_gb ?? 0);

        if (Array.isArray(data.load_avg) && data.load_avg.length >= 3) {
            setText('load_avg_value', data.load_avg.join(' / '));
        }

        // Update resource chart
        if (data.metrics_history && resourceChart) {
            const m = data.metrics_history;
            resourceChart.data.labels = m.map(d => new Date(d.timestamp * 1000).toLocaleTimeString());
            resourceChart.data.datasets[0].data = m.map(d => d.cpu);
            resourceChart.data.datasets[1].data = m.map(d => d.mem);
            resourceChart.data.datasets[2].data = m.map(d => d.disk);
            resourceChart.update('none');
        }
    };

    let pollTimeout = null;
    let isFetching = false;

    const scheduleNextPoll = (delay = 5000) => {
        if (pollTimeout) {
            clearTimeout(pollTimeout);
            pollTimeout = null;
        }
        if (!document.hidden) {
            pollTimeout = setTimeout(runPoll, delay);
        }
    };

    const runPoll = async () => {
        if (isFetching || document.hidden) {
            return;
        }
        isFetching = true;
        const refreshBtn = document.getElementById('manual_refresh_btn');
        if (refreshBtn) refreshBtn.classList.add('animate-spin');

        try {
            await updateStats();
            scheduleNextPoll(5000);
        } catch (error) {
            console.error('Failed to update stats:', error);
            scheduleNextPoll(15000);
        } finally {
            isFetching = false;
            if (refreshBtn) {
                setTimeout(() => refreshBtn.classList.remove('animate-spin'), 600);
            }
        }
    };

    // Expose for manual click trigger
    window.refreshServerStats = runPoll;

    if (updateUrl) {
        runPoll();

        // Pause polling when tab is hidden, resume immediately when tab is active
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                runPoll();
            } else if (pollTimeout) {
                clearTimeout(pollTimeout);
                pollTimeout = null;
            }
        });
    }
});
