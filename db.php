<?php
date_default_timezone_set('Asia/Manila');

try {
    $pdo = new PDO('mysql:host=localhost;dbname=capstone_db;timezone=Asia/Manila', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>