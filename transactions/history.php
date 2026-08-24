<?php
// transactions/history.php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit(); }
require '../config/database.php';
$user_id = $_SESSION['user_id'];

$page  = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;
$f_cat   = $_GET['cat']   ?? '';
$f_month = $_GET['month'] ?? '';

$where  = "user_id = :uid";
$params = [':uid' => $user_id];
if ($f_cat)   { $where .= " AND category = :cat"; $params[':cat'] = $f_cat; }
if ($f_month) { $where .= " AND DATE_FORMAT(date, '%Y-%m') = :month"; $params[':month'] = $f_month; }

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE $where");
$count_stmt->execute($params);
$total = $count_stmt->fetchColumn();
$pages = ceil($total / $limit);

// Summary for filtered view
$sum_stmt = $pdo->prepare("SELECT 
    SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as income,
    SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as expenses
    FROM transactions WHERE $where");
$sum_stmt->execute($params);
$summary = $sum_stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM transactions WHERE $where ORDER BY date DESC, id DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$txns = $stmt->fetchAll();

include '../includes/header.php';
?>

<style>
.ledger-row {
    display: grid;
    grid-template-columns: 100px 1fr 130px 90px 80px 110px;
    align-items: center;
    gap: 12px;
    padding: 13px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    transition: background 0.18s;
    font-size: 14px;
}
.ledger-row:hover { background: rgba(255,255,255,0.03); }
.ledger-head { 
    font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
    color: var(--text-muted); padding: 10px 16px; border-bottom: 1px solid var(--border-card);
    display: grid;
    grid-template-columns: 100px 1fr 130px 90px 80px 110px;
    gap: 12px;
}
.amount-cell { text-align: right; font-weight: 700; font-size: 15px; }
.income-amt  { color: #10b981; }
.expense-amt { color: #ef4444; }
.cat-badge {
    display: inline-block; padding: 2px 10px;
    border-radius: 999px; font-size: 11px; font-weight: 600;
    border: 1px solid var(--border-card);
    background: var(--bg-card);
    color: var(--text-muted);
}
.method-badge {
    display: inline-block; padding: 2px 8px;
    border-radius: 6px; font-size: 10px; font-weight: 700; letter-spacing: 0.5px;
    border: 1px solid var(--border-card);
    background: var(--bg-card);
    color: var(--text-muted);
}
.summary-bar { display: flex; gap: 24px; padding: 14px 16px; background: rgba(0,0,0,0.2); border-radius: 12px; }
</style>

<div class="row fade-in-up">
    <div class="col-md-8 mb-4">
        <h2 class="fw-bold mb-1">Transaction Ledger</h2>
        <p class="text-secondary mb-0">Complete history · <?= number_format($total) ?> transaction<?= $total != 1 ? 's' : '' ?></p>
    </div>
    <div class="col-md-4 text-end mt-3 mt-md-0">
        <a href="../dashboard/" class="btn btn-neon w-100">
            <i class="bi bi-plus-circle-fill me-2"></i> Log Transaction
        </a>
    </div>
</div>

<div class="glass-card fade-in-up delay-1 mb-5" style="overflow:hidden;">
    <!-- Filter Bar -->
    <form method="GET" class="d-flex flex-wrap gap-2 p-3 border-bottom border-secondary border-opacity-25">
        <select name="cat" class="form-select bg-dark text-light border-secondary" style="width:auto;">
            <option value="">All Categories</option>
            <?php foreach (['Food','Transport','Entertainment','Shopping','Bills','Health','Necessities','Investments','Junk','Income'] as $c): ?>
            <option value="<?= $c ?>" <?= $f_cat === $c ? 'selected' : '' ?>><?= $c ?></option>
            <?php endforeach; ?>
        </select>
        <input type="month" name="month" class="form-control bg-dark text-light border-secondary" style="width:auto;" value="<?= htmlspecialchars($f_month) ?>">
        <button type="submit" class="btn btn-outline-light"><i class="bi bi-funnel me-1"></i>Filter</button>
        <a href="history.php" class="btn btn-outline-secondary">Clear</a>
    </form>

    <!-- Summary Cards -->
    <?php if ($total > 0): ?>
    <div class="d-flex gap-3 p-3 border-bottom border-secondary border-opacity-25 flex-wrap">
        <div style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);border-radius:10px;padding:10px 18px;text-align:center;">
            <div style="font-size:11px;color:#10b981;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Income</div>
            <div style="font-size:18px;font-weight:800;color:#10b981;">+₹<?= number_format($summary['income'], 2) ?></div>
        </div>
        <div style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:10px;padding:10px 18px;text-align:center;">
            <div style="font-size:11px;color:#ef4444;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Expenses</div>
            <div style="font-size:18px;font-weight:800;color:#ef4444;">-₹<?= number_format($summary['expenses'], 2) ?></div>
        </div>
        <div style="background:rgba(56,189,248,0.08);border:1px solid rgba(56,189,248,0.2);border-radius:10px;padding:10px 18px;text-align:center;">
            <?php $net = $summary['income'] - $summary['expenses']; ?>
            <div style="font-size:11px;color:#38bdf8;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Net</div>
            <div style="font-size:18px;font-weight:800;color:<?= $net >= 0 ? '#10b981' : '#ef4444' ?>;"><?= $net >= 0 ? '+' : '' ?>₹<?= number_format(abs($net), 2) ?></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Table Header -->
    <div class="ledger-head">
        <span>Date</span>
        <span>Description</span>
        <span>Category</span>
        <span>Subcategory</span>
        <span>Method</span>
        <span style="text-align:right;">Amount</span>
    </div>

    <!-- Table Rows -->
    <?php if (empty($txns)): ?>
    <div class="text-center text-secondary py-5">
        <i class="bi bi-inbox fs-1 d-block mb-2 opacity-30"></i>
        No transactions found for this query.
    </div>
    <?php else: ?>
        <?php foreach ($txns as $t):
            $is_income = $t['amount'] > 0;
            $sign = $is_income ? '+' : '-';
            $method = $t['payment_method'] ?? 'UPI';
        ?>
        <div class="ledger-row">
            <span class="text-secondary" style="font-size:13px;"><?= date('M d, Y', strtotime($t['date'])) ?></span>
            <span class="fw-semibold text-light" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($t['description'] ?? $t['desc'] ?? '—') ?></span>
            <span><span class="cat-badge"><?= htmlspecialchars($t['category']) ?></span></span>
            <span><span class="cat-badge" style="opacity:0.7;"><?= htmlspecialchars($t['subcategory'] ?? '') ?></span></span>
            <span><span class="method-badge"><?= htmlspecialchars($method) ?></span></span>
            <span class="amount-cell <?= $is_income ? 'income-amt' : 'expense-amt' ?>"><?= $sign ?>₹<?= number_format(abs($t['amount']), 2) ?></span>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="p-3 border-top border-secondary border-opacity-25 d-flex justify-content-center gap-2">
        <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?>&cat=<?= urlencode($f_cat) ?>&month=<?= urlencode($f_month) ?>" class="btn btn-sm btn-outline-secondary">← Prev</a><?php endif; ?>
        <span class="btn btn-sm btn-outline-secondary disabled">Page <?= $page ?> / <?= $pages ?></span>
        <?php if ($page < $pages): ?><a href="?page=<?= $page+1 ?>&cat=<?= urlencode($f_cat) ?>&month=<?= urlencode($f_month) ?>" class="btn btn-sm btn-outline-secondary">Next →</a><?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
