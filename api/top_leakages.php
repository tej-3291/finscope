<?php
// api/top_leakages.php
// Returns the top 3 discretionary "leakage" subcategories from the last 6 months.
// Excludes: Income, Investments, Necessities (healthy / unavoidable spend).
// Groups by subcategory (granular level), sorted by total spend.

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

require '../config/database.php';
$user_id = $_SESSION['user_id'];

// Categories considered "leakage" (discretionary, cuttable)
// Strictly excluding healthy/unavoidable spend as per user request.
$EXCLUDED_CATEGORIES = ['Income', 'Investments', 'Necessities'];
$placeholders = implode(',', array_fill(0, count($EXCLUDED_CATEGORIES), '?'));

// Look back 6 calendar months
$six_months_ago = date('Y-m-d', strtotime('-6 months'));

$sql = "
    SELECT
        COALESCE(NULLIF(TRIM(subcategory), ''), category) AS sub_label,
        category,
        SUM(ABS(amount))                                  AS total_6m,
        COUNT(*)                                          AS txn_count_6m,
        COUNT(DISTINCT DATE_FORMAT(date, '%Y-%m'))        AS months_active,
        SUM(ABS(amount)) / COUNT(*)                       AS avg_per_txn
    FROM transactions
    WHERE
        user_id      = ?
        AND amount   < 0
        AND date     >= ?
        AND category NOT IN ($placeholders)
    GROUP BY sub_label, category
    HAVING txn_count_6m >= 1
    ORDER BY txn_count_6m DESC
    LIMIT 3
";

$params = array_merge([$user_id, $six_months_ago], $EXCLUDED_CATEGORIES);
$stmt   = $pdo->prepare($sql);
$stmt->execute($params);
$rows   = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build response with monthly averages
$result = [];
foreach ($rows as $row) {
    $months         = max(1, (int)$row['months_active']);
    $monthly_count  = round($row['txn_count_6m'] / $months, 1);
    $monthly_avg    = round($row['total_6m']     / $months, 2);
    $avg_per_txn    = round($row['avg_per_txn'],  2);

    $result[] = [
        'subcategory'   => $row['sub_label'],
        'category'      => $row['category'],
        'monthly_count' => $monthly_count,      // avg times/month
        'monthly_avg'   => $monthly_avg,         // avg ₹/month
        'avg_per_txn'   => $avg_per_txn,         // avg ₹ per transaction
        'total_6m'      => (float) $row['total_6m'],
    ];
}

echo json_encode($result);
?>
