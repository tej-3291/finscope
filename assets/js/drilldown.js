// assets/js/drilldown.js
document.addEventListener('DOMContentLoaded', () => {
    const neonColors = ['#f472b6', '#c084fc', '#818cf8', '#38bdf8', '#34d399', '#facc15', '#fb923c'];
    Chart.defaults.color = '#94a3b8';
    Chart.defaults.font.family = "'Inter', sans-serif";

    const fetchSubData = async () => {
        try {
            // Variables injected via PHP in categories/index.php
            const focus = window.finFocusCategory || 'Food';
            const month = window.finFocusMonth || '';
            const res = await fetch(`../api/categories_data.php?focus=${encodeURIComponent(focus)}&month=${month}`);
            if(!res.ok) return;
            const data = await res.json();
            renderChart(data);
        } catch(e) { console.error("Drilldown fetch error", e); }
    };

    const renderChart = (data) => {
        const ctx = document.getElementById('drilldownChart');
        const noDataEl = document.getElementById('noSubData');
        if(!ctx) return;

        if(!data || !data.data || data.data.length === 0) {
            ctx.classList.add('d-none');
            if(noDataEl) noDataEl.classList.remove('d-none');
            return;
        }
        
        ctx.classList.remove('d-none');
        if(noDataEl) noDataEl.classList.add('d-none');

        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.data,
                    backgroundColor: neonColors,
                    borderWidth: 2,
                    borderColor: 'var(--bg-primary-dark)',
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    animateScale: true,
                    animateRotate: true,
                    duration: 1200,
                    easing: 'easeOutBounce'
                },
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { usePointStyle: true, color: '#f8fafc', padding: 20, font: {size: 13} }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        bodyFont: { size: 15, weight: 'bold' },
                        padding: 14,
                        cornerRadius: 12,
                        callbacks: {
                            label: (context) => ` ${context.label}: ₹${context.raw.toLocaleString('en-IN')}`
                        }
                    }
                }
            }
        });
    };

    fetchSubData();
});
