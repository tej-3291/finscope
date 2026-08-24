<?php
// api/update_theme.php
session_start();
require '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$theme = $data['theme'] ?? 'dark';
$user_id = $_SESSION['user_id'];

$allowed = ['dark', 'light', 'emerald'];
if (!in_array($theme, $allowed)) {
    http_response_code(400);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE users SET theme_pref = ? WHERE id = ?");
    $stmt->execute([$theme, $user_id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
}
?>
