<?php
// api/dashboard_data.php
session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(401); exit(json_encode(['error' => 'Unauthorized'])); }
require '../config/database.php';

$user_id = $_SESSION['user_id'];
$response = [];

// 1. Behavior Bar Graph Data (Last 6 Months grouped by Category for Expenses only)
$behavior_stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(date, '%b %y') as month_label,
        category,
        SUM(ABS(amount)) as total
    FROM transactions 
    WHERE user_id = ? AND amount < 0 AND date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY month_label, category
    ORDER BY MIN(date) ASC
");
$behavior_stmt->execute([$user_id]);
$behavior_data = $behavior_stmt->fetchAll();

// Structure Behavior Data for Chart.js (Vertical grouped bars)
$months = [];
$categories = [];
foreach ($behavior_data as $row) {
    if (!in_array($row['month_label'], $months)) $months[] = $row['month_label'];
    if (!in_array($row['category'], $categories)) $categories[] = $row['category'];
}

$datasets = [];
// Premium vibrant colors for dark mode bars
$colors = ['#38bdf8', '#818cf8', '#c084fc', '#f472b6', '#fb7185', '#fb923c', '#facc15']; 
$color_idx = 0;

foreach ($categories as $cat) {
    if($cat === 'Income') continue; // Don't plot income in behavior graph
    
    $data_points = [];
    foreach ($months as $m) {
        $val = 0;
        foreach ($behavior_data as $row) {
            if ($row['month_label'] === $m && $row['category'] === $cat) {
                $val = (float)$row['total']; break;
            }
        }
        $data_points[] = $val;
    }
    
    $datasets[] = [
        'label' => $cat,
        'data' => $data_points,
        'backgroundColor' => $colors[$color_idx % count($colors)],
        'borderRadius' => 4,
    ];
    $color_idx++;
}

$response['behavior'] = [
    'labels' => $months,
    'datasets' => $datasets
];

// 2. Pie Chart Data
$pieMonth = $_GET['month'] ?? date('Y-m'); // Expected 'YYYY-MM'
$pieMonthStart = $pieMonth . '-01';
$pieMonthEnd = date('Y-m-t', strtotime($pieMonthStart));

$pie_stmt = $pdo->prepare("
    SELECT category, SUM(ABS(amount)) as total 
    FROM transactions 
    WHERE user_id = ? AND amount < 0 AND date BETWEEN ? AND ? 
    GROUP BY category
");
$pie_stmt->execute([$user_id, $pieMonthStart, $pieMonthEnd]);
$pie_data = $pie_stmt->fetchAll();

$pie_labels = [];
$pie_values = [];
$pie_colors = [];

// Reuse same colors to keep UI consistent
$color_idx = 0;
foreach ($pie_data as $row) {
    $pie_labels[] = $row['category'];
    $pie_values[] = (float)$row['total'];
    $pie_colors[] = $colors[$color_idx % count($colors)];
    $color_idx++;
}

$response['pie'] = [
    'labels' => $pie_labels,
    'data' => $pie_values,
    'colors' => $pie_colors
];

header('Content-Type: application/json');
echo json_encode($response);
?>
