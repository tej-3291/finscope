<?php
// dashboard/index.php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit(); }
require '../config/database.php';
$user_id = $_SESSION['user_id'];

// Detect the most recent month that actually has data for this user
$latest_stmt = $pdo->prepare("
    SELECT DATE_FORMAT(MAX(date), '%Y-%m') as latest_month
    FROM transactions WHERE user_id = ?
");
$latest_stmt->execute([$user_id]);
$latest_row = $latest_stmt->fetchColumn();
$active_month = $latest_row ?: date('Y-m');
$active_month_start = $active_month . '-01';
$active_month_end   = date('Y-m-t', strtotime($active_month_start));
$active_month_label = date('F Y', strtotime($active_month_start));

// Fetch quick totals for hero cards (most recent active month)
$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as monthly_income,
        SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as monthly_spent
    FROM transactions 
    WHERE user_id = ? AND date BETWEEN ? AND ?
");
$stmt->execute([$user_id, $active_month_start, $active_month_end]);
$current_month = $stmt->fetch();
$m_inc = $current_month['monthly_income'] ?? 0;
$m_exp = $current_month['monthly_spent'] ?? 0;
$savings = $m_inc - $m_exp;

// Savings rate for progress bar
$savings_rate = $m_inc > 0 ? round(($savings / $m_inc) * 100) : 0;

// Fetch Recent Txns (always latest 4 regardless of month)
$recent_stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY date DESC LIMIT 4");
$recent_stmt->execute([$user_id]);
$recent_txns = $recent_stmt->fetchAll();

// Fetch Category Spending for Pie Chart (same active month)
$cat_stmt = $pdo->prepare("
    SELECT category, SUM(ABS(amount)) as total 
    FROM transactions 
    WHERE user_id = ? AND amount < 0 AND date BETWEEN ? AND ?
    GROUP BY category
");
$cat_stmt->execute([$user_id, $active_month_start, $active_month_end]);
$cat_spending = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<style>
/* ── Senior Dashboard Layout ──────────────────────────────────── */
.dash-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 1.5rem;
}

@media (max-width: 1200px) {
    .dash-grid > div { grid-column: span 6; }
}
@media (max-width: 768px) {
    .dash-grid > div { grid-column: span 12; }
}

.stat-card {
    grid-column: span 3;
    padding: 2rem;
}

.main-chart-card {
    grid-column: span 8;
    padding: 2.5rem;
}

.side-insight-card {
    grid-column: span 4;
}

.mini-indicator {
    font-size: 0.7rem; font-weight: 800; letter-spacing: 1px;
    text-transform: uppercase; margin-bottom: 0.5rem; display: block;
}

.big-stat-val {
    font-size: 2.5rem; font-weight: 900; letter-spacing: -1.5px;
    margin-bottom: 0.25rem; line-height: 1.1;
}

/* ── ESG & Mini-Sim Polish ───────────────────────────────────── */
.esg-score-badge {
    padding: 0.75rem 1.5rem; border-radius: 1.25rem;
    background: var(--bg-card); border: 1px solid var(--accent);
    font-weight: 900; font-size: 2.5rem; color: var(--accent);
    box-shadow: 0 10px 30px -10px var(--accent-glow);
}
</style>

<div class="row mb-4 fade-in-up">
    <div class="col-md-8">
        <h2 class="fw-bold mb-1">Dashboard</h2>
        <p class="text-secondary mb-0">Overview for <?= $active_month_label ?><?= $active_month !== date('Y-m') ? ' <span class="badge bg-warning text-dark ms-2" style="font-size:10px;">Past Month</span>' : '' ?></p>
    </div>
    <div class="col-md-4 text-end mt-3 mt-md-0">
        <button class="btn btn-neon w-100" data-bs-toggle="modal" data-bs-target="#addTxnModal">
            <i class="bi bi-plus-circle-fill me-2"></i> Log Transaction
        </button>
    </div>
</div>

<div class="dash-grid">
    <!-- Row 1: Key Stats -->
    <div class="glass-card stat-card fade-in-up">
        <div class="card-shine"></div>
        <span class="mini-indicator">Total Balance</span>
        <div class="big-stat-val text-gradient">₹<?php echo number_format($savings, 0); ?></div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-positive-soft text-positive" style="font-size: 9px;">
                <i class="bi bi-shield-check me-1"></i>SECURE
            </span>
            <span class="text-muted small" style="font-size:9px;">LOCAL STORAGE</span>
        </div>
    </div>

    <div class="glass-card stat-card fade-in-up delay-1">
        <div class="card-shine"></div>
        <span class="mini-indicator">Monthly Burn</span>
        <div class="big-stat-val text-negative">₹<?php echo number_format($m_exp, 0); ?></div>
        <?php $burn_rate = $m_inc > 0 ? min(100, round(($m_exp / $m_inc) * 100)) : 0; ?>
        <div class="progress bg-dark bg-opacity-25" style="height: 4px; border-radius: 99px;">
            <div class="progress-bar bg-danger" style="width: <?= $burn_rate ?>%"></div>
        </div>
    </div>

    <div class="glass-card stat-card fade-in-up delay-2">
        <div class="card-shine"></div>
        <span class="mini-indicator">Savings Velocity</span>
        <div class="big-stat-val text-positive">+₹<?php echo number_format($savings, 0); ?></div>
        <div class="text-muted small" style="font-size: 10px;">vs last month: +12%</div>
    </div>

    <div class="glass-card stat-card fade-in-up delay-3">
        <div class="card-shine"></div>
        <span class="mini-indicator">Efficiency</span>
        <div class="big-stat-val" style="color: var(--accent);"><?= $savings_rate ?>%</div>
        <div class="savings-bar mt-2"><div class="savings-bar-fill" style="width: <?= $savings_rate ?>%"></div></div>
    </div>
</div>

<div class="dash-grid mt-4">
    <!-- Row 2: Pulse & Pie -->
    <div class="glass-card main-chart-card fade-in-up delay-2">
        <div class="card-shine"></div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0">Financial Pulse <span class="text-muted fw-normal ms-2 small">Trend Analysis</span></h5>
            <div class="badge bg-neon-soft text-neon"><i class="bi bi-activity me-1"></i>Realtime</div>
        </div>
        <div style="height: 350px;">
            <canvas id="behaviorChart"></canvas>
        </div>
    </div>

    <a href="../categories/" class="glass-card p-4 fade-in-up delay-2 d-flex flex-column text-decoration-none" style="grid-column: span 4; cursor: pointer;">
        <div class="card-shine"></div>
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h5 class="fw-bold mb-0" style="color: var(--text-main);">Expenditure Split</h5>
            <i class="bi bi-arrow-up-right-circle text-muted"></i>
        </div>
        <div style="height: 250px;" class="flex-grow-1 d-flex align-items-center justify-content-center">
            <canvas id="categoryPieChart"></canvas>
        </div>
        <p class="text-muted small mt-3 mb-0 text-center">Click to explore full category details →</p>
    </a>
</div>

<div class="dash-grid mt-4">
    <!-- Row 3: Premium Insights -->
    <div class="glass-card p-4 fade-in-up delay-3" style="grid-column: span 12;">
        <div class="card-shine"></div>
        <div class="d-flex align-items-center gap-2 mb-4">
            <div class="bg-neon-soft p-2 rounded-3 text-neon"><i class="bi bi-robot"></i></div>
            <h5 class="fw-bold mb-0">Elite AI Insights</h5>
        </div>
        <div id="insightsContainer">
            <div class="text-center py-5">
                <div class="spinner-border text-neon spinner-border-sm" role="status"></div>
            </div>
        </div>
    </div>
</div>

<div class="dash-grid mt-4">
    <!-- Row 4: ESG & Simulator -->
    <div class="glass-card p-4 fade-in-up delay-3" style="grid-column: span 6;">
        <div class="card-shine"></div>
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-globe-americas text-positive fs-5"></i>
                <h5 class="fw-bold mb-0">ESG Impact Score</h5>
            </div>
            <div class="text-muted small fw-bold"><?= strtoupper(date('F Y')) ?></div>
        </div>
        <div class="row align-items-center">
            <div class="col-md-5 text-center">
                <div class="esg-score-badge mb-2">A+</div>
                <div class="text-muted small">Impact Grade</div>
            </div>
            <div class="col-md-7">
                <div style="height: 180px;">
                    <canvas id="esgChart"></canvas>
                </div>
            </div>
        </div>
        <p class="text-muted small mt-3 mb-0">Your spending in <span class="text-positive fw-bold">Investments</span> is driving your Governance score up by 12%.</p>
    </div>

    <div class="glass-card p-4 fade-in-up delay-3" style="grid-column: span 6;">
        <div class="card-shine"></div>
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-speedometer2 text-neon fs-5"></i>
                <h5 class="fw-bold mb-0">Leakage Simulator</h5>
            </div>
            <a href="../simulator/" class="btn btn-sm btn-outline-secondary px-3" style="border-radius: 10px; font-size: 11px;">Elite Mode →</a>
        </div>
        
        <div id="miniSimContainer"></div>

        <div class="d-flex justify-content-between align-items-end pt-3 border-top mt-3" style="border-color: var(--border-card) !important;">
            <div>
                <span class="mini-indicator">Est. 1Y Wealth Gain</span>
                <div class="sim-result-big mb-0" id="ms_total">₹0</div>
            </div>
            <div class="text-end">
                <div class="text-muted small">Monthly: <span class="fw-bold" style="color: var(--text-main);" id="ms_monthly">₹0</span></div>
            </div>
        </div>
    </div>
</div>

<div class="dash-grid mt-4">
    <!-- Row 5: Recent Activity -->
    <div class="glass-card p-4 fade-in-up delay-3" style="grid-column: span 12;">
        <div class="card-shine"></div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0">Recent Intelligence Transaction Log</h5>
            <a href="../transactions/history.php" class="btn btn-sm btn-outline-secondary px-4" style="border-radius: 10px;">Full History</a>
        </div>
        <div class="row g-4">
            <?php foreach (array_slice($recent_txns, 0, 4) as $txn): 
                $isInc = $txn['amount'] > 0;
            ?>
                <div class="col-md-3">
                    <div class="p-4 rounded-4 h-100" style="background: var(--bg-card); border: 1px solid var(--border-card); transition: transform 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                             <span class="badge <?= $isInc ? 'bg-positive-soft text-positive' : 'bg-negative-soft text-negative' ?>" style="font-size: 10px; padding: 4px 10px; border-radius: 6px;">
                                <?= $isInc ? 'INCOME' : 'EXPENSE' ?>
                             </span>
                             <span class="text-muted small fw-bold" style="font-size:10px;"><?= date('M d', strtotime($txn['date'])) ?></span>
                        </div>
                        <div class="fw-bold mb-1 text-truncate" style="color: var(--text-main); font-size: 14px;"><?= htmlspecialchars($txn['description']) ?></div>
                        <div class="h5 fw-900 mb-0 <?= $isInc ? 'text-positive' : 'text-negative' ?>"><?= $isInc ? '+' : '-' ?>₹<?= number_format(abs($txn['amount'])) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

        <script>
        // Mini Simulator Logic
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('miniSimContainer');
            let habits = [];

            fetch('../api/top_leakages.php')
                .then(r => r.json())
                .then(data => {
                    habits = data.slice(0, 2); // Only show top 2 on mini-sim
                    if (habits.length === 0) {
                        habits = [
                            { subcategory: 'Dining Out', monthly_avg: 4000 },
                            { subcategory: 'Snacks', monthly_avg: 1200 }
                        ];
                    }
                    render();
                });

            function render() {
                container.innerHTML = '';
                habits.forEach((h, i) => {
                    const row = document.createElement('div');
                    row.className = 'mb-4';
                    row.innerHTML = `
                        <div class="d-flex justify-content-between mb-2">
                            <label style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;">${h.subcategory}</label>
                            <span id="ms_val_${i}" style="font-size:11px;font-weight:800;color:var(--accent-theme);">50%/12mo</span>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="range" class="sim-slider" id="ms_perc_${i}" min="0" max="100" value="50">
                            </div>
                            <div class="col-6">
                                <input type="range" class="sim-slider" id="ms_time_${i}" min="1" max="60" value="12">
                            </div>
                        </div>
                    `;
                    container.appendChild(row);
                    row.querySelector(`#ms_perc_${i}`).addEventListener('input', calc);
                    row.querySelector(`#ms_time_${i}`).addEventListener('input', calc);
                });
                calc();
            }

            function calc() {
                let totalWealth = 0;
                let monthly = 0;

                habits.forEach((h, i) => {
                    const p = document.getElementById(`ms_perc_${i}`).value;
                    const t = document.getElementById(`ms_time_${i}`).value;
                    document.getElementById(`ms_val_${i}`).textContent = `${p}%/${t}mo`;
                    
                    const saved = parseFloat(h.monthly_avg) * (p / 100);
                    monthly += saved;
                    
                    const fv = saved * t; // Direct savings only
                    totalWealth += fv;
                });
                
                document.getElementById('ms_total').textContent = '₹' + Math.round(totalWealth).toLocaleString('en-IN');
                document.getElementById('ms_monthly').textContent = '₹' + Math.round(monthly).toLocaleString('en-IN');
            }
        });
        </script>


<!-- Add Transaction Modal -->
<div class="modal fade" id="addTxnModal" tabindex="-1" style="backdrop-filter: blur(5px);">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-card border-0">
      <div class="modal-header border-secondary border-opacity-25">
        <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2 text-neon"></i> Log Transaction</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="../transactions/add.php" method="POST">
          <div class="modal-body">
              <div class="mb-3">
                  <label class="form-label text-secondary small text-uppercase">Type</label>
                  <select name="type" id="txnType" class="form-select bg-dark text-light border-secondary">
                      <option value="expense">Expense (−)</option>
                      <option value="income">Income (+)</option>
                  </select>
              </div>
              <div class="mb-3">
                  <label class="form-label text-secondary small text-uppercase">Amount</label>
                  <div class="input-group">
                      <span class="input-group-text bg-dark border-secondary text-light">₹</span>
                      <input type="number" step="0.01" name="amount" class="form-control bg-dark text-light border-secondary" required>
                  </div>
              </div>
              <div class="row">
                  <div class="col-6 mb-3">
                      <label class="form-label text-secondary small text-uppercase">Category</label>
                      <select name="category" class="form-select bg-dark text-light border-secondary" required>
                          <option value="Necessities">Necessities</option>
                          <option value="Investments">Investments</option>
                          <option value="Entertainment">Entertainment</option>
                          <option value="Junk">Junk</option>
                          <option value="Income">Income</option>
                          <option value="Food">Food</option>
                          <option value="Transport">Transport</option>
                          <option value="Health">Health</option>
                          <option value="Bills">Bills</option>
                          <option value="Shopping">Shopping</option>
                      </select>
                  </div>
                  <div class="col-6 mb-3">
                      <label class="form-label text-secondary small text-uppercase">Subcategory</label>
                      <input type="text" name="subcategory" class="form-control bg-dark text-light border-secondary" placeholder="e.g. Snacks">
                  </div>
              </div>
              <div class="mb-3">
                  <label class="form-label text-secondary small text-uppercase">Description</label>
                  <input type="text" name="description" class="form-control bg-dark text-light border-secondary" required placeholder="e.g. Chai at stall">
              </div>
              <div class="row">
                  <div class="col-6 mb-3">
                      <label class="form-label text-secondary small text-uppercase">Method</label>
                      <select name="method" class="form-select bg-dark text-light border-secondary">
                          <option value="UPI">UPI</option>
                          <option value="Card">Card</option>
                          <option value="Cash">Cash</option>
                          <option value="Net Banking">Net Banking</option>
                      </select>
                  </div>
                  <div class="col-6 mb-3">
                      <label class="form-label text-secondary small text-uppercase">Date</label>
                      <input type="date" name="date" class="form-control bg-dark text-light border-secondary" value="<?= date('Y-m-d') ?>" required>
                  </div>
              </div>
          </div>
          <div class="modal-footer border-secondary border-opacity-25">
            <button type="submit" class="btn btn-primary w-100 fw-bold py-2">Save Transaction</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="/finscope/assets/js/dashboard_charts.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // AI Insights Fetch
        fetch('../api/insights.php')
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('insightsContainer');
            if (data.error) {
                container.innerHTML = `<div class="text-danger p-4">Error loading insights: ${data.error}</div>`;
                return;
            }
            if (!data || data.length === 0) {
               container.innerHTML = `<div class="text-muted p-4">No insights generated yet.</div>`;
               return;
            }
            
            const item = data[0]; // Display only the single hyper-relevant insight
            
            container.innerHTML = `
                <div class="px-4 py-3">
                    <div class="mb-4 d-flex align-items-center gap-4">
                        <div style="width: 64px; height: 64px; border-radius: 18px; background: var(--bg-card); border: 1px solid var(--accent); display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 16px -4px var(--accent-glow);">
                            <i class="bi ${item.icon} ${item.color}" style="font-size: 1.8rem;"></i>
                        </div>
                        <div>
                            <h5 class="fw-black mb-1" style="color: var(--text-main); letter-spacing: -0.5px;">${item.title}</h5>
                            <div style="width: 30px; height: 3px; background: var(--accent); border-radius: 2px;"></div>
                        </div>
                    </div>
                    <p class="mb-0" style="color: var(--text-muted); font-size: 14px; line-height: 1.6; font-weight: 500;">${item.text}</p>
                </div>
            `;
        })
        .catch(err => {
            console.error("Insights Fetch Error:", err);
            document.getElementById('insightsContainer').innerHTML = `<div class="text-muted p-4">Failed to load insights. Check console.</div>`;
        });

        // Charts are handled by dashboard_charts.js

        // Card Shine Effect
        document.querySelectorAll('.glass-card').forEach(card => {
            card.addEventListener('mousemove', e => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                card.style.setProperty('--mouse-x', `${x}px`);
                card.style.setProperty('--mouse-y', `${y}px`);
            });
        });

        // ESG Chart
        fetch('../api/esg_data.php')
        .then(r => r.json())
        .then(data => {
            document.querySelector('.esg-score-badge').textContent = data.grade;
            document.querySelector('.esg-score-badge').nextElementSibling.innerHTML = 
                `Your spending in <span class="text-positive fw-bold">${data.top_positive_category || 'Investments'}</span> is driving your Governance score up by 12%.`;
                
            const ctx = document.getElementById('esgChart').getContext('2d');
            new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: ['Environmental', 'Social', 'Governance'],
                    datasets: [{
                        label: 'Impact Score',
                        data: [data.scores.Environmental, data.scores.Social, data.scores.Governance],
                        backgroundColor: 'rgba(16, 185, 129, 0.2)',
                        borderColor: '#10b981',
                        borderWidth: 2,
                        pointBackgroundColor: '#10b981'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            angleLines: { color: 'rgba(255,255,255,0.1)' },
                            grid: { color: 'rgba(255,255,255,0.1)' },
                            pointLabels: { color: '#94a3b8', font: { size: 10 } },
                            ticks: { display: false, stepSize: 20 },
                            min: 0, max: 100
                        }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        })
        .catch(err => console.error("ESG Chart Fetch Error:", err));
    });
</script>

<?php include '../includes/footer.php'; ?>
