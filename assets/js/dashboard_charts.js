// assets/js/dashboard_charts.js
document.addEventListener('DOMContentLoaded', () => {
    // Shared Colors (Deep Neon Palette)
    const neonColors = ['#38bdf8', '#818cf8', '#c084fc', '#f472b6', '#fb7185', '#fb923c', '#facc15'];

    // Common Chart Defaults
    Chart.defaults.color = '#94a3b8'; // text-secondary
    Chart.defaults.font.family = "'Inter', sans-serif";
    
    // Init global chart instances
    let behaviorChart, pieChart;

    const initCharts = async () => {
        try {
            const currentMonth = document.getElementById('pieMonthSelect')?.value;
            const res = await fetch(`../api/dashboard_data.php?month=${currentMonth || ''}`);
            if(!res.ok) return;
            const data = await res.json();
            
            if (data.behavior) renderBehaviorChart(data.behavior);
            if (data.pie) renderPieChart(data.pie);
        } catch(e) { console.error('Error fetching dashboard chart data:', e); }
    };

    const renderBehaviorChart = (data) => {
        const ctx = document.getElementById('behaviorChart');
        if(!ctx) return;
        
        if(behaviorChart) behaviorChart.destroy();
        
        behaviorChart = new Chart(ctx, {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1500,
                    easing: 'easeOutQuart',
                    delay: (context) => context.dataIndex * 100 // Stagger effect
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        stacked: false
                    },
                    y: {
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        beginAtZero: true,
                        ticks: { callback: (value) => '₹' + value }
                    }
                },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleFont: { size: 13 },
                        bodyFont: { size: 14, weight: 'bold' },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: (context) => `${context.dataset.label}: ₹${context.raw.toLocaleString('en-IN')}`
                        }
                    }
                }
            }
        });
    };

    const renderPieChart = (data) => {
        const ctx = document.getElementById('categoryPieChart');
        const noDataEl = document.getElementById('pieNoData');
        if(!ctx) return;
        
        if(pieChart) pieChart.destroy();

        if(!data || !data.data || data.data.length === 0) {
            ctx.classList.add('d-none');
            if(noDataEl) noDataEl.classList.remove('d-none');
            return;
        }

        ctx.classList.remove('d-none');
        if(noDataEl) noDataEl.classList.add('d-none');

        pieChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.data,
                    backgroundColor: data.colors || neonColors,
                    borderWidth: 2,
                    borderColor: 'var(--bg-primary-dark)',
                    hoverOffset: 12
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                animation: { animateScale: true, animateRotate: true, duration: 1500, easing: 'easeOutQuart' },
                plugins: {
                    legend: { display: false }, // Hide ugly default legend to use space
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        bodyFont: { size: 14, weight: 'bold' },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: (context) => ` ${context.label}: ₹${context.raw.toLocaleString('en-IN')}`
                        }
                    }
                },
                onClick: (event, elements) => {
                    if (elements.length > 0) {
                        const idx = elements[0].index;
                        const label = data.labels[idx];
                        const month = document.getElementById('pieMonthSelect')?.value;
                        // Drill down navigation
                        window.location.href = `../categories/?focus=${encodeURIComponent(label)}&month=${month}`;
                    }
                }
            }
        });
    };

    // Listen to month dropdown changes
    const pieSelect = document.getElementById('pieMonthSelect');
    if(pieSelect) {
        pieSelect.addEventListener('change', async (e) => {
            try {
                const res = await fetch(`../api/dashboard_data.php?month=${e.target.value}`);
                if(res.ok) {
                    const data = await res.json();
                    renderPieChart(data.pie);
                }
            } catch(err) {}
        });
    }

    // Run on load
    initCharts();
});
