<?php
// profile/update.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
require '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $new_pw = trim($_POST['new_password']);
    $goal = intval($_POST['goal'] ?? 2000);

    try {
        // Update Goal
        $stmt_goal = $pdo->prepare("UPDATE users SET savings_goal = ? WHERE id = ?");
        $stmt_goal->execute([$goal, $user_id]);

        // Update Password if provided
        if (!empty($new_pw)) {
            if (strlen($new_pw) < 6) {
                header("Location: index.php?error=Password must be 6+ characters.");
                exit();
            }
            $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
            $stmt_pw = $pdo->prepare("UPDATE users SET hashed_pw = ? WHERE id = ?");
            $stmt_pw->execute([$hashed, $user_id]);
            header("Location: index.php?msg=Security and Goal settings updated successfully!");
        }
        else {
            header("Location: index.php?msg=Monthly Savings Goal updated successfully!");
        }
    }
    catch (Exception $e) {
        header("Location: index.php?error=Update failed: " . $e->getMessage());
    }
}
?>
