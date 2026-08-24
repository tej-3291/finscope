<?php
session_start();
require 'config/database.php';
echo "--- TABLE STRUCTURE ---\n";
$stmt = $pdo->query("DESCRIBE transactions");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- SAMPLE DATA ---\n";
$stmt = $pdo->query("SELECT * FROM transactions LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- TOP LEAKAGES QUERY TEST ---\n";
$_SESSION['user_id'] = 1; // Assuming user 1 for test, change if needed
$user_id = 1;

$EXCLUDED_CATEGORIES = ['Income', 'Investments', 'Necessities'];
$placeholders = implode(',', array_fill(0, count($EXCLUDED_CATEGORIES), '?'));
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
print_r($rows);
?>
