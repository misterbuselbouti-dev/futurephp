<?php
// FUTURE AUTOMOTIVE - Buses Edit Page with Validation
// صفحة تعديل الحافلات مع التحقق

require_once 'config.php';
require_once 'includes/functions.php';

// Check authentication
require_login();

// Page title
$page_title = 'Edit Bus';

// Get bus ID
$bus_id = sanitize_input($_GET['id'] ?? 0);
if (!$bus_id) {
    $_SESSION['error'] = 'Bus ID is required';
    redirect('buses_complete.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $pdo = $database->connect();
    
    $action = sanitize_input($_POST['action'] ?? '');
    
    if ($action === 'update') {
        // Get current bus data
        $stmt = $pdo->prepare("SELECT * FROM buses WHERE id = ?");
        $stmt->execute([$bus_id]);
        $current_bus = $stmt->fetch();
        
        if (!$current_bus) {
            $_SESSION['error'] = 'Bus not found';
            redirect('buses_complete.php');
        }
        
        // Get form data
        $bus_number = sanitize_input($_POST['bus_number'] ?? '');
        $license_plate = sanitize_input($_POST['license_plate'] ?? '');
        $category = sanitize_input($_POST['category'] ?? '');
        $make = sanitize_input($_POST['make'] ?? '');
        $model = sanitize_input($_POST['model'] ?? '');
        $year = $_POST['year'] ?? null;
        $capacity = $_POST['capacity'] ?? null;
        $puissance_fiscale = $_POST['puissance_fiscale'] ?? null;
        $status = sanitize_input($_POST['status'] ?? 'active');
        
        // Validation
        $errors = [];
        
        // Check bus number uniqueness (exclude current bus)
        if (empty($bus_number)) {
            $errors[] = 'Bus number is required';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM buses WHERE bus_number = ? AND id != ?");
            $stmt->execute([$bus_number, $bus_id]);
            if ($stmt->fetch()) {
                $errors[] = 'Bus number already exists';
            }
        }
        
        // Check license plate uniqueness (exclude current bus)
        if (empty($license_plate)) {
            $errors[] = 'License plate is required';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM buses WHERE license_plate = ? AND id != ?");
            $stmt->execute([$license_plate, $bus_id]);
            if ($stmt->fetch()) {
                $errors[] = 'License plate already exists';
            }
        }
        
        // Validate category
        if (!in_array($category, ['Bus', 'Minibus'])) {
            $errors[] = 'Invalid category';
        }
        
        // Validate status
        if (!in_array($status, ['active', 'inactive', 'maintenance', 'retired'])) {
            $errors[] = 'Invalid status';
        }
        
        // Validate year
        if ($year !== null && ($year < 1990 || $year > 2030)) {
            $errors[] = 'Year must be between 1990 and 2030';
        }
        
        // Validate capacity
        if ($capacity !== null && ($capacity < 1 || $capacity > 100)) {
            $errors[] = 'Capacity must be between 1 and 100';
        }
        
        // Validate puissance fiscale
        if ($puissance_fiscale !== null && ($puissance_fiscale < 1 || $puissance_fiscale > 50)) {
            $errors[] = 'Puissance fiscale must be between 1 and 50';
        }
        
        if (empty($errors)) {
            // Update bus
            $stmt = $pdo->prepare("UPDATE buses SET bus_number = ?, license_plate = ?, category = ?, make = ?, model = ?, year = ?, capacity = ?, puissance_fiscale = ?, status = ? WHERE id = ?");
            $result = $stmt->execute([$bus_number, $license_plate, $category, $make, $model, $year, $capacity, $puissance_fiscale, $status, $bus_id]);
            
            if ($result) {
                $_SESSION['message'] = 'Bus updated successfully';
                redirect('buses_complete.php');
            } else {
                $_SESSION['error'] = 'Failed to update bus';
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
        }
    }
}

// Get bus data
$bus = null;
try {
    $database = new Database();
    $pdo = $database->connect();
    
    $stmt = $pdo->prepare("SELECT * FROM buses WHERE id = ?");
    $stmt->execute([$bus_id]);
    $bus = $stmt->fetch();
    
    if (!$bus) {
        $_SESSION['error'] = 'Bus not found';
        redirect('buses_complete.php');
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Error loading bus: ' . $e->getMessage();
    redirect('buses_complete.php');
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
            padding-top: 70px; /* Space for fixed navbar */
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
        .form-label {
            font-weight: 600;
            color: #495057;
        }
        .form-control:focus, .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .category-badge {
            font-weight: bold;
        }
        .category-bus {
            background-color: #007bff;
            color: white;
        }
        .category-minibus {
            background-color: #28a745;
            color: white;
        }
        .validation-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Edit Bus</h1>
                <div>
                    <a href="buses_complete.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Buses
                    </a>
                </div>
            </div>
            
            <!-- Alert Messages -->
            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bus"></i>
                        Edit Bus: <?php echo htmlspecialchars($bus['bus_number']); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="busEditForm">
                        <input type="hidden" name="action" value="update">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bus Number *</label>
                                <input type="text" class="form-control" name="bus_number" id="busNumber" required 
                                       value="<?php echo htmlspecialchars($bus['bus_number']); ?>">
                                <small class="text-muted">Must be unique</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">License Plate *</label>
                                <input type="text" class="form-control" name="license_plate" id="licensePlate" required 
                                       value="<?php echo htmlspecialchars($bus['license_plate']); ?>">
                                <small class="text-muted">Must be unique</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category *</label>
                                <select class="form-select" name="category" id="category" required>
                                    <option value="">Select Category</option>
                                    <option value="Bus" <?php echo $bus['category'] === 'Bus' ? 'selected' : ''; ?>>Bus</option>
                                    <option value="Minibus" <?php echo $bus['category'] === 'Minibus' ? 'selected' : ''; ?>>Minibus</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="status">
                                    <option value="active" <?php echo $bus['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="maintenance" <?php echo $bus['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                    <option value="inactive" <?php echo $bus['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="retired" <?php echo $bus['status'] === 'retired' ? 'selected' : ''; ?>>Retired</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Make</label>
                                <input type="text" class="form-control" name="make" id="make" 
                                       value="<?php echo htmlspecialchars($bus['make']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Model</label>
                                <input type="text" class="form-control" name="model" id="model" 
                                       value="<?php echo htmlspecialchars($bus['model']); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Year</label>
                                <input type="number" class="form-control" name="year" id="year" min="1990" max="2030"
                                       value="<?php echo htmlspecialchars($bus['year']); ?>">
                                <small class="text-muted">1990-2030</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Capacity</label>
                                <input type="number" class="form-control" name="capacity" id="capacity" min="1" max="100"
                                       value="<?php echo htmlspecialchars($bus['capacity']); ?>">
                                <small class="text-muted">1-100 passengers</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Puissance Fiscale (CV)</label>
                                <input type="number" class="form-control" name="puissance_fiscale" id="puissanceFiscale" min="1" max="50"
                                       value="<?php echo htmlspecialchars($bus['puissance_fiscale']); ?>">
                                <small class="text-muted">1-50 CV</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title mb-3">Current Information</h6>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>Category:</strong><br>
                                                <span class="badge category-badge <?php echo $bus['category'] === 'Bus' ? 'category-bus' : 'category-minibus'; ?>">
                                                    <?php echo htmlspecialchars($bus['category']); ?>
                                                </span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Capacity:</strong><br>
                                                <?php echo htmlspecialchars($bus['capacity']); ?> passengers
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Puissance:</strong><br>
                                                <?php echo htmlspecialchars($bus['puissance_fiscale']); ?> CV
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Status:</strong><br>
                                                <span class="badge bg-<?php 
                                                    echo $bus['status'] === 'active' ? 'success' : 
                                                    ($bus['status'] === 'maintenance' ? 'warning' : 'danger'); 
                                                ?>">
                                                    <?php echo htmlspecialchars($bus['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="buses.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Real-time validation
        document.getElementById('busNumber').addEventListener('blur', function() {
            const value = this.value.trim();
            if (value.length < 3) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        document.getElementById('licensePlate').addEventListener('blur', function() {
            const value = this.value.trim();
            if (value.length < 5) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        document.getElementById('capacity').addEventListener('blur', function() {
            const value = parseInt(this.value);
            if (value < 1 || value > 100) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        document.getElementById('puissanceFiscale').addEventListener('blur', function() {
            const value = parseInt(this.value);
            if (value < 1 || value > 50) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        // Form validation before submit
        document.getElementById('busEditForm').addEventListener('submit', function(e) {
            const busNumber = document.getElementById('busNumber').value.trim();
            const licensePlate = document.getElementById('licensePlate').value.trim();
            const category = document.getElementById('category').value;
            
            if (!busNumber || !licensePlate || !category) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return false;
            }
            
            if (busNumber.length < 3) {
                e.preventDefault();
                alert('Bus number must be at least 3 characters');
                return false;
            }
            
            if (licensePlate.length < 5) {
                e.preventDefault();
                alert('License plate must be at least 5 characters');
                return false;
            }
        });
    </script>
</body>
</html>
