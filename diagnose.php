<?php
session_start();
require 'config/database.php';
header('Content-Type: application/json');
$res = [
    'session' => $_SESSION,
    'db_check' => [],
    'user_record' => []
];
try {
    $stmt = $pdo->query("DESCRIBE users");
    $res['db_check'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (isset($_SESSION['user_id'])) {
        $uid = $_SESSION['user_id'];
        $stmt_u = $pdo->prepare("SELECT id, email, savings_goal FROM users WHERE id = ?");
        $stmt_u->execute([$uid]);
        $res['user_record'] = $stmt_u->fetch(PDO::FETCH_ASSOC);
    }
}
catch (Exception $e) {
    $res['exception'] = $e->getMessage();
}
echo json_encode($res, JSON_PRETTY_PRINT);
?>
