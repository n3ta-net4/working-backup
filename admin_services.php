<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'db.php';
$user = $_SESSION['user'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $stmt = $pdo->prepare("INSERT INTO grooming_services (service_name, category, description, fixed_price, small_price, medium_price, large_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['service_name'],
                    $_POST['category'],
                    $_POST['description'],
                    $_POST['fixed_price'] ?: null,
                    $_POST['small_price'] ?: null,
                    $_POST['medium_price'] ?: null,
                    $_POST['large_price'] ?: null
                ]);
                break;

            case 'edit':
                $stmt = $pdo->prepare("UPDATE grooming_services SET service_name=?, category=?, description=?, fixed_price=?, small_price=?, medium_price=?, large_price=? WHERE id=?");
                $stmt->execute([
                    $_POST['service_name'],
                    $_POST['category'],
                    $_POST['description'],
                    $_POST['fixed_price'] ?: null,
                    $_POST['small_price'] ?: null,
                    $_POST['medium_price'] ?: null,
                    $_POST['large_price'] ?: null,
                    $_POST['id']
                ]);
                break;

            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM grooming_services WHERE id=?");
                $stmt->execute([$_POST['id']]);
                break;
        }
        header("Location: admin_services.php");
        exit();
    }
}

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
    <title>Manage Services</title>
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
        
        /* Update sidebar styles to match admin_appointments.php */
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

        /* Update main content styles */
        .main-content {
            margin-left: 240px;
            padding: 30px;
            width: calc(100% - 240px);
            background-color: #fff;
            overflow-y: auto;
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

        /* Update service cards styling */
        .services-category {
            background: #2c3e50;
            padding: 25px;
            margin: 15px 0;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .category-title {
            color: #ffffff;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1abc9c;
        }

        /* ...existing admin controls and modal styles... */
        
        .admin-controls {
            margin-bottom: 20px;
        }

        .btn-add {
            background: #1abc9c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .service-card {
            position: relative;
        }

        .admin-actions {
            position: absolute;
            top: 10px;
            right: 10px;
        }

        .btn-edit, .btn-delete {
            padding: 5px 10px;
            margin-left: 5px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        .btn-edit {
            background: #f39c12;
            color: white;
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        hr {
            border: 0;
            height: 1px;
            background: #fff;
            margin: 10px 0;
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
            position: relative;
        }

        .service-name {
            color: #fff;
            font-size: 20px;
            margin-bottom: 15px;
            text-align: center;
            padding-top: 30px;
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
            <li><a href="admin_manage_reservations.php"><i class="fas fa-hotel"></i> Manage Reservations</a></li>
            <li><a href="admin_services.php" class="active"><i class="fas fa-bone"></i> Manage Services</a></li>
            <li><a href="admin_feedback.php"><i class="fas fa-comments"></i> Manage Feedback</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h1>Manage Services</h1>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>

        <div class="services-container">
            <div class="admin-controls">
                <button class="btn-add" onclick="showAddModal()">Add New Service</button>
            </div>

            <?php foreach ($groupedServices as $category => $categoryServices): ?>
            <div class="services-category">
                <h2 class="category-title"><?php echo htmlspecialchars($category); ?> Services</h2>
                <div class="services-grid">
                    <?php foreach ($categoryServices as $service): ?>
                    <div class="service-card">
                        <div class="admin-actions">
                            <button class="btn-edit" onclick="showEditModal(<?php echo htmlspecialchars(json_encode($service)); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-delete" onclick="deleteService(<?php echo $service['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <h3 class="service-name"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                        <ul class="price-list">
                            <?php if (isset($service['fixed_price']) && $service['fixed_price'] !== null): ?>
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
                        <?php if (isset($service['description']) && !empty($service['description'])): ?>
                            <p class="service-description"><?php echo htmlspecialchars($service['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add/Edit Service Modal -->
    <div id="serviceModal" class="modal">
        <div class="modal-content">
            <form id="serviceForm" method="POST">
                <input type="hidden" name="action" id="formAction">
                <input type="hidden" name="id" id="serviceId">
                
                <div class="form-group">
                    <label for="service_name">Service Name</label>
                    <input type="text" id="service_name" name="service_name" required>
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="A La Carte">A La Carte</option>
                        <option value="Package">Package</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"></textarea>
                </div>

                <div class="form-group">
                    <label for="fixed_price">Fixed Price (if applicable)</label>
                    <input type="number" id="fixed_price" name="fixed_price" step="0.01">
                </div>

                <div class="form-group">
                    <label for="small_price">Small Price</label>
                    <input type="number" id="small_price" name="small_price" step="0.01">
                </div>

                <div class="form-group">
                    <label for="medium_price">Medium Price</label>
                    <input type="number" id="medium_price" name="medium_price" step="0.01">
                </div>

                <div class="form-group">
                    <label for="large_price">Large Price</label>
                    <input type="number" id="large_price" name="large_price" step="0.01">
                </div>

                <button type="submit" class="btn-add">Save Service</button>
                <button type="button" onclick="closeModal()" class="btn-delete">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function showAddModal() {
            document.getElementById('serviceForm').reset();
            document.getElementById('formAction').value = 'add';
            document.getElementById('serviceModal').style.display = 'block';
        }

        function showEditModal(service) {
            document.getElementById('formAction').value = 'edit';
            document.getElementById('serviceId').value = service.id;
            document.getElementById('service_name').value = service.service_name;
            document.getElementById('category').value = service.category;
            document.getElementById('description').value = service.description;
            document.getElementById('fixed_price').value = service.fixed_price;
            document.getElementById('small_price').value = service.small_price;
            document.getElementById('medium_price').value = service.medium_price;
            document.getElementById('large_price').value = service.large_price;
            document.getElementById('serviceModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('serviceModal').style.display = 'none';
        }

        function deleteService(id) {
            if (confirm('Are you sure you want to delete this service?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html> 

