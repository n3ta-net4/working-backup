<?php
if(!isset($_SESSION)) {
    session_start();
}

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include "db.php";

// Handle appointment deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $appointmentId = $_POST['appointment_id'];
    $stmt = $pdo->prepare('DELETE FROM appointments WHERE id = ?');
    $stmt->execute([$appointmentId]);
    $_SESSION['success'] = "Appointment deleted successfully.";
    header("Location: admin_manage_appointments.php");
    exit();
}

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$date = isset($_GET['date']) ? $_GET['date'] : '';

// Build the query based on filters
$query = "SELECT a.*, u.name as user_name, u.email, a.notes 
          FROM appointments a 
          JOIN users u ON a.user_id = u.id 
          WHERE a.status != 'pending'";  // Exclude pending appointments
$params = [];

if ($status !== 'all') {
    $query .= " AND a.status = ?";
    $params[] = $status;
}

if ($date) {
    $query .= " AND a.appointment_date = ?";
    $params[] = $date;
}

$query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage All Appointments</title>
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

        /* Sidebar styles from admin_appointments.php */
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
        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: #1abc9c;
        }
        
        /* Main content adjustment */
        .main-content {
            margin-left: 240px;
            padding: 30px;
            width: calc(100% - 240px);
            background-color: #fff;
            overflow-y: auto;
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

        .no-appointments {
            text-align: center;
            color: #2c3e50;
            padding: 20px;
            background: #ecf0f1;
            border-radius: 8px;
            margin-top: 20px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
        }

        .status-approved { background: #2ecc71; }
        .status-rejected { background: #e74c3c; }
        .status-pending { background: #f1c40f; color: #2c3e50; }

        .delete-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }

        .delete-btn:hover {
            background: #c0392b;
        }
        hr {
            border: 0;
            height: 1px;
            background: #fff;
            margin: 10px 0;
        }

        .appointment-card {
            background: #2c3e50;
            padding: 25px;
            margin: 15px 0;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
            border-left: 4px solid #3498db;
            color: white;
        }

        .appointment-card:hover {
            transform: translateY(-2px);
        }

        .appointment-info {
            margin-bottom: 20px;
        }

        .appointment-info p {
            margin: 8px 0;
            color: #ecf0f1;
            font-size: 15px;
        }

        .info-label {
            font-weight: bold;
            color: #ecf0f1;
            margin-right: 10px;
            width: 80px;
            display: inline-block;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            font-size: 14px;
        }

        .delete-btn {
            background-color: #e74c3c;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            font-size: 14px;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }

        .appointment-card h3 {
            color: #ffffff !important;
            margin-bottom: 15px;
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
        
        .rejection-reason {
            background: rgba(231, 76, 60, 0.2);
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
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
            <li><a href="admin_manage_appointments.php" class="active"><i class="fas fa-calendar"></i> Manage Appointments</a></li>
            <li><a href="admin_manage_reservations.php"><i class="fas fa-hotel"></i> Manage Reservations</a></li>
            <li><a href="admin_services.php"><i class="fas fa-bone"></i> Manage Services</a></li>
            <li><a href="admin_feedback.php"><i class="fas fa-comments"></i> Manage Feedback</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h1>Manage All Appointments</h1>
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

                <label for="date">Date:</label>
                <input type="date" name="date" id="date" value="<?= htmlspecialchars($date) ?>">

                <button type="submit" class="filter-btn">Apply Filters</button>
            </form>
        </div>

        <?php foreach($appointments as $appointment): ?>
            <div class="appointment-card">
                <div class="appointment-info">
                    <h3>Appointment Details</h3>
                    <p><span class="info-label">Client:</span><?= htmlspecialchars($appointment['user_name']) ?></p>
                    <p><span class="info-label">Email:</span><?= htmlspecialchars($appointment['email']) ?></p>
                    <p><span class="info-label">Date:</span><?= htmlspecialchars($appointment['appointment_date']) ?></p>
                    <p><span class="info-label">Time:</span><?= htmlspecialchars($appointment['appointment_time']) ?></p>
                    <p>
                        <span class="info-label">Status:</span>
                        <span class="status-badge status-<?= $appointment['status'] ?>">
                            <?= ucfirst(htmlspecialchars($appointment['status'])) ?>
                        </span>
                    </p>
                    <?php if (!empty($appointment['notes'])): ?>
                        <p><span class="info-label">Notes:</span><?= htmlspecialchars($appointment['notes']) ?></p>
                    <?php endif; ?>
                    <?php if ($appointment['status'] === 'rejected' && !empty($appointment['rejection_reason'])): ?>
                        <div class="rejection-reason">
                            <span class="info-label">Rejection Reason:</span><?= htmlspecialchars($appointment['rejection_reason']) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="button-group">
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this appointment?');">
                        <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                        <button type="submit" name="delete" class="delete-btn">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if(empty($appointments)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <p>No appointments found matching the selected criteria.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
