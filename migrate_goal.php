<?php
require 'config/database.php';
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS savings_goal INT DEFAULT 2000");
    echo "Migration successful: savings_goal column added/exists.";
}
catch (Exception $e) {
    echo "Migration error: " . $e->getMessage();
}
?>
