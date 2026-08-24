<?php
// categories/index.php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit(); }
require '../config/database.php';

$user_id = $_SESSION['user_id'];
$monthStr = $_GET['month'] ?? date('Y-m');
$monthStart = $monthStr . '-01';
$monthEnd = date('Y-m-t', strtotime($monthStart));

// Fetch ALL categories with their totals and sub-breakdown for this month
$cat_stmt = $pdo->prepare("
    SELECT category, SUM(ABS(amount)) as total
    FROM transactions
    WHERE user_id = ? AND amount < 0 AND date BETWEEN ? AND ?
    GROUP BY category
    ORDER BY total DESC
");
$cat_stmt->execute([$user_id, $monthStart, $monthEnd]);
$categories = $cat_stmt->fetchAll();

// For each category, fetch subcategory breakdown
$cat_data = [];
foreach ($categories as $cat) {
    $sub_stmt = $pdo->prepare("
        SELECT subcategory, SUM(ABS(amount)) as total, COUNT(*) as count
        FROM transactions
        WHERE user_id = ? AND category = ? AND amount < 0 AND date BETWEEN ? AND ?
        GROUP BY subcategory ORDER BY total DESC
    ");
    $sub_stmt->execute([$user_id, $cat['category'], $monthStart, $monthEnd]);
    $subs = $sub_stmt->fetchAll();
    
    // Recent transactions for this category
    $txn_stmt = $pdo->prepare("
        SELECT description, subcategory, amount, date, payment_method
        FROM transactions
        WHERE user_id = ? AND category = ? AND amount < 0 AND date BETWEEN ? AND ?
        ORDER BY date DESC LIMIT 10
    ");
    $txn_stmt->execute([$user_id, $cat['category'], $monthStart, $monthEnd]);
    $txns = $txn_stmt->fetchAll();

    $cat_data[] = [
        'name'  => $cat['category'],
        'total' => $cat['total'],
        'subs'  => $subs,
        'txns'  => $txns,
    ];
}

// Total spending
$grand_total = array_sum(array_column($categories, 'total'));

// Chart colors palette
$palette = ['#38bdf8','#10b981','#a78bfa','#f59e0b','#ef4444','#ec4899','#06b6d4','#84cc16'];

include '../includes/header.php';
?>

<style>
.cat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; }
.cat-card {
    background: rgba(14,26,48,0.7);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 18px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1);
    position: relative; overflow: hidden;
}
.cat-card::before {
    content: ''; position: absolute; inset: 0;
    border-radius: 18px; opacity: 0;
    transition: opacity 0.3s;
    pointer-events: none;
}
.cat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.4); }
.cat-card:hover::before { opacity: 1; }
.cat-card:hover .pie-wrapper canvas { filter: drop-shadow(0 0 8px currentColor); }

.pie-wrapper { position: relative; width: 120px; height: 120px; margin: 0 auto 12px; }
.pie-center-label {
    position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
    text-align: center; pointer-events: none;
}
.cat-name { font-weight: 700; font-size: 15px; text-align: center; margin-bottom: 4px; }
.cat-amount { text-align: center; font-size: 20px; font-weight: 800; letter-spacing: -0.5px; }
.cat-share { text-align: center; font-size: 12px; color: var(--text-muted); margin-top: 2px; }
.click-hint { 
    text-align: center; font-size: 11px; color: var(--text-muted);
    margin-top: 10px; transition: color 0.2s;
}
.cat-card:hover .click-hint { color: var(--accent-neon); }

/* Detail Modal */
#catDetailModal .modal-content {
    background: var(--bg-card);
    backdrop-filter: blur(24px);
    border: 1px solid var(--border-card);
    border-radius: 20px;
    color: var(--text-main);
}
.detail-chart-wrap { position: relative; width: 180px; height: 180px; margin: 0 auto; }
.sub-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05);
}
.sub-item:last-child { border-bottom: none; }
.sub-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.sub-bar-wrap { flex: 1; background: rgba(255,255,255,0.06); border-radius: 999px; height: 4px; overflow: hidden; }
.sub-bar-fill { height: 100%; border-radius: 999px; }
.txn-mini { font-size: 12px; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.04); display:flex; justify-content: space-between; }
.txn-mini:last-child { border-bottom: none; }
</style>

<div class="row mb-5 fade-in-up">
    <div class="col-md-8">
        <h2 class="fw-bold mb-1">Category Deep Dive</h2>
        <p class="text-secondary mb-0">
            <?= date('F Y', strtotime($monthStart)) ?> · 
            Total spent: <strong class="text-negative">₹<?= number_format($grand_total, 2) ?></strong>
        </p>
    </div>
    <div class="col-md-4 text-end mt-3 mt-md-0">
        <form method="GET" class="d-inline">
            <input type="month" name="month" class="form-control bg-dark text-light border-secondary d-inline-block w-auto" 
                   value="<?= $monthStr ?>" onchange="this.form.submit()">
        </form>
    </div>
</div>

<?php if (empty($cat_data)): ?>
<div class="glass-card p-5 text-center text-secondary fade-in-up">
    <i class="bi bi-pie-chart fs-1 d-block mb-3 opacity-30"></i>
    <h5>No expense data for <?= date('F Y', strtotime($monthStart)) ?></h5>
    <p class="small">Log some transactions to see your spending breakdown.</p>
    <a href="../dashboard/" class="btn btn-neon mt-2"><i class="bi bi-plus-circle me-2"></i>Log Transaction</a>
</div>
<?php else: ?>

<!-- Category Cards Grid -->
<div class="cat-grid fade-in-up delay-1" id="catGrid">
    <?php foreach ($cat_data as $idx => $cat):
        $color = $palette[$idx % count($palette)];
        $pct = $grand_total > 0 ? round(($cat['total'] / $grand_total) * 100) : 0;
        $subs_json = json_encode(array_map(fn($s) => ['label' => $s['subcategory'] ?: 'Uncat.', 'value' => (float)$s['total']], $cat['subs']));
        $txns_json = json_encode(array_map(fn($t) => [
            'desc' => $t['description'] ?? '',
            'sub'  => $t['subcategory'] ?? '',
            'amt'  => abs($t['amount']),
            'date' => date('M d', strtotime($t['date'])),
            'method' => $t['method'] ?? $t['payment_method'] ?? 'UPI',
        ], $cat['txns']));
    ?>
    <div class="cat-card"
         data-cat-name="<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>"
         data-cat-color="<?= $color ?>"
         data-cat-total="<?= $cat['total'] ?>"
         data-cat-pct="<?= $pct ?>"
         data-cat-subs='<?= htmlspecialchars($subs_json, ENT_QUOTES) ?>'
         data-cat-txns='<?= htmlspecialchars($txns_json, ENT_QUOTES) ?>'
         data-pie-idx="<?= $idx ?>"
         style="border-top: 3px solid <?= $color ?>; --card-color: <?= $color ?>;">

        <div class="pie-wrapper" style="pointer-events: none;">
            <canvas id="pie_<?= $idx ?>" width="120" height="120"></canvas>
            <div class="pie-center-label">
                <div style="font-size:18px;font-weight:800;color:<?= $color ?>;"><?= $pct ?>%</div>
            </div>
        </div>

        <div class="cat-name"><?= htmlspecialchars($cat['name']) ?></div>
        <div class="cat-amount" style="color:<?= $color ?>;">₹<?= number_format($cat['total'], 0) ?></div>
        <div class="cat-share"><?= count($cat['subs']) ?> sub-categories · <?= $pct ?>% of total</div>
        <div class="click-hint"><i class="bi bi-zoom-in me-1"></i>Click to explore</div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Summary Bar -->
<div class="glass-card p-4 mt-4 fade-in-up delay-2">
    <h6 class="fw-bold mb-3 text-secondary text-uppercase" style="font-size:11px;letter-spacing:1px;">Spending Distribution</h6>
    <div style="display:flex;height:16px;border-radius:999px;overflow:hidden;gap:2px;">
        <?php foreach ($cat_data as $idx => $cat):
            $color = $palette[$idx % count($palette)];
            $pct = $grand_total > 0 ? ($cat['total'] / $grand_total) * 100 : 0;
        ?>
        <div style="width:<?= $pct ?>%;background:<?= $color ?>;transition:width 1s ease;" 
             title="<?= htmlspecialchars($cat['name']) ?>: ₹<?= number_format($cat['total'],0) ?>"></div>
        <?php endforeach; ?>
    </div>
    <div class="d-flex flex-wrap gap-3 mt-3">
        <?php foreach ($cat_data as $idx => $cat): $color = $palette[$idx % count($palette)]; ?>
        <div class="d-flex align-items-center gap-2" style="font-size:12px;">
            <span style="width:10px;height:10px;border-radius:50%;background:<?= $color ?>;display:inline-block;"></span>
            <span class="text-secondary"><?= htmlspecialchars($cat['name']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php endif; ?>

<!-- Detail Modal -->
<div class="modal fade" id="catDetailModal" tabindex="-1" style="backdrop-filter: blur(8px);">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" id="catDetailContent">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="detailTitle"></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-5 text-center mb-4 mb-md-0">
            <div class="detail-chart-wrap">
                <canvas id="detailPieChart" width="180" height="180"></canvas>
                <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;">
                    <div id="detailTotal" style="font-size:1.3rem;font-weight:800;"></div>
                    <div class="text-secondary" style="font-size:11px;">TOTAL</div>
                </div>
            </div>
            <div id="detailSubLegend" class="mt-3 text-start px-2"></div>
          </div>
          <div class="col-md-7">
            <h6 class="fw-bold text-secondary text-uppercase mb-3" style="font-size:11px;letter-spacing:1px;">Subcategory Breakdown</h6>
            <div id="detailSubBars" class="mb-4"></div>
            <h6 class="fw-bold text-secondary text-uppercase mb-3" style="font-size:11px;letter-spacing:1px;">Recent Transactions</h6>
            <div id="detailTxns" style="max-height:200px;overflow-y:auto;scrollbar-width:thin;"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Categories JS - Event Delegation Pattern (Bootstrap-safe) -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const PALETTE = ['#38bdf8','#10b981','#a78bfa','#f59e0b','#ef4444','#ec4899','#06b6d4','#84cc16'];
    let detailChart  = null;
    let detailModal  = null;

    /* ── Draw Pie Charts ────────────────────────────────────── */
    document.querySelectorAll('.cat-card').forEach(function (card) {
        const idx   = card.dataset.pieIdx;
        const color = card.dataset.catColor;
        const subs  = JSON.parse(decodeURIComponent(card.dataset.catSubs || '%5B%5D'));
        const canvas = document.getElementById('pie_' + idx);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (!subs.length) {
            new Chart(ctx, { type: 'doughnut', data: {
                labels: [card.dataset.catName],
                datasets: [{ data: [1], backgroundColor: [color], borderWidth: 0 }]
            }, options: { cutout: '65%', plugins: { legend: { display: false }, tooltip: { enabled: false } }, animation: { duration: 800 } }});
        } else {
            new Chart(ctx, { type: 'doughnut', data: {
                labels: subs.map(s => s.label),
                datasets: [{ data: subs.map(s => s.value), backgroundColor: subs.map((_,i) => PALETTE[i % PALETTE.length] + 'cc'), borderWidth: 0 }]
            }, options: { cutout: '65%', plugins: { legend: { display: false }, tooltip: { enabled: false } }, animation: { duration: 800 } }});
        }
    });

    /* ── Modal: Event Delegation on #catGrid ─────────────── */
    const grid = document.getElementById('catGrid');
    if (!grid) return;

    grid.addEventListener('click', function (e) {
        const card = e.target.closest('.cat-card');
        if (!card) return;

        const name  = card.dataset.catName;
        const color = card.dataset.catColor;
        const total = parseFloat(card.dataset.catTotal);
        const subs  = JSON.parse(decodeURIComponent(card.dataset.catSubs  || '%5B%5D'));
        const txns  = JSON.parse(decodeURIComponent(card.dataset.catTxns  || '%5B%5D'));

        /* Title */
        document.getElementById('detailTitle').innerHTML = `<span style="color:${color}">${name}</span> — Breakdown`;
        document.getElementById('detailTotal').style.color = color;
        document.getElementById('detailTotal').textContent = '₹' + Math.floor(total).toLocaleString('en-IN');

        /* Destroy old chart before creating new one */
        if (detailChart) { detailChart.destroy(); detailChart = null; }
        const ctx2   = document.getElementById('detailPieChart').getContext('2d');
        const labels = subs.length ? subs.map(s => s.label) : [name];
        const data   = subs.length ? subs.map(s => s.value) : [total];
        const colors = subs.length ? subs.map((_,i) => PALETTE[i % PALETTE.length]) : [color];
        detailChart  = new Chart(ctx2, {
            type: 'doughnut',
            data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 0, hoverOffset: 8 }] },
            options: { cutout: '60%', plugins: { legend: { display: false }, tooltip: { callbacks: {
                label: c => ` ₹${Math.floor(c.raw).toLocaleString('en-IN')} (${Math.round(c.raw/total*100)}%)`
            }}}, animation: { duration: 600 } }
        });

        /* Legend */
        document.getElementById('detailSubLegend').innerHTML = labels.map((l,i) => `
            <div style="display:flex;align-items:center;gap:6px;font-size:12px;margin-bottom:5px;">
                <span style="width:9px;height:9px;border-radius:50%;background:${colors[i]};display:inline-block;flex-shrink:0;"></span>
                <span style="color:var(--text-muted);">${l}</span>
            </div>`).join('');

        /* Sub bars */
        const barsEl = document.getElementById('detailSubBars');
        if (!subs.length) {
            barsEl.innerHTML = '<div class="small" style="color:var(--text-muted);">No subcategory data.</div>';
        } else {
            const maxVal = Math.max(...subs.map(s => s.value));
            barsEl.innerHTML = subs.map((s,i) => `
                <div class="sub-item">
                    <span class="sub-dot" style="background:${PALETTE[i%PALETTE.length]};"></span>
                    <span style="min-width:90px;font-size:12px;color:var(--text-main);">${s.label}</span>
                    <div class="sub-bar-wrap flex-grow-1">
                        <div class="sub-bar-fill" style="width:${(s.value/maxVal*100)}%;background:${PALETTE[i%PALETTE.length]};"></div>
                    </div>
                    <span style="font-size:12px;font-weight:700;color:${PALETTE[i%PALETTE.length]};min-width:70px;text-align:right;">₹${Math.floor(s.value).toLocaleString('en-IN')}</span>
                </div>`).join('');
        }

        /* Transactions */
        const txnEl = document.getElementById('detailTxns');
        if (!txns.length) {
            txnEl.innerHTML = '<div class="small" style="color:var(--text-muted);">No transactions.</div>';
        } else {
            txnEl.innerHTML = txns.map(t => `
                <div style="padding:8px 0;border-bottom:1px solid var(--border-card);display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <div style="color:var(--text-main);font-weight:600;font-size:13px;">${t.desc || t.sub || '—'}</div>
                        <div style="color:var(--text-muted);font-size:11px;">${t.sub} · ${t.method} · ${t.date}</div>
                    </div>
                    <span style="color:var(--e-500);font-weight:700;white-space:nowrap;padding-left:12px;">-₹${t.amt.toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2})}</span>
                </div>`).join('');
        }

        /* Show modal — Bootstrap is guaranteed loaded at DOMContentLoaded */
        if (!detailModal) {
            detailModal = new bootstrap.Modal(document.getElementById('catDetailModal'));
        }
        detailModal.show();
    });
});
</script>

<?php include '../includes/footer.php'; ?>

