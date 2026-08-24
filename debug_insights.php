<?php
require 'config/database.php';
$user_id = 3; // Assuming test user is 3 based on earlier logs

$insights = [];

// Fetch Top Leakages (Frequency Based)
$stmt_leak = $pdo->prepare("
    SELECT 
        COALESCE(NULLIF(TRIM(subcategory), ''), category) as label,
        COUNT(*) as freq,
        SUM(ABS(amount)) as total
    FROM transactions 
    WHERE user_id = ? 
    AND amount < 0 
    AND category NOT IN ('Income', 'Investments', 'Necessities')
    AND date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
    GROUP BY label
    ORDER BY freq DESC
    LIMIT 1
");
$stmt_leak->execute([$user_id]);
$top_leak = $stmt_leak->fetch();

if ($top_leak) {
    $monthly_avg = $top_leak['total'];
    $potential_save = round($monthly_avg * 0.5); // 50% reduction target
    $insights[] = [
        'icon' => 'bi-lightning-charge-fill',
        'color' => 'text-warning',
        'title' => 'Frequency Leak Detected',
        'text' => "You bought <strong>{$top_leak['label']}</strong> {$top_leak['freq']} times this month. Cutting this in half would retain <strong>₹" . number_format($potential_save) . "</strong> monthly."
    ];
}

// Rule 2: Savings Rate Analysis (Real Data)
$stmt_sr = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as inc,
        SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as exp
    FROM transactions 
    WHERE user_id = ? AND date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
");
$stmt_sr->execute([$user_id]);
$sr = $stmt_sr->fetch();
$inc = $sr['inc'] ?? 0;
$exp = $sr['exp'] ?? 0;
$rate = $inc > 0 ? (($inc - $exp) / $inc) * 100 : 0;

if ($rate < 15 && $inc > 0) {
    $insights[] = [
        'icon' => 'bi-piggy-bank-fill',
        'color' => 'text-negative',
        'title' => 'Savings Under Pressure',
        'text' => "Your 30-day savings rate is " . round($rate, 1) . "%. To reach 'Elite' status, we should target a 20% margin by auditing 'Junk' spends."
    ];
}
elseif ($rate >= 30) {
    $insights[] = [
        'icon' => 'bi-trophy-fill',
        'color' => 'text-positive',
        'title' => 'Wealth Warrior Status',
        'text' => "Exceptional! You've retained " . round($rate, 1) . "% of your income this month. Tactical tip: Move surplus into 'Investments' to shield it from inflation."
    ];
}

// Rule 3: Subscriptions Check (Real Transactions)
$stmt_subs = $pdo->prepare("
    SELECT description, ABS(amount) as amt 
    FROM transactions 
    WHERE user_id = ? AND (category = 'Bills' OR category = 'Entertainment')
    AND (description LIKE '%Netflix%' OR description LIKE '%Spotify%' OR description LIKE '%Prime%' OR description LIKE '%Hotstar%' OR subcategory = 'Subscriptions')
    AND date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
");
$stmt_subs->execute([$user_id]);
$subs = $stmt_subs->fetchAll();

if (count($subs) >= 1) {
    $total_subs = array_sum(array_column($subs, 'amt'));
    $insights[] = [
        'icon' => 'bi-play-btn-fill',
        'color' => 'text-neon',
        'title' => 'Subscription Audit',
        'text' => "Detected " . count($subs) . " active utility/media subscriptions costing <strong>₹" . number_format($total_subs) . "</strong>. Are these all still providing value?"
    ];
}

// Fallback
if (count($insights) < 2) {
    $insights[] = [
        'icon' => 'bi-shield-check',
        'color' => 'text-positive',
        'title' => 'Disciplined Trajectory',
        'text' => "Your recent spending habits show high intentionality. Continue this streak to maintain your financial fortress."
    ];
}

echo "OUTPUT:\n";
echo json_encode($insights);
$json_error = json_last_error_msg();
echo "\nJSON Error: " . $json_error;
?>
