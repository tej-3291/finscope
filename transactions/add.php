<?php
// transactions/add.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
require '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $type = $_POST['type'] ?? 'expense';
    $amount = abs(floatval($_POST['amount']));
    
    // Apply polarity strict rules requested
    if ($type === 'expense') {
        $amount = -$amount;
    }
    
    $category = trim($_POST['category']);
    $subcategory = trim($_POST['subcategory']);
    $description = trim($_POST['description']);
    $method = $_POST['method'] ?? 'UPI';
    $date = $_POST['date'] ?? date('Y-m-d');

    try {
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, category, subcategory, description, payment_method, date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $amount, $category, $subcategory, $description, $method, $date]);
        
        // Safe redirect: only allow internal paths (avoid open redirect via Referer)
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host && strpos($referer, $host) !== false) {
            header("Location: $referer");
        } else {
            header("Location: ../dashboard/");
        }
    } catch (PDOException $e) {
        die("Error saving transaction: " . $e->getMessage());
    }
} else {
    header("Location: ../dashboard/");
}
?>
