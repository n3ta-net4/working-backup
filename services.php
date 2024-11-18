<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

include 'db.php';
$user = $_SESSION['user'];

// Fetch services from database
$stmt = $pdo->query("SELECT * FROM grooming_services ORDER BY category, service_name");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group services by category
$groupedServices = [];
foreach ($services as $service) {
    $groupedServices[$service['category']][] = $service;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grooming Services</title>
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
            min-height: 100vh;
            background-color: #f5f7fa;
        }

        /* Copy sidebar styles from booking_calendar.php */
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
            width: calc(100% - 240px);
        }

        /* Updated services styles to match booking_calendar.php theme */
        .services-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .services-category {
            background: #2c3e50;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .category-title {
            color: #fff;
            font-size: 28px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1abc9c;
            text-align: center;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .service-card {
            background: #34495e;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid #415b76;
        }

        .service-card:hover {
            transform: translateY(-5px);
            background: #2c3e50;
            border-color: #1abc9c;
        }

        .service-name {
            color: #fff;
            font-size: 20px;
            margin-bottom: 15px;
            text-align: center;
        }

        .price-list {
            list-style: none;
            padding: 0;
        }

        .price-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px dashed #415b76;
        }

        .price-label {
            color: #ecf0f1;
        }

        .price-value {
            color: #1abc9c;
            font-weight: bold;
        }

        .service-description {
            margin-top: 15px;
            color: #bdc3c7;
            font-size: 14px;
            line-height: 1.5;
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
            <li><a href="user_dashboard.php"><i class="fas fa-home"></i>Dashboard</a></li>
            <li><a href="booking_calendar.php"><i class="fas fa-calendar-alt"></i>Book Pet Grooming</a></li>
            <li><a href="book_pet_boarding.php"><i class="fas fa-hotel"></i>Book Pet Hotel</a></li>
            <li><a href="services.php" class="active"><i class="fas fa-list"></i>Services & Prices</a></li>
            <li><a href="feedback.php"><i class="fas fa-comments"></i>Feedback & Reviews</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="services-container">
            <?php foreach ($groupedServices as $category => $categoryServices): ?>
            <div class="services-category">
                <h2 class="category-title"><?php echo htmlspecialchars($category); ?> Services</h2>
                <div class="services-grid">
                    <?php foreach ($categoryServices as $service): ?>
                    <div class="service-card">
                        <h3 class="service-name"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                        <ul class="price-list">
                            <?php if (isset($service['fixed_price'])): ?>
                                <li class="price-item">
                                    <span class="price-label">Price</span>
                                    <span class="price-value">₱<?php echo number_format($service['fixed_price'], 2); ?></span>
                                </li>
                            <?php else: ?>
                                <li class="price-item">
                                    <span class="price-label">Small</span>
                                    <span class="price-value">₱<?php echo number_format($service['small_price'], 2); ?></span>
                                </li>
                                <li class="price-item">
                                    <span class="price-label">Medium</span>
                                    <span class="price-value">₱<?php echo number_format($service['medium_price'], 2); ?></span>
                                </li>
                                <li class="price-item">
                                    <span class="price-label">Large</span>
                                    <span class="price-value">₱<?php echo number_format($service['large_price'], 2); ?></span>
                                </li>
                            <?php endif; ?>
                        </ul>
                        <?php if (isset($service['description'])): ?>
                            <p class="service-description"><?php echo htmlspecialchars($service['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>