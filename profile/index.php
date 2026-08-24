<?php
// profile/index.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
require '../config/database.php';

$user_id = $_SESSION['user_id'];
$msg = $_GET['msg'] ?? '';
$err = $_GET['error'] ?? '';

// Fetch User Data
$stmt = $pdo->prepare("SELECT email, theme_pref, savings_goal FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$savings_goal = $user['savings_goal'] ?? 2000;

// Fetch YTD Stats (Simplified proxy for Phase 2)
$stats_stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_income,
        SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as total_spent,
        SUM(CASE WHEN amount < 0 AND ABS(amount) < 100 THEN ABS(amount) ELSE 0 END) as micro_leakage
    FROM transactions 
    WHERE user_id = ? AND YEAR(date) = YEAR(CURRENT_DATE)
");
$stats_stmt->execute([$user_id]);
$stats = $stats_stmt->fetch();

$income = $stats['total_income'] ?? 0;
$spent = $stats['total_spent'] ?? 0;
$leakage = $stats['micro_leakage'] ?? 0;
$leakage_pct = $spent > 0 ? round(($leakage / $spent) * 100) : 0;

// Fetch active subscriptions (approximated by recurring Entertainment/Bills)
$subs_stmt = $pdo->prepare("
    SELECT description, ABS(amount) as cost, MAX(date) as last_paid
    FROM transactions
    WHERE user_id = ? AND category IN ('Entertainment', 'Bills') AND subcategory IN ('Media', 'Subscriptions', 'Utilities') AND amount < 0
    GROUP BY description, amount
    HAVING COUNT(id) >= 1
    ORDER BY last_paid DESC
    LIMIT 4
");
$subs_stmt->execute([$user_id]);
$subscriptions = $subs_stmt->fetchAll();

include '../includes/header.php';
?>

<div class="row fade-in-up">
    <div class="col-12 mb-4">
        <h2 class="fw-bold mb-1">Your Profile</h2>
        <p class="text-secondary">Manage settings and review your year-to-date trajectory.</p>
    </div>

    <!-- Alert Messages -->
    <div class="col-12">
        <?php if ($msg): ?>
            <div class="alert bg-positive-soft text-positive border-0"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if ($err): ?>
            <div class="alert bg-negative-soft text-negative border-0"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>
    </div>

    <!-- Stats Cards (YTD) -->
    <div class="col-md-4 mb-4 delay-1 fade-in-up">
        <div class="glass-card p-4 h-100 position-relative overflow-hidden">
            <div class="position-absolute opacity-10" style="right: -10px; bottom: -10px; font-size: 5rem;">
                <i class="bi bi-wallet2 text-positive"></i>
            </div>
            <p class="text-secondary mb-1 fw-bold text-uppercase small">YTD Income</p>
            <h3 class="text-positive mb-0 fw-bold">₹<span class="counter" data-target="<?= $income ?>">0</span></h3>
        </div>
    </div>
    
    <div class="col-md-4 mb-4 delay-2 fade-in-up">
        <div class="glass-card p-4 h-100 position-relative overflow-hidden">
            <div class="position-absolute opacity-10" style="right: -10px; bottom: -10px; font-size: 5rem;">
                <i class="bi bi-graph-down text-negative"></i>
            </div>
            <p class="text-secondary mb-1 fw-bold text-uppercase small">YTD Spent</p>
            <h3 class="text-negative mb-0 fw-bold">₹<span class="counter" data-target="<?= $spent ?>">0</span></h3>
        </div>
    </div>

    <div class="col-md-4 mb-4 delay-3 fade-in-up">
        <div class="glass-card p-4 h-100 position-relative overflow-hidden" style="border-bottom: 3px solid var(--accent-neon);">
            <div class="position-absolute opacity-10" style="right: -10px; bottom: -10px; font-size: 5rem;">
                <i class="bi bi-droplet-half text-neon"></i>
            </div>
            <p class="text-secondary mb-1 fw-bold text-uppercase small">Micro Leakage (< ₹100 Txns)</p>
            <h3 class="text-neon mb-0 fw-bold">₹<span class="counter" data-target="<?= $leakage ?>">0</span> <span class="fs-5 text-secondary fw-normal">(<?= $leakage_pct ?>% of total)</span></h3>
        </div>
    </div>

    <!-- Settings Form & Subscriptions -->
    <div class="col-lg-7 mx-auto mt-4 fade-in-up delay-2">
        <div class="glass-card p-5 h-100">
            <h4 class="mb-4 d-flex align-items-center"><i class="bi bi-gear-fill me-2 text-neon"></i> Account Settings</h4>
            <form action="update.php" method="POST">
                <div class="mb-4">
                    <label class="form-label text-secondary small text-uppercase">Email</label>
                    <input type="email" class="form-control p-3" value="<?= htmlspecialchars($user['email']) ?>" readonly disabled>
                    <div class="form-text text-secondary">Email cannot be changed currently.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-secondary small text-uppercase">Set Saving Goal (Monthly)</label>
                    <div class="input-group">
                        <span class="input-group-text" style="background:var(--bg-card);border-color:var(--border-card);color:var(--text-main);"><i class="bi bi-currency-rupee"></i></span>
                        <input type="number" name="goal" class="form-control p-3" placeholder="2000" value="<?= htmlspecialchars($savings_goal) ?>">
                    </div>
                </div>
                
                <hr class="my-4" style="border-color: var(--border-card);">
                
                <h5 class="mb-3">Security</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <input type="password" name="new_password" class="form-control p-3" placeholder="New Password (optional)">
                    </div>
                    <div class="col-md-6 mb-3">
                        <button type="submit" class="btn btn-neon w-100 p-3 fw-bold h-100">Update Profile</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Active Subscriptions Panel -->
    <div class="col-lg-5 mt-4 fade-in-up delay-3">
        <div class="glass-card p-4 h-100" style="background: linear-gradient(135deg, rgba(30, 41, 59, 0.6), rgba(15, 23, 42, 0.8));">
            <h5 class="fw-bold mb-4 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-arrow-repeat text-info me-2"></i> Active Subscriptions</span>
            </h5>
            
            <?php if(empty($subscriptions)): ?>
                <div class="text-center text-secondary py-4">No recurring subscriptions detected.</div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                <?php foreach($subscriptions as $sub): ?>
                    <div style="border:1px solid var(--border-card); background:var(--bg-card); border-radius:0.75rem;" class="d-flex justify-content-between align-items-center p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-info bg-opacity-25 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-play-circle-fill"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold" style="color:var(--text-main);"><?= htmlspecialchars($sub['description']) ?></h6>
                                <small style="color:var(--text-muted);">Last paid: <?= date('M d', strtotime($sub['last_paid'])) ?></small>
                            </div>
                        </div>
                        <div class="text-end">
                            <h6 class="mb-0 text-negative fw-bold">-₹<?= number_format($sub['cost'], 0) ?></h6>
                            <small style="color:var(--text-muted);">/mo</small>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
                
                <button class="btn btn-neon w-100 mt-4 btn-sm">Manage All</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// JS Counter Animation
document.addEventListener('DOMContentLoaded', () => {
    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
        const updateCount = () => {
            const target = +counter.getAttribute('data-target');
            const count = +counter.innerText;
            const speed = 200; // Lower is faster
            const inc = target / speed;

            if (count < target) {
                counter.innerText = Math.ceil(count + inc);
                setTimeout(updateCount, 1);
            } else {
                counter.innerText = target.toLocaleString('en-IN');
            }
        };
        updateCount();
    });
});
</script>

<?php include '../includes/footer.php'; ?>
