<?php
// FUTURE AUTOMOTIVE - Buses Management Page (Final Version)
// صفحة إدارة الحافلات

require_once 'config.php';
require_once 'includes/functions.php';

// Check authentication
require_login();

// Page title
$page_title = 'Bus Management';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $pdo = $database->connect();

    $action = sanitize_input($_POST['action'] ?? '');
    
    if ($action === 'add') {
        // Check for duplicates
        $bus_number = sanitize_input($_POST['bus_number'] ?? '');
        $license_plate = sanitize_input($_POST['license_plate'] ?? '');
        
        // Check bus number uniqueness
        $stmt = $pdo->prepare("SELECT id FROM buses WHERE bus_number = ?");
        $stmt->execute([$bus_number]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Bus number already exists';
            redirect("buses.php");
        }
        
        // Check license plate uniqueness
        $stmt = $pdo->prepare("SELECT id FROM buses WHERE license_plate = ?");
        $stmt->execute([$license_plate]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'License plate already exists';
            redirect("buses.php");
        }
        
        // Insert new bus
        $stmt = $pdo->prepare("INSERT INTO buses (bus_number, license_plate, category, make, model, year, capacity, puissance_fiscale, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $bus_number,
            $license_plate,
            sanitize_input($_POST['category']),
            sanitize_input($_POST['make']),
            sanitize_input($_POST['model']),
            $_POST['year'] ?? null,
            $_POST['capacity'] ?? null,
            $_POST['puissance_fiscale'] ?? null,
            $_POST['status'] ?? 'active'
        ]);
        
        $_SESSION['message'] = 'Bus added successfully';
        redirect("buses.php");
        
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM buses WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        
        $_SESSION['message'] = 'Bus deleted successfully';
        redirect("buses.php");
    }
}

// Get buses from database
$buses = [];
$total_buses = 0;
$active_buses = 0;

try {
    $database = new Database();
    $pdo = $database->connect();

    // Get all buses
    $stmt = $pdo->query("SELECT * FROM buses ORDER BY category, bus_number");
    $buses = $stmt->fetchAll();
    
    // Get bus statistics
    $total_buses = count($buses);
    $active_buses = count(array_filter($buses, function($bus) { return $bus['status'] === 'active'; }));
    
} catch (Exception $e) {
    $error_message = "Error loading buses: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s;
        }
        .sidebar-collapsed .main-content {
            margin-left: 70px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border: none;
        }
        .table th {
            background-color: #007bff;
            color: white;
            border: none;
        }
        .btn-group .btn {
            margin: 0 2px;
        }
        .status-active {
            color: #28a745;
        }
        .status-maintenance {
            color: #ffc107;
        }
        .status-inactive {
            color: #dc3545;
        }
        .category-badge {
            font-weight: bold;
            font-size: 0.8em;
        }
        .category-bus {
            background-color: #007bff;
            color: white;
        }
        .category-minibus {
            background-color: #28a745;
            color: white;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stats-card .card-body {
            color: white;
        }
        .btn-edit {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .btn-edit:hover {
            background-color: #5a6268;
            border-color: #5a6268;
        }
        .btn-delete {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-delete:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin: -20px -20px 20px -20px;
        }
        .header-section h1 {
            margin: 0;
            font-weight: 600;
        }
        .header-section .breadcrumb {
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 0.5rem 1rem;
            margin-bottom: 0;
        }
        .breadcrumb-item a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }
        .breadcrumb-item a:hover {
            color: white;
        }
        .breadcrumb-item.active {
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="container-fluid">
            <!-- Header Section -->
            <div class="header-section">
                <div class="container">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active">Bus Management</li>
                        </ol>
                    </nav>
                    <h1><i class="fas fa-bus me-3"></i>Bus Management</h1>
                    <p class="lead mb-0">Manage your fleet of buses and minibuses</p>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0"><?php echo $total_buses; ?></h4>
                                    <p class="mb-0">Total Vehicles</p>
                                </div>
                                <i class="fas fa-bus fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0"><?php echo count(array_filter($buses, function($bus) { return $bus['category'] === 'Bus'; })); ?></h4>
                                    <p class="mb-0">Buses</p>
                                </div>
                                <i class="fas fa-bus fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0"><?php echo count(array_filter($buses, function($bus) { return $bus['category'] === 'Minibus'; })); ?></h4>
                                    <p class="mb-0">Minibuses</p>
                                </div>
                                <i class="fas fa-van-shuttle fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Alert Messages -->
            <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Vehicle List
                    </h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#busModal">
                        <i class="fas fa-plus me-2"></i>Add Vehicle
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($buses)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bus fa-4x text-secondary mb-3"></i>
                        <h4 class="text-secondary">No vehicles currently available</h4>
                        <p class="text-muted">Start by adding your first bus or minibus</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#busModal">
                            <i class="fas fa-plus me-2"></i>Add New Vehicle
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Bus Number</th>
                                    <th>Category</th>
                                    <th>Make/Model</th>
                                    <th>Year</th>
                                    <th>License Plate</th>
                                    <th>Capacity</th>
                                    <th>Puissance Fiscale</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($buses as $bus): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($bus['bus_number']); ?></strong></td>
                                    <td>
                                        <span class="badge category-badge <?php echo $bus['category'] === 'Bus' ? 'category-bus' : 'category-minibus'; ?>">
                                            <?php echo htmlspecialchars($bus['category']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($bus['make'] . ' ' . $bus['model']); ?></td>
                                    <td><?php echo $bus['year'] ?? '-'; ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($bus['license_plate']); ?></span>
                                    </td>
                                    <td><?php echo $bus['capacity'] ?? '-'; ?> passengers</td>
                                    <td><?php echo $bus['puissance_fiscale'] ?? '-'; ?> CV</td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $bus['status'] === 'active' ? 'success' : 
                                            ($bus['status'] === 'maintenance' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php 
                                            $status_labels = [
                                                'active' => 'Active',
                                                'maintenance' => 'Maintenance',
                                                'inactive' => 'Inactive'
                                            ];
                                            echo $status_labels[$bus['status']] ?? $bus['status'];
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="buses_edit.php?id=<?php echo $bus['id']; ?>" class="btn btn-sm btn-edit" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-sm btn-delete" onclick="deleteBus(<?php echo $bus['id']; ?>)" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Bus Modal -->
    <div class="modal fade" id="busModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-bus"></i>
                        Add New Vehicle
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="busForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bus Number *</label>
                                <input type="text" class="form-control" name="bus_number" id="busNumber" required>
                                <small class="text-muted">Must be unique</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">License Plate *</label>
                                <input type="text" class="form-control" name="license_plate" id="licensePlate" required>
                                <small class="text-muted">Must be unique</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category *</label>
                                <select class="form-select" name="category" id="category" required>
                                    <option value="">Select Category</option>
                                    <option value="Bus">Bus</option>
                                    <option value="Minibus">Minibus</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="status">
                                    <option value="active" selected>Active</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Make</label>
                                <input type="text" class="form-control" name="make" id="make">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Model</label>
                                <input type="text" class="form-control" name="model" id="model">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Year</label>
                                <input type="number" class="form-control" name="year" id="year" min="1990" max="2030">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Capacity</label>
                                <input type="number" class="form-control" name="capacity" id="capacity" min="1" max="100">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Puissance Fiscale (CV)</label>
                                <input type="number" class="form-control" name="puissance_fiscale" id="puissanceFiscale" min="1" max="50">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteBus(id) {
            if (confirm('Are you sure you want to delete this vehicle? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Reset form when modal is closed
        document.getElementById('busModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('busForm').reset();
        });
    </script>
</body>
</html>
