<?php
// auth/register.php
session_start();
require '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Basic validation
    if (strlen($password) < 6) {
        header("Location: ../index.php?error=Password must be at least 6 characters.");
        exit();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../index.php?error=Invalid email address.");
        exit();
    }

    $hashed_pw = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (email, hashed_pw, theme_pref) VALUES (?, ?, 'dark')");
        if ($stmt->execute([$email, $hashed_pw])) {
            header("Location: ../index.php?msg=Account created successfully! You can now login.");
            exit();
        }
    } catch (PDOException $e) {
        // Handle Duplicate Email
        if ($e->getCode() == 23000) {
            header("Location: ../index.php?error=Email is already taken.");
        } else {
            header("Location: ../index.php?error=Registration failed.");
        }
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}
?>
