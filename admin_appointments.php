<?php

if(!isset($_SESSION)){
    session_start();
}

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");exit();
}

include "db.php";

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a_id = $_POST['appointment_id'];
    $act = $_POST['action'];
    $newStatus = ($act==='approve')? 'approved':'rejected';
    
    if($act === 'reject') {
      $reason=$_POST['rejection_reason'];
        $q = $pdo->prepare('UPDATE appointments SET status = ?, rejection_reason = ? WHERE id = ?');
        $q->execute([$newStatus, $reason, $a_id]);
    } else{
        $q=$pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");  
        $q->execute([$newStatus, $a_id]);
    }
    
    header('Location: admin_appointments.php');exit();
}

$q = $pdo->prepare("SELECT a.*, u.name as user_name, u.email, a.notes 
    FROM appointments a JOIN users u ON a.user_id = u.id 
    WHERE a.status = 'pending' ORDER BY a.appointment_date,a.appointment_time");
$q->execute();
$appointments=$q->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments</title>
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
        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: #1abc9c;
        }
        .main-content {
            margin-left: 240px;
            padding: 30px;
            width: calc(100% - 240px);
            background-color: #fff;
            overflow-y: auto;
        }
        h2 {
            margin-bottom: 20px;
            color: #2c3e50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #2c3e50;
            color: #fff;
        }
        button {
            padding: 5px 10px;
            margin: 0 5px;
            border: none;
            border-radius: 5px;
            color: #fff;
            cursor: pointer;
        }
        .approve {
            background-color: #4caf50; 
        }
        .reject {
            background-color: #f44336; 
        }
        .approve:hover {
            background-color: #388e3c;
        }
        .reject:hover {
            background-color: #c62828;
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
        .approve-btn {
            background-color: #2ecc71;
            color: white;
        }
        .approve-btn:hover {
            background-color: #27ae60;
        }
        .reject-btn {
            background-color: #e74c3c;
            color: white;
        }
        .reject-btn:hover {
            background-color: #c0392b;
        }
        .cancel-btn {
            background-color: #7f8c8d;
            color: white;
        }
        .cancel-btn:hover {
            background-color: #95a5a6;
        }
        .icon {
            font-size: 16px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
        }

        .modal textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            border: 1px solid #ddd;
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
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        <li><a href="admin_appointments.php" class="active"><i class="fas fa-calendar-check"></i> Pending Appointments</a></li>
        <li><a href="admin_bookings.php"><i class="fas fa-paw"></i> Pending Reservations</a></li>
        <li><a href="admin_manage_appointments.php"><i class="fas fa-calendar"></i> Manage Appointments</a></li>
        <li><a href="admin_manage_reservations.php"><i class="fas fa-hotel"></i> Manage Reservations</a></li>
        <li><a href="admin_services.php"><i class="fas fa-bone"></i> Manage Services</a></li>
        <li><a href="admin_feedback.php"><i class="fas fa-comments"></i> Manage Feedback</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="top-bar">
        <h1>Manage Appointments</h1>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <hr>
    <?php foreach($appointments as $a): ?>
        <div class="appointment-card">
            <div class="appointment-info">
                <h3 style="color: #2c3e50;margin-bottom: 15px">Appointment Details</h3>
                <p><span class="info-label">Client:</span><?=htmlspecialchars($a['user_name'])?></p>
                <p><span class="info-label">Email:</span><?=htmlspecialchars($a['email'])?></p>
                <p><span class="info-label">Date:</span><?=htmlspecialchars($a['appointment_date'])?></p>
                <p><span class="info-label">Time:</span><?=htmlspecialchars($a['appointment_time'])?></p>
                <?php if (!empty($a['notes'])): ?>
                    <p><span class="info-label">Notes:</span><?=htmlspecialchars($a['notes'])?></p>
                <?php endif; ?>
            </div>
            <div class="button-group">
                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to approve this appointment?');">
                    <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn approve-btn">
                        <i class="fas fa-check icon"></i> Approve
                    </button>
                </form>
                <button type="button" class="btn reject-btn" onclick="openRejectModal(<?= $a['id'] ?>)">
                    <i class="fas fa-times icon"></i> Reject
                </button>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if(empty($appointments)): ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <p>No pending appointments at the moment.</p>
        </div>
    <?php endif ?>
</div>

<div id="rejectModal" class="modal">
    <div class="modal-content">
        <h3>Rejection Reason</h3>
        <form method="POST" id="rejectForm">
            <input type="hidden" name="appointment_id" id="modal_appointment_id">
            <input type="hidden" name="action" value="reject">
            <textarea name="rejection_reason" rows="4" required 
                placeholder="Please provide a reason for rejection"></textarea>
            <div class="button-group">
                <button type="submit" class="btn reject-btn">Submit</button>
                <button type="button" class="btn cancel-btn" onclick="closeRejectModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(aid) {
    var m = document.getElementById('rejectModal')
    m.style.display = 'block'
    document.getElementById('modal_appointment_id').value=aid
}

function closeRejectModal() {
  document.getElementById('rejectModal').style.display='none'
}

window.onclick = function(ev) {
    let m = document.getElementById('rejectModal')
    if(ev.target==m) {m.style.display="none"}
}
</script>
</body>
</html>
