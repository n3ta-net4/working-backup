<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

// Validate form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    $userId = $_SESSION['user']['id'];
    $userName = $_SESSION['user']['name'];
    $imagePath = null;
    
    // Validate services
    $services = isset($_POST['services']) ? $_POST['services'] : [];
    if (empty($services)) {
        $_SESSION['error'] = "Please select at least one service to review.";
        header("Location: feedback.php");
        exit();
    }
    
    // Convert services array to JSON
    $servicesJson = json_encode($services);

    // Handle image upload
    if (isset($_FILES['review_image']) && $_FILES['review_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/reviews/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Validate file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['review_image']['type'], $allowedTypes)) {
            $_SESSION['error'] = "Invalid file type. Please upload JPG, PNG or GIF.";
            header("Location: feedback.php");
            exit();
        }

        if ($_FILES['review_image']['size'] > $maxSize) {
            $_SESSION['error'] = "File too large. Maximum size is 5MB.";
            header("Location: feedback.php");
            exit();
        }

        // Generate unique filename
        $fileName = uniqid('review_') . '_' . time() . '_' . 
                   basename($_FILES['review_image']['name']);
        $targetFile = $uploadDir . $fileName;

        // Upload file
        if (move_uploaded_file($_FILES['review_image']['tmp_name'], $targetFile)) {
            $imagePath = $targetFile;
        } else {
            $_SESSION['error'] = "Error uploading file.";
            header("Location: feedback.php");
            exit();
        }
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO feedback (user_id, user_name, rating, comment, services, image_path, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$userId, $userName, $rating, $comment, $servicesJson, $imagePath]);
        $_SESSION['success'] = "Thank you for your feedback!";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error submitting feedback: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid request method";
}

header("Location: feedback.php");
exit();