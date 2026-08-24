<?php
// api/categories_data.php
session_start();
if (!isset($_SESSION['user_id'])) { exit; }
require '../config/database.php';

$user_id = $_SESSION['user_id'];
$focus = $_GET['focus'] ?? 'Food';
$monthStart = ($_GET['month'] ?? date('Y-m')) . '-01';
$monthEnd = date('Y-m-t', strtotime($monthStart));

$stmt = $pdo->prepare("
    SELECT subcategory, SUM(ABS(amount)) as total
    FROM transactions
    WHERE user_id = ? AND category = ? AND amount < 0 AND date BETWEEN ? AND ?
    GROUP BY subcategory
    ORDER BY total DESC
");
$stmt->execute([$user_id, $focus, $monthStart, $monthEnd]);
$data = $stmt->fetchAll();

$response = [
    'labels' => [],
    'data' => []
];

foreach ($data as $row) {
    if (empty($row['subcategory'])) $row['subcategory'] = 'Uncat.';
    $response['labels'][] = $row['subcategory'];
    $response['data'][] = (float)$row['total'];
}

header('Content-Type: application/json');
echo json_encode($response);
?>
