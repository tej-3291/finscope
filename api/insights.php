<?php
// api/insights.php
session_start();
if (!isset($_SESSION['user_id'])) {
    exit;
}
require '../config/database.php';
$user_id = $_SESSION['user_id'];

$insights = [];

// Calculate total monthly expenditure first
$stmt_exp = $pdo->prepare("
    SELECT SUM(ABS(amount)) as total_spent 
    FROM transactions 
    WHERE user_id = ? AND amount < 0 AND date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
");
$stmt_exp->execute([$user_id]);
$total_spent = $stmt_exp->fetchColumn() ?: 0;

// Fetch Top 3 Leakages (Frequency Based)
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
    LIMIT 3
");
$stmt_leak->execute([$user_id]);
$top_leaks = $stmt_leak->fetchAll();

if (count($top_leaks) > 0 && $total_spent > 0) {
    $leak_names = array_column($top_leaks, 'label');
    $total_leak_amount = array_sum(array_column($top_leaks, 'total'));
    $potential_save = round($total_leak_amount * 0.5); // 50% reduction
    
    $leak_pct = round(($total_leak_amount / $total_spent) * 100);
    $names_str = implode(', ', $leak_names);
    
    $insights[] = [
        'icon' => 'bi-lightning-charge-fill',
        'color' => 'text-warning',
        'title' => 'Expenditure & Micro Leaks',
        'text' => "Your overall 30-day spend is <strong>₹" . number_format($total_spent) . "</strong>. Your top 3 frequency leaks (<span class='text-warning'>" . $names_str . "</span>) account for <strong>" . $leak_pct . "%</strong> of this. Cutting these micro-expenses in half would securely retain <strong>₹" . number_format($potential_save) . "</strong> every month."
    ];
} else {
    // Fallback if no leakages are found
    $insights[] = [
        'icon' => 'bi-shield-check',
        'color' => 'text-positive',
        'title' => 'Optimal Expenditure',
        'text' => "Your 30-day spend is <strong>₹" . number_format($total_spent) . "</strong> with zero high-frequency micro leaks detected in non-essential categories. Elite trajectory maintained!"
    ];
}

// Return JSON instead of HTML
header('Content-Type: application/json');
echo json_encode($insights);
?>
