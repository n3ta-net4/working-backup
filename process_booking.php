<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            SELECT a.id 
            FROM accommodations a 
            LEFT JOIN pet_boarding pb ON a.id = pb.accommodation_id 
                AND pb.status != 'rejected'
                AND (
                    (pb.check_in <= ? AND pb.check_out >= ?) OR
                    (pb.check_in <= ? AND pb.check_out >= ?) OR
                    (pb.check_in >= ? AND pb.check_out <= ?)
                )
            WHERE a.type = ? AND a.number = ?
            HAVING COUNT(pb.id) = 0
        ");

        $stmt->execute([
            $_POST['check_in'],
            $_POST['check_in'],
            $_POST['check_out'],
            $_POST['check_out'],
            $_POST['check_in'],
            $_POST['check_out'],
            $_POST['accommodation_type'],
            $_POST['accommodation_number']
        ]);

        if ($accommodation = $stmt->fetch()) {
            // Insert the booking
            $bookingStmt = $pdo->prepare("
                INSERT INTO pet_boarding (
                    user_id, pet_name, pet_type, accommodation_id, check_in, check_out, status, notes
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)
            ");

            $bookingStmt->execute([
                $_SESSION['user']['id'],
                $_POST['pet_name'],
                $_POST['pet_type'],
                $accommodation['id'],
                $_POST['check_in'],
                $_POST['check_out'],
                $_POST['notes'] ?? ''
            ]);

            $_SESSION['booking_success'] = "Your pet boarding reservation was successful!";
            header("Location: book_pet_boarding.php");
        } else {
            header("Location: book_pet_boarding.php?error=unavailable");
        }
    } catch (PDOException $e) {
        header("Location: book_pet_boarding.php?error=database");
    }
    exit();
}

header("Location: book_pet_boarding.php");
exit();