<?php
require 'config/database.php';
$stmt = $pdo->query("DESCRIBE users");
print_r($stmt->fetchAll());
?>
