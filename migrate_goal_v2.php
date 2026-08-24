<?php
require 'config/database.php';
try {
    $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'savings_goal'")->fetch();
    if (!$check) {
        $pdo->exec("ALTER TABLE users ADD COLUMN savings_goal INT DEFAULT 2000");
        echo "Migration successful: savings_goal column added.";
    }
    else {
        echo "Migration skipped: savings_goal column already exists.";
    }
}
catch (Exception $e) {
    echo "Migration error: " . $e->getMessage();
}
?>
