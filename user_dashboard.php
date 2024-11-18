<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];

$notifications = [
    "Your booking was confirmed.",
    "New appointment available for booking.",
    "Reminder: Your appointment is tomorrow."
];

// Fetch real reviews from database
try {
    $stmt = $pdo->query("SELECT user_name, rating, comment, created_at, image_path, services FROM feedback ORDER BY created_at DESC LIMIT 5");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $reviews = [];
    $_SESSION['error'] = "Error fetching reviews: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Helvetica', Arial, sans-serif;
        }
        body {
            display: flex;
            height: 100vh;
            background-color: #f5f7fa;
        }
        .sidebar {
            width: 240px;
            background-color: #2c3e50;
            color: #fff;
            padding: 20px;
            position: fixed;
            height: 100%;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
        }
        .sidebar .logo {
            text-align: center;
            margin-bottom: 10px; 
        }
        .sidebar .logo img {
            width: 200px; 
            margin-bottom: 5px; 
            border-radius: 8px; 
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); 
        }
        .sidebar .user-details {
            text-align: center;
            margin-bottom: 10px;
        }
        .sidebar .user-details h2 {
            font-size: 20px; 
            margin-bottom: 3px;
            font-weight: bold;
        }
        .sidebar .user-details p {
            font-size: 20px;
            color: #ecf0f1;
        }
        .sidebar .divider {
            border-bottom: 1px solid #fff;
            margin: 10px 0;
        }
        .sidebar ul {
            list-style: none;
            padding-top: 10px; 
            flex-grow: 1; 
        }
        .sidebar ul li {
            margin-bottom: 15px; 
        }
        .sidebar ul li a {
            color: #fff;
            text-decoration: none;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 6px;
            transition: background-color 0.3s ease-in-out;
        }
        .sidebar ul li a:hover {
            background-color: #1abc9c;
        }
        .sidebar ul li a.active {
            background-color: #1abc9c;
        }
        .main-content {
            margin-left: 240px;
            padding: 30px;
            width: 100%;
            background-color: #fff;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #ecf0f1;
            padding: 15px 30px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        .top-bar h1 {
            font-size: 30px;
            font-weight: 600;
            color: #2c3e50;
        }
        .btn-logout, .btn-notifications {
            background-color: #e74c3c;
            padding: 10px 20px;
            color: #fff;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 20px;
            transition: background-color 0.3s ease-in-out;
            margin-left: 15px;
        }
        .btn-logout:hover, .btn-notifications:hover {
            background-color: #c0392b;
        }
        .notifications {
            position: absolute;
            top: 60px;
            right: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            display: none;
            z-index: 1000;
            width: 280px;
            padding: 10px;
        }
        .notification {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            font-size: 10px;
            color: #2c3e50;
        }
        .notification:last-child {
            border-bottom: none;
        }
        .notification:hover {
            background-color: #f1f1f1;
        }
        .show {
            display: block;
        }
        
        /* Add new styles for feedback section */
        .feedback-section {
            margin-top: 30px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        
        .feedback-form {
            max-width: 600px;
            margin-bottom: 30px;
        }
        
        .feedback-form textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .rating-input {
            margin: 10px 0;
        }
        
        .reviews-list {
            margin-top: 20px;
        }
        
        .review-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .star-rating {
            color: #f1c40f;
            margin: 5px 0;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">
        <a href="user_dashboard.php">
            <img src="aw-k9.png" alt="aw-k9 logo">
        </a>
    </div>
    <div class="user-details">
        <h2><?php echo htmlspecialchars($user['name']); ?></h2>
        <p><?php echo htmlspecialchars($user['email']); ?></p>
    </div>
    <div class="divider"></div>
    <ul>
        <li><a href="user_dashboard.php" class="active"><i class="fas fa-home"></i>Dashboard</a></li>
        <li><a href="booking_calendar.php"><i class="fas fa-calendar-alt"></i>Book Pet Grooming</a></li>
        <li><a href="book_pet_boarding.php"><i class="fas fa-hotel"></i>Book Pet Hotel</a></li>
        <li><a href="services.php"><i class="fas fa-list"></i>Services & Prices</a></li>
        <li><a href="feedback.php"><i class="fas fa-comments"></i>Feedback & Reviews</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="top-bar">
        <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>
        <div>
            <button class="btn-notifications" onclick="toggleNotifications()">Notifications</button>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error" style="background: #ffe6e6; color: #ff0000; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
            <?php 
            echo htmlspecialchars($_SESSION['error']); 
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success" style="background: #e6ffe6; color: #008000; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
            <?php 
            echo htmlspecialchars($_SESSION['success']); 
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <div class="content-section">
        <p>You are logged in as <strong><?php echo htmlspecialchars($user['email']); ?></strong>.</p>
    </div>

    <div class="notifications" id="notificationDropdown">
        <h3 style="padding: 10px 0;">Notifications</h3>
        <?php foreach ($notifications as $notification): ?>
            <div class="notification"><?php echo htmlspecialchars($notification); ?></div>
        <?php endforeach; ?>
    </div>
    
</div>

<script>
    function toggleNotifications() {
        const dropdown = document.getElementById('notificationDropdown');
        dropdown.classList.toggle('show');
    }

    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('notificationDropdown');
        const button = document.querySelector('.btn-notifications');
        if (!dropdown.contains(event.target) && !button.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    });
</script>

</body>
</html>
