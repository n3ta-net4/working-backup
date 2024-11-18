
<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT image_path FROM feedback WHERE id = ?");
        $stmt->execute([$_POST['feedback_id']]);
        $feedback = $stmt->fetch();


        if ($feedback && !empty($feedback['image_path']) && file_exists($feedback['image_path'])) {
            unlink($feedback['image_path']);
        }


        $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = ?");
        $stmt->execute([$_POST['feedback_id']]);

        $_SESSION['success'] = "Feedback deleted successfully.";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error deleting feedback: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: admin_feedback.php");
exit();