<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

include 'db.php';
$user = $_SESSION['user'];

// Add handlers for appointment deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_appointment'])) {
        $stmt = $pdo->prepare('DELETE FROM appointments WHERE id = ? AND user_id = ?');
        $stmt->execute([$_POST['appointment_id'], $user['id']]);
        header("Location: booking_calendar.php");
        exit();
    }
    
    if (isset($_POST['delete_all'])) {
        $stmt = $pdo->prepare('DELETE FROM appointments WHERE user_id = ?');
        $stmt->execute([$user['id']]);
        header("Location: booking_calendar.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $time = $_POST['time'];
    $user_id = $user['id'];
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    $response = ['success' => false, 'message' => ''];

    // Check for the most recent appointment status
    $stmt = $pdo->prepare('
        SELECT * FROM appointments 
        WHERE appointment_date = ? 
        AND appointment_time = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ');
    $stmt->execute([$date, $time]);
    $existing = $stmt->fetch();

    if (!$existing || $existing['status'] === 'rejected') {
        // Create new appointment regardless of existing rejected ones
        $stmt = $pdo->prepare('
            INSERT INTO appointments 
            (user_id, appointment_date, appointment_time, notes, status, created_at) 
            VALUES (?, ?, ?, ?, "pending", CURRENT_TIMESTAMP)
        ');
        $stmt->execute([$user_id, $date, $time, $notes]);
        
        $response['success'] = true;
        $response['message'] = "Appointment booked successfully!";
    } else {
        $response['message'] = "This time slot is already booked!";
    }

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Generate time slots
function generateTimeSlots() {
    $slots = [];
    $start = strtotime('09:00');
    $end = strtotime('17:00');
    $interval = 30 * 60; // 30 minutes in seconds

    for ($time = $start; $time <= $end; $time += $interval) {
        $slots[] = date('H:i', $time);
    }
    return $slots;
}

$timeSlots = generateTimeSlots();

// Get current month/year if not specified
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Ensure we're not showing past months
if ($year < date('Y') || ($year == date('Y') && $month < date('n'))) {
    $month = date('n');
    $year = date('Y');
}

function getNextMonth($month, $year) {
    return $month == 12 ? [1, $year + 1] : [$month + 1, $year];
}

function getPrevMonth($month, $year) {
    $currentMonth = date('n');
    $currentYear = date('Y');
    
    $prevMonth = ($month == 1) ? 12 : $month - 1;
    $prevYear = ($month == 1) ? $year - 1 : $year;
    
    return ($prevYear < $currentYear || ($prevYear == $currentYear && $prevMonth < $currentMonth)) 
           ? false 
           : [$prevMonth, $prevYear];
}

function buildCalendar($month, $year) {
    $firstDay = mktime(0,0,0,$month,1,$year);
    $numberDays = date('t',$firstDay);
    $dateComponents = getdate($firstDay);
    $monthName = $dateComponents['month'];
    $dayOfWeek = $dateComponents['wday'];
    
    // Navigation
    $next = getNextMonth($month, $year);
    $prev = getPrevMonth($month, $year);
    
    $calendar = "<div class='calendar'>";
    
    // Add navigation
    $calendar .= "<div class='calendar-nav'>";
    if ($prev) {
        $calendar .= "<a href='?month={$prev[0]}&year={$prev[1]}'>&lt; Previous</a>";
    }
    $calendar .= " <span class='current-month'>$monthName $year</span> ";
    $calendar .= "<a href='?month={$next[0]}&year={$next[1]}'>Next &gt;</a>";
    $calendar .= "</div>";
    
    $calendar .= "<div class='calendar-grid'>";
    $daysOfWeek = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    
    foreach($daysOfWeek as $day) {
        $calendar .= "<div class='calendar-day-header'>$day</div>";
    }
    
    if ($dayOfWeek > 0) { 
        for($k=0;$k<$dayOfWeek;$k++){
            $calendar .= "<div class='calendar-day empty'></div>";
        }
    }
    
    for($i=1;$i<=$numberDays;$i++) {
        $currentDate = sprintf("%04d-%02d-%02d", $year, $month, $i);
        $class = 'calendar-day';
        if($i == date('d') && $month == date('m') && $year == date('Y')) {
            $class .= ' today';
        }
        if(strtotime($currentDate) < strtotime(date('Y-m-d'))) {
            $class .= ' past';
        }
        $calendar .= "<div class='$class' data-date='$currentDate'>$i</div>";
    }
    
    $calendar .= "</div></div>";
    return $calendar;
}

// Add this PHP function after the other functions and before the HTML
function checkTimeSlotStatus($date, $time) {
    global $pdo;
    $stmt = $pdo->prepare('
        SELECT status 
        FROM appointments 
        WHERE appointment_date = ? 
        AND appointment_time = ?
        ORDER BY created_at DESC 
        LIMIT 1
    ');
    $stmt->execute([$date, $time]);
    $result = $stmt->fetch(PDO::FETCH_COLUMN);
    
    return ($result && $result !== 'rejected') ? $result : 'available';
}

// Add this PHP endpoint before the HTML
if (isset($_GET['check_status'])) {
    $date = $_GET['date'];
    $time = $_GET['time'];
    echo checkTimeSlotStatus($date, $time);
    exit;
}

// Generate calendar with current month/year
$calendar = buildCalendar($month, $year);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
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
            min-height: 100vh;  /* change from height: 100vh to min-height: 100vh */
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
            width: calc(100% - 240px);
            margin-bottom: 0.01px; 
        }

        .booking-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        select, input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        button {
            background-color: #2c3e50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }

        button:hover {
            background-color: #1abc9c;
        }

        .error-message {
            color: red;
            margin-bottom: 10px;
        }
        .calendar {
            max-width: none; /* remove max-width restriction */
            margin: 0;
            background: #2c3e50;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            padding: 20px;
            color: #fff;
        }

        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 10px 0;
        }

        .calendar-nav a {
            color: #fff;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
            background: #34495e;
            font-weight: 500;
        }

        .calendar-nav a:hover {
            background: #1abc9c;
            color: white;
        }

        .current-month {
            font-size: 28px;
            font-weight: bold;
            color: #fff;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            padding: 10px;
        }

        .calendar-day-header {
            background: #34495e;
            color: #fff;
            padding: 15px 10px;
            text-align: center;
            font-weight: 600;
            border-radius: 8px;
            font-size: 14px;
        }

        .calendar-day {
            background: #34495e;
            border: 1px solid #415b76;
            border-radius: 8px;
            padding: 15px;
            min-height: 100px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            position: relative;
            color: #fff;
        }

        .calendar-day:not(.past):not(.empty):hover {
            background: #1abc9c;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            cursor: pointer;
            border-color: #1abc9c;
        }

        .calendar-day.today {
            background: #1abc9c;
            border: 2px solid #fff;
            font-weight: bold;
        }

        .calendar-day.past {
            background: #243442;
            color: #7f8c8d;
            cursor: not-allowed;
            border-color: #2c3e50;
        }

        .calendar-day.empty {
            background: transparent;
            border: none;
        }

        .time-slot-modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            z-index: 1000;
            max-width: 600px;
            width: 90%;
        }

        .time-slot-modal h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 24px;
            text-align: center;
        }

        .time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
            max-height: 400px;
            overflow-y: auto;
            padding: 10px;
        }

        .time-slot {
            padding: 12px;
            text-align: center;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 16px;
        }

        .time-slot:hover {
            background: #2c3e50;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .modal-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            z-index: 999;
            backdrop-filter: blur(4px);
            transition: all 0.3s ease;
        }

        /* Add scrollbar styling */
        .time-slots::-webkit-scrollbar {
            width: 8px;
        }

        .time-slots::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .time-slots::-webkit-scrollbar-thumb {
            background: #2c3e50;
            border-radius: 4px;
        }

        .time-slots::-webkit-scrollbar-thumb:hover {
            background: #1abc9c;
        }

        .services-container,
        .services-title,
        .services-grid,
        .service-card,
        .price-list,
        .service-details,
        .additional-services {
            /* Delete these style blocks entirely */
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .calendar-section {
            min-width: 0;
            height: fit-content;
        }

        .calendar {
            max-width: none;
            height: 800px; /* Match appointments-section max-height */
            margin: 0;
            background: #2c3e50;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            padding: 20px;
            color: #fff;
            display: flex;
            flex-direction: column;
        }

        .calendar-grid {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            padding: 10px;
            overflow-y: auto;
        }

        .calendar-day {
            min-height: 80px; /* Adjusted for better fit */
            height: auto;
        }

        .appointments-section {
            background: #2c3e50;
            padding: 20px;
            border-radius: 15px;
            color: white;
            max-height: 800px;
            overflow-y: auto;
        }
        /* Add these new styles after your existing styles */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px; /* increased from 5px to 20px */
            margin-bottom: 30px;
        }

        .calendar-section {
            min-width: 0; /* Prevents calendar overflow */
        }

        .appointments-section {
            background: #2c3e50;
            padding: 20px;
            border-radius: 15px;
            color: white;
            max-height: 800px;
            overflow-y: auto;
        }

        .appointments-section h2 {
            margin-bottom: 20px;
            font-size: 24px;
            text-align: center;
        }

        .appointment-card {
            background: #34495e;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            border-left: 5px solid transparent;
        }

        .appointment-card.approved {
            border-left-color: #2ecc71;
        }

        .appointment-card.rejected {
            border-left-color: #e74c3c;
        }

        .appointment-card.pending {
            border-left-color: #f1c40f;
        }

        .appointment-details {
            margin-bottom: 8px;
            color: #ecf0f1;
        }

        .status-approved {
            color: #2ecc71;
        }

        .status-rejected {
            color: #e74c3c;
        }

        .status-pending {
            color: #f1c40f;
        }

        .delete-btn {
            background-color: #e74c3c;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.3s;
        }

        .delete-btn:hover {
            background-color: #a12d23;
        }

        .delete-all-btn {
            background-color: #e74c3c;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 20px;
            width: 100%;
            transition: background-color 0.3s;
        }

        .delete-all-btn:hover {
            background-color: #a12d23;
        }

        .no-appointments {
            text-align: center;
            color: #ecf0f1;
            padding: 20px;
        }

        .rejection-reason {
            background: rgba(231, 76, 60, 0.2);
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }

        /* Adjust the main content width to fit both columns */
        .main-content {
            max-width: 100%;
            padding: 30px;
        }

        /* Make the services container full width */
        .services-container {
            margin-top: 30px;
            width: 100%;
        }

        .message-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .message {
            padding: 15px 25px;
            margin-bottom: 10px;
            border-radius: 5px;
            color: white;
            opacity: 0;
            transform: translateX(100%);
            animation: slideIn 0.5s forwards;
        }

        .message.success {
            background-color: #2ecc71;
        }

        .message.error {
            background-color: #e74c3c;
        }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .message.fade-out {
            animation: fadeOut 0.5s forwards;
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }

        .submit-btn {
            margin-top: 20px;
            background-color: #2c3e50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .submit-btn:hover:not(:disabled) {
            background-color: #1abc9c;
        }

        .submit-btn:disabled {
            background-color: #95a5a6;
            cursor: not-allowed;
        }

        .time-slot.selected {
            background-color: #1abc9c !important;
            color: white !important;
            border: 2px solid #fff;
        }

        .notes-container {
            margin: 20px 0;
        }

        #appointmentNotes {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            resize: vertical;
            font-family: inherit;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .char-counter {
            text-align: right;
            font-size: 12px;
            color: #666;
        }

        .appointment-details.notes {
            white-space: pre-line;
            margin-top: 10px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
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
            <li><a href="booking_calendar.php" class="active"><i class="fas fa-calendar-alt"></i>Book Pet Grooming</a></li>
            <li><a href="book_pet_boarding.php"><i class="fas fa-hotel"></i>Book Pet Hotel</a></li>
            <li><a href="services.php"><i class="fas fa-list"></i>Services & Prices</a></li>
            <li><a href="feedback.php"><i class="fas fa-comments"></i>Feedback & Reviews</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div id="messageContainer" class="message-container"></div>
        <div class="content-grid">
            <div class="calendar-section">
                <?php
                    $month = isset($_GET['month']) ? $_GET['month'] : date('m');
                    $year = isset($_GET['year']) ? $_GET['year'] : date('Y');
                    echo buildCalendar($month, $year);
                ?>
            </div>
            
            <div class="appointments-section">
                <h2>My Appointments</h2>
                <?php
                // Fetch appointments
                $stmt = $pdo->prepare('
                    SELECT * FROM appointments 
                    WHERE user_id = ? 
                    ORDER BY appointment_date DESC, appointment_time DESC
                ');
                $stmt->execute([$user['id']]);
                $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <?php if (count($appointments) > 0): ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete all appointments?');">
                        <button type="submit" name="delete_all" class="delete-all-btn">Delete All Appointments</button>
                    </form>
                    <div class="appointments-container">
                        <?php foreach ($appointments as $appointment): ?>
                            <div class="appointment-card <?php echo $appointment['status']; ?>">
                                <div class="appointment-details"><strong>Date:</strong> <?php echo htmlspecialchars($appointment['appointment_date']); ?></div>
                                <div class="appointment-details"><strong>Time:</strong> <?php echo htmlspecialchars($appointment['appointment_time']); ?></div>
                                <div class="appointment-details"><strong>Status:</strong> <span class="status-<?php echo $appointment['status']; ?>"><?php echo ucfirst(htmlspecialchars($appointment['status'])); ?></span></div>
                                <?php if (!empty($appointment['notes'])): ?>
                                    <div class="appointment-details notes"><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($appointment['notes'])); ?></div>
                                <?php endif; ?>
                                <?php if ($appointment['status'] === 'rejected' && !empty($appointment['rejection_reason'])): ?>
                                    <div class="appointment-details rejection-reason">
                                        <strong>Rejection Reason:</strong> <?php echo htmlspecialchars($appointment['rejection_reason']); ?>
                                    </div>
                                <?php endif; ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this appointment?');">
                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                    <button type="submit" name="delete_appointment" class="delete-btn">Delete</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-appointments">You have no appointments at this time.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="time-slot-modal" id="timeSlotModal">
        <h3>Select Time Slot</h3>
        <div class="time-slots" id="timeSlots"></div>
        <div class="notes-container">
            <textarea id="appointmentNotes" 
                      placeholder="Add any notes or special requests..."
                      rows="3"
                      maxlength="500"></textarea>
            <div class="char-counter">0/500</div>
        </div>
        <button id="submitAppointment" class="submit-btn" disabled>Book Appointment</button>
    </div>
    <div class="modal-backdrop" id="modalBackdrop"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('timeSlotModal');
    const backdrop = document.getElementById('modalBackdrop');
    const timeSlotsContainer = document.getElementById('timeSlots');
    let selectedDate = null;
    let selectedTime = null;
    const submitButton = document.getElementById('submitAppointment');
    const notesTextarea = document.getElementById('appointmentNotes');
    const charCounter = document.querySelector('.char-counter');
    
    document.querySelectorAll('.calendar-day:not(.past):not(.empty)').forEach(day => {
        day.addEventListener('click', function() {
            const date = this.dataset.date;
            showTimeSlots(date);
        });
    });
    
    backdrop.addEventListener('click', closeModal);
    
    function showTimeSlots(date) {
        selectedDate = date;
        let timeSlots = generateTimeSlots();
        timeSlotsContainer.innerHTML = '';
        
        const now = new Date();
        const selectedDateObj = new Date(date); // renamed variable to avoid shadowing
        const isToday = selectedDateObj.toDateString() === now.toDateString();
        
        timeSlots.forEach(time => {
            const [hours, minutes] = time.split(':');
            const slotTime = new Date(selectedDateObj); // use the renamed variable
            slotTime.setHours(parseInt(hours), parseInt(minutes), 0);
            
            const slot = document.createElement('div');
            slot.textContent = time;
            
            if (isToday && slotTime <= now) {
                slot.className = 'time-slot past';
                slot.style.backgroundColor = '#f5f5f5';
                slot.style.color = '#adb5bd';
                slot.style.cursor = 'not-allowed';
            } else {
                slot.className = 'time-slot';
                // Check slot status
                fetch(`booking_calendar.php?check_status=1&date=${date}&time=${time}`)
                    .then(response => response.text())
                    .then(status => {
                        if (status === 'pending') {
                            slot.style.backgroundColor = '#FFD700';
                            slot.style.color = '#000000';
                            slot.style.cursor = 'not-allowed';
                        } else if (status === 'approved' || status === 'booked') {
                            slot.style.backgroundColor = '#FF9999';
                            slot.style.color = '#000000';
                            slot.style.cursor = 'not-allowed';
                        } else if (status === 'rejected' || status === 'available') {
                            slot.addEventListener('click', () => selectTimeSlot(slot, time));
                        }
                    });
            }
            
            timeSlotsContainer.appendChild(slot);
        });
        
        modal.style.display = 'block';
        backdrop.style.display = 'block';
    }
    
    submitButton.addEventListener('click', () => {
        if (selectedDate && selectedTime) {
            bookAppointment(selectedDate, selectedTime);
        }
    });

    function selectTimeSlot(slotElement, time) {
        // Remove selected class from all slots
        document.querySelectorAll('.time-slot').forEach(slot => {
            slot.classList.remove('selected');
        });
        
        // Add selected class to clicked slot
        slotElement.classList.add('selected');
        selectedTime = time;
        
        // Enable submit button
        submitButton.disabled = false;
    }

    function generateTimeSlots() {
        const slots = [];
        const start = 9;
        const end = 17;
        
        for(let hour = start; hour < end; hour++) {
            slots.push(`${hour}:00`);
            slots.push(`${hour}:30`);
        }
        
        return slots;
    }
    
    function bookAppointment(date, time) {
        const formData = new FormData();
        formData.append('date', date);
        formData.append('time', time);
        formData.append('notes', document.getElementById('appointmentNotes').value);

        fetch('booking_calendar.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            showMessage(data.message, data.success);
            if (data.success) {
                closeModal();
                location.reload();
            }
        })
        .catch(error => {
            showMessage('An error occurred while booking the appointment.', false);
        });
    }

    function showMessage(message, isSuccess) {
        const messageContainer = document.getElementById('messageContainer');
        const messageElement = document.createElement('div');
        messageElement.className = `message ${isSuccess ? 'success' : 'error'}`;
        messageElement.textContent = message;
        messageContainer.appendChild(messageElement);

        setTimeout(() => {
            messageElement.classList.add('fade-out');
            setTimeout(() => {
                messageContainer.removeChild(messageElement);
            }, 500);
        }, 3000);
    }
    
    function closeModal() {
        modal.style.display = 'none';
        backdrop.style.display = 'none';
        selectedDate = null;
        selectedTime = null;
        submitButton.disabled = true;
        document.getElementById('appointmentNotes').value = '';
        document.querySelector('.char-counter').textContent = '0/500';
    }

    notesTextarea.addEventListener('input', function() {
        const count = this.value.length;
        charCounter.textContent = `${count}/500`;
    });
});
</script>

</body>
</html>

