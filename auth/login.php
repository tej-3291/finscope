<?php
// auth/login.php
session_start();
require '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT id, hashed_pw, theme_pref FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['hashed_pw'])) {
            // Login Success
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['theme'] = $user['theme_pref'] ?? 'dark';
            
            // Set cookie for JS theme pickup on first load
            setcookie('finscope_theme', $_SESSION['theme'], time() + (86400 * 30), "/");

            header("Location: ../dashboard/");
            exit();
        } else {
            header("Location: ../index.php?error=Invalid email or password.");
            exit();
        }
    } catch (PDOException $e) {
        header("Location: ../index.php?error=Database error: " . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}
?>
