<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $booking_id = $_POST['booking_id'];
    $stmt = $pdo->prepare("DELETE FROM pet_boarding WHERE id = ?");
    $stmt->execute([$booking_id]);
    $_SESSION['success'] = "Reservation deleted successfully.";
    header("Location: admin_manage_reservations.php");
    exit();
}

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build query based on filter
$query = "
    SELECT pb.id, pb.pet_name, pb.pet_type, pb.status, pb.notes,
           DATE(pb.check_in) as check_in, 
           DATE(pb.check_out) as check_out, 
           pb.rejection_reason,
           a.type, a.number, u.name as client_name 
    FROM pet_boarding pb 
    JOIN accommodations a ON pb.accommodation_id = a.id 
    JOIN users u ON pb.user_id = u.id 
    WHERE pb.status != 'pending'";

$params = [];

if ($status !== 'all') {
    $query .= " AND pb.status = ?";
    $params[] = $status;
}

if ($start_date) {
    $query .= " AND DATE(pb.check_in) >= ?";
    $params[] = $start_date;
}

if ($end_date) {
    $query .= " AND DATE(pb.check_in) <= ?";
    $params[] = $end_date;
}

$query .= " ORDER BY pb.check_in DESC";

$stmt = empty($params) ? 
    $pdo->query($query) : 
    $pdo->prepare($query);

if (!empty($params)) {
    $stmt->execute($params);
}

$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage All Reservations</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .sidebar h2 {
            color: #ecf0f1;
            margin-bottom: 20px;
            text-align: center;
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
        hr {
            border: 0;
            height: 1px;
            background: #fff;
            margin: 10px 0;
        }
        .main-content {
            margin-left: 240px;
            padding: 30px;
            width: calc(100% - 240px);
            background-color: #fff;
            overflow-y: auto;
        }
        .filter-section {
            background: #2c3e50;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .filter-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            margin-right: 10px;
            transition: all 0.2s ease;
            background-color: #34495e;
            color: white;
        }

        .filter-btn:hover {
            background-color: #1abc9c;
        }

        .filter-btn.active {
            background-color: #1abc9c;
        }

        .reservation-card {
            background: #2c3e50;
            padding: 25px;
            margin: 15px 0;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
            border-left: 4px solid #3498db;
            color: white;
        }

        .reservation-card:hover {
            transform: translateY(-2px);
        }

        .reservation-info {
            margin-bottom: 20px;
        }

        .reservation-info p {
            margin: 8px 0;
            color: #ecf0f1;
            font-size: 15px;
        }

        .info-label {
            font-weight: bold;
            color: #ecf0f1;
            margin-right: 10px;
            width: 120px;
            display: inline-block;
        }

        .delete-btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            background-color: #e74c3c;
            color: white;
            transition: all 0.2s ease;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
            color: white;
        }

        .status-approved {
            background: #2ecc71;
        }

        .status-rejected {
            background: #e74c3c;
        }  
        .rejection-reason {
            background: rgba(231, 76, 60, 0.2);
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }

        @media (max-width: 600px) {
            .main-content {
                margin-left: 0;
            }
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #cccccc;
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

        .btn-logout {
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

        .btn-logout:hover {
            background-color: #c0392b;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 12px;
            margin: 20px 0;
            border: 2px dashed #dee2e6;
        }
        .empty-state i {
            font-size: 48px;
            color: #6c757d;
            margin-bottom: 15px;
        }
        .empty-state p {
            font-size: 18px;
            color: #495057;
            margin: 0;
        }
        .filters {
            background: #2c3e50;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .filter-group label {
            font-weight: bold;
            color: #ecf0f1;
        }

        .filter-group select, 
        .filter-group input {
            padding: 10px;
            border: 1px solid #415b76;
            border-radius: 6px;
            font-size: 14px;
            background: #34495e;
            color: #ecf0f1;
        }

        .filter-btn {
            background: #1abc9c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s;
            font-weight: 600;
        }

        .filter-btn:hover {
            background: #16a085;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <a href="admin_dashboard.php">
                <img src="aw-k9.png" alt="aw-k9 logo">
            </a>
        </div>
        <h2>Admin Dashboard</h2>
        <hr>
        <ul>
            <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="admin_appointments.php"><i class="fas fa-calendar-check"></i> Pending Appointments</a></li>
            <li><a href="admin_bookings.php"><i class="fas fa-paw"></i> Pending Reservations</a></li>
            <li><a href="admin_manage_appointments.php"><i class="fas fa-calendar"></i> Manage Appointments</a></li>
            <li><a href="admin_manage_reservations.php" class="active"><i class="fas fa-hotel"></i> Manage Reservations</a></li>
            <li><a href="admin_services.php"><i class="fas fa-bone"></i> Manage Services</a></li>
            <li><a href="admin_feedback.php"><i class="fas fa-comments"></i> Manage Feedback</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h1>Manage All Reservations</h1>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <div class="filters">
            <form method="GET" class="filter-group">
                <label for="status">Status:</label>
                <select name="status" id="status">
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>

                <label for="start_date">Start Date:</label>
                <input type="date" name="start_date" id="start_date" value="<?= htmlspecialchars($start_date) ?>">

                <label for="end_date">End Date:</label>
                <input type="date" name="end_date" id="end_date" value="<?= htmlspecialchars($end_date) ?>">

                <button type="submit" class="filter-btn">Apply Filters</button>
            </form>
        </div>

        <?php foreach ($bookings as $booking): ?>
            <div class="reservation-card">
                <div class="reservation-info">
                    <h3>Reservation Details</h3>
                    <p><span class="info-label">Client:</span> <?= htmlspecialchars($booking['client_name']) ?></p>
                    <p><span class="info-label">Pet Name:</span> <?= htmlspecialchars($booking['pet_name']) ?></p>
                    <p><span class="info-label">Pet Type:</span> <?= htmlspecialchars($booking['pet_type']) ?></p>
                    <p><span class="info-label">Accommodation:</span> <?= htmlspecialchars($booking['type']) ?> <?= htmlspecialchars($booking['number']) ?></p>
                    <p><span class="info-label">Check In:</span> <?= htmlspecialchars($booking['check_in']) ?></p>
                    <p><span class="info-label">Check Out:</span> <?= htmlspecialchars($booking['check_out']) ?></p>
                    <p><span class="info-label">Status:</span> 
                        <span class="status-badge status-<?= $booking['status'] ?>">
                            <?= ucfirst(htmlspecialchars($booking['status'])) ?>
                        </span>
                    </p>
                    <?php if (!empty($booking['notes'])): ?>
                        <p><span class="info-label">Notes:</span> <?= htmlspecialchars($booking['notes']) ?></p>
                    <?php endif; ?>
                    <?php if ($booking['status'] === 'rejected' && !empty($booking['rejection_reason'])): ?>
                        <div class="rejection-reason">
                            <span class="info-label">Rejection Reason:</span> <?= htmlspecialchars($booking['rejection_reason']) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this reservation?');">
                    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                    <button type="submit" name="delete" class="delete-btn">
                        <i class="fas fa-trash"></i> Delete Reservation
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
        
        <?php if(empty($bookings)): ?>
            <div class="empty-state">
                <i class="fas fa-hotel"></i>
                <p>No reservations found matching the selected criteria.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function confirmDelete() {
            return confirm('Are you sure you want to delete this reservation?');
        }
    </script>
</body>
</html>

