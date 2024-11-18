<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user'])) {
    exit(json_encode([]));
}

try {
    if (empty($_POST['check_in']) || empty($_POST['check_out'])) {
        $stmt = $pdo->prepare("
            SELECT DISTINCT number 
            FROM accommodations 
            WHERE type = ? 
            ORDER BY number
        ");
        $stmt->execute([$_POST['type']]);
    } else {
        $stmt = $pdo->prepare("
            SELECT DISTINCT a.number
            FROM accommodations a 
            LEFT JOIN pet_boarding pb ON a.id = pb.accommodation_id 
                AND pb.status != 'rejected'
                AND (
                    (pb.check_in <= ? AND pb.check_out >= ?) OR
                    (pb.check_in <= ? AND pb.check_out >= ?) OR
                    (pb.check_in >= ? AND pb.check_out <= ?)
                )
            WHERE a.type = ?
            GROUP BY a.number
            HAVING COUNT(pb.id) = 0
            ORDER BY a.number
        ");

        $stmt->execute([
            $_POST['check_in'],
            $_POST['check_in'],
            $_POST['check_out'],
            $_POST['check_out'],
            $_POST['check_in'],
            $_POST['check_out'],
            $_POST['type']
        ]);
    }

    $available_numbers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    header('Content-Type: application/json');
    echo json_encode($available_numbers);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([]);
}