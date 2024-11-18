<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

require_once 'db.php';
$user = $_SESSION['user'];

// Get available rooms and cages
$stmt = $pdo->query("SELECT DISTINCT type FROM accommodations");
$accommodationTypes = $stmt->fetchAll();

// Get success message if it exists
$successMessage = '';
if (isset($_SESSION['booking_success'])) {
    $successMessage = $_SESSION['booking_success'];
    unset($_SESSION['booking_success']); // Clear the message
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_booking'])) {
        $stmt = $pdo->prepare('DELETE FROM pet_boarding WHERE id = ? AND user_id = ?');
        $stmt->execute([$_POST['booking_id'], $user['id']]);
        header("Location: book_pet_boarding.php");
        exit();
    }
    
    if (isset($_POST['delete_all'])) {
        $stmt = $pdo->prepare('DELETE FROM pet_boarding WHERE user_id = ?');
        $stmt->execute([$user['id']]);
        header("Location: book_pet_boarding.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Pet Boarding</title>
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
            width: calc(100% - 240px);
            margin-bottom: 0.01px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }
        button {
            background-color: #1abc9c;
            color: white;
            padding: 12px 25px; /* Increased padding */
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px; /* Added font-size */
            width: 100%; /* Added this line */
            display: block; /* Added this line */
            transition: all 0.3s ease; /* Add transition for smooth animation */
        }
        button:hover {
            background-color: #16a085;
            transform: translateY(-2px); /* Slight lift effect */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Add shadow on hover */
        }
        .success-message {
            background-color: #2ecc71;
            color: white;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: none;
            animation: fadeOut 0.5s ease-in-out 3s forwards;
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
            max-width: 1900px; /* Added this to match booking_calendar */
            margin: 0 auto; /* Center the grid */
        }

        .booking-section {
            background: #2c3e50; /* Updated to match booking_calendar style */
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            color: white; /* Added for better contrast */
        }

        /* Update form elements for better contrast */
        .form-group label {
            color: #fff;
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #415b76;
            border-radius: 4px;
            background: #34495e;
            color: #fff;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1abc9c;
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
            padding: 8px 15px; /* Increased padding */
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.3s;
            font-size: 14px; /* Added font-size */
        }

        .delete-btn:hover {
            background-color: #a12d23;  /* Changed to a darker red for hover */
        }

        .delete-all-btn {
            background-color: #e74c3c;  /* Changed to match delete-btn */
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
            background-color: #a12d23;  /* Changed to match delete-btn hover */
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

        .show {
            display: none;
        }
        
        /* Add these rules for textarea placeholder */
        textarea::placeholder {
            color: #fff;
        }
        
        /* For Firefox */
        textarea::-moz-placeholder {
            color: #fff;
        }
        
        /* For Internet Explorer */
        textarea:-ms-input-placeholder {
            color: #fff;
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
            <li><a href="book_pet_boarding.php" class="active"><i class="fas fa-hotel"></i>Book Pet Hotel</a></li>
            <li><a href="services.php"><i class="fas fa-list"></i>Services & Prices</a></li>
            <li><a href="feedback.php"><i class="fas fa-comments"></i>Feedback & Reviews</a></li>
        </ul>
    </div>

    <div class="main-content">
        <br>
        <?php if ($successMessage): ?>
        <div class="success-message" id="successMessage">
            <?php echo htmlspecialchars($successMessage); ?>
        </div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="booking-section">
                <form action="process_booking.php" method="POST">
                    <div class="form-group">
                        <label for="pet_name">Pet Name</label>
                        <input type="text" id="pet_name" name="pet_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="pet_type">Pet Type</label>
                        <select id="pet_type" name="pet_type" required>
                            <option value="">Select Pet Type</option>
                            <option value="dog">Dog</option>
                            <option value="cat">Cat</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="accommodation_type">Accommodation Type</label>
                        <select id="accommodation_type" name="accommodation_type" required>
                            <option value="">Select Accommodation</option>
                            <option value="cage">Cage</option>
                            <option value="room">Room</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="check_in">Check-in Date</label>
                        <input type="date" id="check_in" name="check_in" required>
                    </div>

                    <div class="form-group">
                        <label for="check_out">Check-out Date</label>
                        <input type="date" id="check_out" name="check_out" required>
                    </div>

                    <div class="form-group">
                        <label for="accommodation_number">Accommodation Number</label>
                        <select id="accommodation_number" name="accommodation_number" required>
                            <option value="">Select Number</option>    
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="notes">Special Notes</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Any special requirements or notes for your pet"></textarea>
                    </div>

                    <button type="submit">Book Now</button>
                </form>
            </div>

            <div class="appointments-section">
                <h2>My Bookings</h2>
                <?php
                // Fetch pet boarding appointments
                $stmt = $pdo->prepare('
                    SELECT pb.*, a.type as accommodation_type, a.number as accommodation_number 
                    FROM pet_boarding pb
                    JOIN accommodations a ON pb.accommodation_id = a.id
                    WHERE pb.user_id = ? 
                    ORDER BY pb.check_in DESC
                ');
                $stmt->execute([$user['id']]);
                $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <?php if (count($bookings) > 0): ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete all bookings?');">
                        <button type="submit" name="delete_all" class="delete-all-btn">Delete All Bookings</button>
                    </form>
                    <?php foreach ($bookings as $booking): ?>
                        <div class="appointment-card <?php echo $booking['status']; ?>">
                            <div class="appointment-details"><strong>Pet Name:</strong> <?php echo htmlspecialchars($booking['pet_name']); ?></div>
                            <div class="appointment-details"><strong>Pet Type:</strong> <?php echo htmlspecialchars($booking['pet_type']); ?></div>
                            <div class="appointment-details"><strong>Accommodation:</strong> <?php echo htmlspecialchars($booking['accommodation_type'] . ' ' . $booking['accommodation_number']); ?></div>
                            <div class="appointment-details"><strong>Check-in:</strong> <?php echo htmlspecialchars($booking['check_in']); ?></div>
                            <div class="appointment-details"><strong>Check-out:</strong> <?php echo htmlspecialchars($booking['check_out']); ?></div>
                            <div class="appointment-details"><strong>Status:</strong> <span class="status-<?php echo $booking['status']; ?>"><?php echo ucfirst(htmlspecialchars($booking['status'])); ?></span></div>
                            <?php if (!empty($booking['notes'])): ?>
                                <div class="appointment-details"><strong>Notes:</strong> <?php echo htmlspecialchars($booking['notes']); ?></div>
                            <?php endif; ?>
                            <?php if ($booking['status'] === 'rejected' && !empty($booking['rejection_reason'])): ?>
                                <div class="appointment-details rejection-reason">
                                    <strong>Rejection Reason:</strong> <?php echo htmlspecialchars($booking['rejection_reason']); ?>
                                </div>
                            <?php endif; ?>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this booking?');">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                <button type="submit" name="delete_booking" class="delete-btn">Delete</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-appointments">You have no pet boarding bookings at this time.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function updateAvailableNumbers() {
            const type = document.getElementById('accommodation_type').value;
            const checkIn = document.getElementById('check_in').value;
            const checkOut = document.getElementById('check_out').value;
            const numberSelect = document.getElementById('accommodation_number');
            
            if (!type) {
                numberSelect.innerHTML = '<option value="">Select Number</option>';
                return;
            }

            numberSelect.innerHTML = '<option value="">Loading...</option>';
            
            fetch('check_availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `type=${encodeURIComponent(type)}&check_in=${encodeURIComponent(checkIn)}&check_out=${encodeURIComponent(checkOut)}`
            })
            .then(response => response.json())
            .then(numbers => {
                const selectText = type === 'cage' ? 'Select Cage Number' : 'Select Room Number';
                numberSelect.innerHTML = `<option value="">${selectText}</option>`;
                numbers.forEach(num => {
                    numberSelect.innerHTML += `<option value="${num}">${type} ${num}</option>`;
                });
            })
            .catch(error => {
                console.error('Error:', error);
                numberSelect.innerHTML = '<option value="">Error loading numbers</option>';
            });
        }

        document.getElementById('accommodation_type').addEventListener('change', updateAvailableNumbers);
        document.getElementById('check_in').addEventListener('change', updateAvailableNumbers);
        document.getElementById('check_out').addEventListener('change', updateAvailableNumbers);

        const typeSelect = document.getElementById('accommodation_type');
        if (typeSelect.value) {
            updateAvailableNumbers();
        }

        const successMessage = document.getElementById('successMessage');
        if (successMessage) {
            successMessage.style.display = 'block';
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 3500);
        }
    </script>
</body>
</html>