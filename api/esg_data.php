<?php
// api/esg_data.php
session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['error' => 'Unauthorized']));
}
require '../config/database.php';

$user_id = $_SESSION['user_id'];

// Fetch last 30 days of transactions grouped by category
$stmt = $pdo->prepare("
    SELECT category, SUM(ABS(amount)) as total 
    FROM transactions 
    WHERE user_id = ? AND date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY) AND amount < 0
    GROUP BY category
");
$stmt->execute([$user_id]);
$data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

/* ESG Weighting Logic: Environmental: Discretionary/Junk/Food (High Carbon) vs Necessities/Investments Social: Local spending/Charity (Necessities) Governance: Financial Discipline (Investments/Savings) */

$scores = [
    'Environmental' => 70, // Base
    'Social' => 75,
    'Governance' => 65
];

if (!empty($data)) {
    $total_spend = array_sum($data);

    $junk_p = ($data['Junk'] ?? 0) / $total_spend;
    $inv_p = ($data['Investments'] ?? 0) / $total_spend;
    $nec_p = ($data['Necessities'] ?? 0) / $total_spend;

    // Impact Governance & Social
    $scores['Governance'] += ($inv_p * 100) - ($junk_p * 50);
    $scores['Social'] += ($nec_p * 40) - ($junk_p * 20);
    $scores['Environmental'] += ($nec_p * 20) - ($junk_p * 60);
}

// Clamp 0-100 and ensure numeric
foreach (['Environmental', 'Social', 'Governance'] as $k) {
    $v = $scores[$k] ?? 70;
    $scores[$k] = max(0, min(100, (int)round($v)));
}

$avg = array_sum($scores) / 3;
$grade = 'B';
if ($avg > 90)
    $grade = 'A+';
elseif ($avg > 80)
    $grade = 'A';
elseif ($avg > 70)
    $grade = 'B+';
elseif ($avg > 60)
    $grade = 'B';
else
    $grade = 'C';

header('Content-Type: application/json');
echo json_encode([
    'scores' => $scores,
    'grade' => $grade,
    'average' => round($avg)
]);
