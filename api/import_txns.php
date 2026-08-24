<?php
// api/import_txns.php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}
require '../config/database.php';

$inputJSON = file_get_contents('php://input');
$input     = json_decode($inputJSON, true);

if (!isset($input['transactions']) || !is_array($input['transactions'])) {
    exit(json_encode(['success' => false, 'error' => 'Invalid payload.']));
}

$user_id  = $_SESSION['user_id'];
$txns     = $input['transactions'];
$imported = 0;

// Allowed payment methods (extended)
$VALID_METHODS = ['UPI', 'Card', 'Cash', 'Net Banking', 'NEFT', 'RTGS', 'Cheque', 'Wallet'];

/**
 * Determine the correct signed amount.
 * Rules:
 *  - If type === 'income' OR category === 'Income' → positive (money in)
 *  - Otherwise → negative (money out)
 */
function resolveAmount(float $rawAbs, string $type, string $category): float {
    if ($type === 'income' || strtolower($category) === 'income') {
        return $rawAbs;   // positive
    }
    return -$rawAbs;      // negative expense
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO transactions
            (user_id, amount, category, subcategory, description, payment_method, date)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($txns as $t) {
        $rawAbs  = abs((float)($t['amount'] ?? 0));
        $type    = strtolower(trim($t['type']     ?? 'expense'));
        $cat     = mb_substr(trim($t['category']  ?? 'Others'), 0, 50);
        $sub     = mb_substr(trim($t['subcategory'] ?? ''), 0, 50);
        // Accept both 'desc' and 'description' from the frontend
        $desc    = mb_substr(trim($t['description'] ?? $t['desc'] ?? ''), 0, 255);
        $method  = in_array($t['method'] ?? '', $VALID_METHODS) ? $t['method'] : 'UPI';
        $date    = $t['date'] ?? date('Y-m-d');

        $amount  = resolveAmount($rawAbs, $type, $cat);

        $stmt->execute([$user_id, $amount, $cat, $sub, $desc, $method, $date]);
        $imported++;
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'imported' => $imported]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
