<?php
// simulator/index.php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit(); }
require '../config/database.php';
include '../includes/header.php';
?>

<style>
    /* ── Dual Slider Layout ───────────────────────────────────────── */
    /* ── Dual Slider Layout ───────────────────────────────────────── */
    .sim-section { display: grid; grid-template-columns: 1fr 380px; gap: 30px; align-items: start; }
    @media (max-width: 992px) { .sim-section { grid-template-columns: 1fr; } }

    .dual-slider-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; }
    @media (max-width: 500px) { .dual-slider-container { grid-template-columns: 1fr; } }
    
    .slider-group { position: relative; }
    .slider-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .slider-label { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
    .slider-val { font-size: 12px; font-weight: 800; color: var(--text-main); }

    /* ── Habit Cards ──────────────────────────────────────────────── */
    .habit-card {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: 1.5rem;
        padding: 24px;
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .habit-card:hover { border-color: rgba(255,255,255,0.25); transform: translateY(-3px); }

    .rank-badge {
        width: 32px; height: 32px; border-radius: 8px;
        background: rgba(255,255,255,0.05);
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 14px; margin-right: 12px;
        border: 1px solid rgba(255,255,255,0.1);
    }

    .habit-name { font-weight: 800; font-size: 18px; margin-bottom: 2px; color: var(--text-main); }
    .habit-cat { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 700; }

    .habit-avg-spend {
        font-size: 12px;
        color: var(--text-main);
        background: var(--bg-card);
        padding: 6px 14px; border-radius: 99px;
        border: 1px solid var(--border-card);
        font-weight: 700;
    }

    .savings-chip {
        display: inline-block; padding: 6px 14px; border-radius: 99px;
        background: rgba(16,185,129,0.1); color: #10b981;
        font-size: 12px; font-weight: 700; border: 1px solid rgba(16,185,129,0.2);
    }

    /* ── Projected Wealth Card ─────────────────────────────────────── */
    .projected-card {
        background: linear-gradient(135deg, rgba(15,23,42,0.8), rgba(30,41,59,0.8));
        backdrop-filter: blur(20px); border-radius: 2rem;
        border: 1px solid rgba(255,255,255,0.1); padding: 30px;
        box-shadow: 0 20px 50px -20px rgba(0,0,0,0.5);
    }

    .big-num { font-size: 3.5rem; font-weight: 900; letter-spacing: -2px; line-height: 1; margin: 15px 0; }
    
    .mini-stat { background: var(--bg-card); padding: 15px; border-radius: 1rem; border: 1px solid var(--border-card); }
    .mini-stat .label { font-size: 10px; text-transform: uppercase; color: var(--text-muted); font-weight: 800; margin-bottom: 5px; }
    .mini-stat .val { font-size: 16px; font-weight: 800; color: var(--text-main); }

    /* Mini indicator label used throughout the page */
    .mini-indicator { font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; display: block; }

    .btn-commit {
        width: 100%; margin-top: 25px; padding: 15px; border-radius: 1rem;
        background: var(--accent); border: none; color: #000;
        font-weight: 700; font-size: 16px; letter-spacing: 0.5px;
        box-shadow: 0 10px 20px -5px var(--accent-glow);
        transition: all 0.3s ease;
    }
    .btn-commit:hover { transform: translateY(-2px); box-shadow: 0 15px 30px -5px var(--accent-glow); }

    /* ── Utilities ────────────────────────────────────────────────── */
    .skeleton { background: rgba(255,255,255,0.05); border-radius: 1.5rem; animation: pulse 1.5s infinite ease-in-out; }
    @keyframes pulse { 0%, 100% { opacity: 0.5; } 50% { opacity: 0.8; } }
    
    .return-select {
        width: 100%; padding: 12px; background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1); border-radius: 12px;
        color: white; font-weight: 600; outline: none; transition: 0.3s;
    }
    .return-select:focus { border-color: var(--accent-theme); background: rgba(255,255,255,0.08); }

    .fin-slider {
        -webkit-appearance: none; width: 100%; height: 6px;
        background: rgba(255,255,255,0.1); border-radius: 99px; outline: none;
    }
    .fin-slider::-webkit-slider-thumb {
        -webkit-appearance: none; width: 22px; height: 22px;
        border-radius: 50%; background: white; cursor: pointer;
        box-shadow: 0 0 15px rgba(0,0,0,0.3), 0 0 0 5px rgba(255,255,255,0.05);
        border: 2px solid var(--accent-theme);
    }
</style>

<div class="row mb-5 fade-in-up">
    <div class="col-md-9">
        <h2 class="fw-black mb-1 text-gradient" style="font-size: 2.8rem; letter-spacing: -2px;">Simulator 3.0</h2>
        <p class="text-muted mb-0 fw-medium">Elite behavioral projection engine • Local-only analysis</p>
    </div>
    <div class="col-md-3 text-end d-lg-block d-none">
        <div class="glass-card px-4 py-2 d-inline-block border-opacity-10">
            <span class="text-muted small fw-bold"><i class="bi bi-shield-lock me-2 text-neon"></i>ENCRYPTED VAULT</span>
        </div>
    </div>
</div>

<div class="sim-section fade-in-up delay-1">
    <!-- ── LEFT: The Habit Lab ────────────────────────────────── -->
    <div>
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h5 class="fw-bold mb-0"><i class="bi bi-fingerprint me-2 text-neon"></i>Behavioral Lab</h5>
            <div id="dataSourceBadge" class="d-none">
                <span class="badge bg-neon-soft text-neon rounded-pill px-3">LIVE SYNC</span>
            </div>
        </div>

        <div id="sliderSkeleton">
            <div class="skeleton mb-4" style="height:180px;"></div>
            <div class="skeleton mb-4" style="height:180px;"></div>
        </div>

        <div id="sliderContainer" class="d-none"></div>

        <div class="glass-card mb-4" style="padding: 2rem;">
            <div class="card-shine"></div>
            <div class="mini-indicator mb-3">Commitment Insight</div>
            <p style="color: var(--text-muted); margin-top: 0; font-size: 0.85rem; margin-bottom: 0;">
                <i class="bi bi-info-circle me-1"></i> These projections are based on your 
                <strong style="color: var(--text-main);">direct expense reduction</strong> over the selected term. 
                No interest or compounding is included.
            </p>
        </div>
    </div>

    <!-- ── RIGHT: Reality Projection ───────────────────────────── -->
    <div style="position: sticky; top: 120px;">
        <div class="glass-card p-0 overflow-hidden mb-4">
            <div class="card-shine"></div>
            <div class="p-4 border-bottom border-white border-opacity-10 pb-2">
                <span class="mini-indicator text-muted">Projected Accumulation</span>
                <h5 class="fw-bold mb-0">Commitment Results</h5>
            </div>
            <div style="height: 220px; padding: 1rem 0;">
                <canvas id="trajectoryChart"></canvas>
            </div>
            <div class="p-4 pt-0">
                <div class="text-center py-4">
                    <span class="mini-indicator text-muted">Estimated Total Savings</span>
                    <div class="big-stat-val text-gradient" id="projTotal" style="font-size: 4rem;">₹0</div>
                    <div id="interestNote" class="text-neon small fw-bold mt-1">Direct Capital Retention</div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="p-3 rounded-4 bg-white bg-opacity-5 border border-white border-opacity-10 text-center">
                            <span class="mini-indicator text-muted">Monthly Save</span>
                            <div class="h5 fw-bold text-positive mb-0" id="projMonthly">₹0</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-4 bg-white bg-opacity-5 border border-white border-opacity-10 text-center">
                            <span class="mini-indicator text-muted">10Y Potential</span>
                            <div class="h5 fw-bold text-neon mb-0" id="proj10y">₹0</div>
                        </div>
                    </div>
                </div>

                <button class="btn-neon w-100 py-3" id="commitBtn">
                    Confirm Lifestyle Shift
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const RANK_COLORS = ['#38bdf8', '#a78bfa', '#fb923c'];
    
    const DEFAULTS = [
        { subcategory: 'Dining & Delivery', monthly_avg: 4500, category: 'Food' },
        { subcategory: 'Impulse Shopping', monthly_avg: 2200, category: 'Shopping' },
        { subcategory: 'Unused Subs',      monthly_avg: 899,  category: 'Bills' }
    ];

    let habits = [];
    let chart = null;

    // Init Trajectory Chart
    const initChart = () => {
        const ctx = document.getElementById('trajectoryChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 200);
        gradient.addColorStop(0, 'rgba(56, 189, 248, 0.2)');
        gradient.addColorStop(1, 'rgba(56, 189, 248, 0)');

        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: Array.from({length: 13}, (_, i) => i + 'm'),
                datasets: [{
                    label: 'Direct Savings',
                    data: [],
                    borderColor: '#38bdf8',
                    borderWidth: 3,
                    fill: true,
                    backgroundColor: gradient,
                    tension: 0.4,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 10 } } },
                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, border: { display: false }, ticks: { display: false } }
                }
            }
        });
    };

    initChart();

    fetch('../api/top_leakages.php')
        .then(r => r.json())
        .then(data => {
            const isLive = Array.isArray(data) && data.length > 0;
            habits = isLive ? data.slice() : DEFAULTS.slice();
            // Pad to at least 3 entries with defaults
            while (habits.length < 3) habits.push(DEFAULTS[habits.length]);
            render(habits, isLive);
        })
        .catch(() => {
            habits = DEFAULTS.slice();
            render(habits, false);
        });

    function render(list, isLive) {
        document.getElementById('sliderSkeleton').classList.add('d-none');
        const container = document.getElementById('sliderContainer');
        container.classList.remove('d-none');
        container.innerHTML = '';

        if (isLive) document.getElementById('dataSourceBadge').classList.remove('d-none');

        list.forEach((h, i) => {
            const card = document.createElement('div');
            card.className = `glass-card p-4 mb-4`;
            card.innerHTML = `
                <div class="card-shine"></div>
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-white bg-opacity-5 rounded-3 p-2 me-3 fw-black" style="color:${RANK_COLORS[i] || '#ffffff'}; font-size: 1.2rem;">#${i+1}</div>
                        <div>
                            <div class="h5 fw-bold mb-0" style="color: var(--text-main);">${h.subcategory}</div>
                            <div class="mini-indicator mb-0">${h.category}</div>
                        </div>
                    </div>
                    <div class="text-end">
                        <span style="font-size:11px; font-weight:700; color:var(--text-main); background:var(--bg-card); padding:6px 14px; border-radius:99px; border:1px solid var(--border-card); display:inline-block;">
                            AVG ₹${Math.round(h.monthly_avg).toLocaleString('en-IN')}/mo
                        </span>
                    </div>
                </div>

                <div class="row g-4 mt-1">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="mini-indicator mb-0">Lifestyle Reduction</span>
                            <span class="fw-bold text-neon" id="perc_val_${i}">50%</span>
                        </div>
                        <input type="range" class="form-range" id="perc_${i}" min="0" max="100" value="50">
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="mini-indicator mb-0">Commitment Term</span>
                            <span class="fw-bold text-neon" id="time_val_${i}">12mo</span>
                        </div>
                        <input type="range" class="form-range" id="time_${i}" min="1" max="60" value="12">
                    </div>
                </div>

                <div class="mt-4 pt-3 d-flex justify-content-between align-items-center" style="border-top: 1px solid var(--border-card);">
                    <span class="mini-indicator mb-0">Projected Savings</span>
                    <span class="text-positive fw-black h6 mb-0" id="habit_save_${i}">₹0</span>
                </div>
            `;

            container.appendChild(card);

            card.querySelector(`#perc_${i}`).addEventListener('input', update);
            card.querySelector(`#time_${i}`).addEventListener('input', update);

            card.addEventListener('mousemove', e => {
                const rect = card.getBoundingClientRect();
                card.style.setProperty('--mouse-x', `${e.clientX - rect.left}px`);
                card.style.setProperty('--mouse-y', `${e.clientY - rect.top}px`);
            });
        });

        update(); // ← Called ONCE here, after all cards are built. No recursive render() call.
    }

    function update() {
        let totalImpact = 0;
        let monthlyImpact = 0;
        let max10y = 0;
        // No rate needed for direct linear savings

        habits.forEach((h, i) => {
            const percEl = document.getElementById(`perc_${i}`);
            const timeEl = document.getElementById(`time_${i}`);
            if (!percEl || !timeEl) return;

            const p = parseInt(percEl.value);
            const t = parseInt(timeEl.value);
            const avg = parseFloat(h.monthly_avg || 0);
            
            document.getElementById(`perc_val_${i}`).textContent = p + '%';
            document.getElementById(`time_val_${i}`).textContent = t + 'mo';

            const saved = avg * (p / 100);
            monthlyImpact += saved;
            
            const fv = saved * t; // Pure linear savings
            totalImpact += fv;

            max10y += saved * 120;

            document.getElementById(`habit_save_${i}`).textContent = `₹${Math.round(fv).toLocaleString('en-IN')}`;
        });

        // Update Numbers
        animate('projTotal', totalImpact, '₹');
        animate('projMonthly', monthlyImpact, '₹');
        animate('proj10y', max10y, '₹');

        // Update Chart
        updateChart(monthlyImpact);
    }

    function updateChart(monthly) {
        const points = [];
        for (let i = 0; i <= 12; i++) {
            points.push(Math.round(monthly * i));
        }
        chart.data.datasets[0].data = points;
        chart.update();
    }

    const animTimers = {};
    function animate(id, target, prefix = '') {
        const el = document.getElementById(id);
        if (!el) return;
        if (animTimers[id]) cancelAnimationFrame(animTimers[id]);
        const start = parseFloat(el.dataset.v || '0');
        const startTs = performance.now();
        const step = ts => {
            const p = Math.min((ts - startTs) / 400, 1);
            const v = start + (target - start) * (1 - Math.pow(1 - p, 4));
            el.textContent = prefix + Math.floor(v).toLocaleString('en-IN');
            if (p < 1) animTimers[id] = requestAnimationFrame(step);
            else el.dataset.v = target;
        };
        animTimers[id] = requestAnimationFrame(step);
    }

    document.getElementById('commitBtn').addEventListener('click', () => {
        alert("Elite Commitment Registered. Your lifestyle trajectory has been shifted.");
    });

    // Global listeners for shine
    document.querySelectorAll('.glass-card').forEach(c => {
        c.addEventListener('mousemove', e => {
            const r = c.getBoundingClientRect();
            c.style.setProperty('--mouse-x', `${e.clientX - r.left}px`);
            c.style.setProperty('--mouse-y', `${e.clientY - r.top}px`);
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
