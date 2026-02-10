<?php
// FUTURE AUTOMOTIVE - Buses Management Page (Updated for new schema)
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
        $stmt = $pdo->prepare("INSERT INTO buses (bus_number, license_plate, category, make, model, year, capacity, puissance_fiscale, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            sanitize_input($_POST['bus_number']),
            sanitize_input($_POST['license_plate']),
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
        
    } elseif ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE buses SET bus_number = ?, license_plate = ?, category = ?, make = ?, model = ?, year = ?, capacity = ?, puissance_fiscale = ?, status = ? WHERE id = ?");
        $stmt->execute([
            sanitize_input($_POST['bus_number']),
            sanitize_input($_POST['license_plate']),
            sanitize_input($_POST['category']),
            sanitize_input($_POST['make']),
            sanitize_input($_POST['model']),
            $_POST['year'] ?? null,
            $_POST['capacity'] ?? null,
            $_POST['puissance_fiscale'] ?? null,
            $_POST['status'] ?? 'active',
            $_POST['id']
        ]);
        
        $_SESSION['message'] = 'Bus data updated successfully';
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
$buses_with_drivers = 0;

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

// Get bus for editing
$edit_bus = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM buses WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_bus = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <!-- ISO 9001 Professional Theme -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/iso-theme.css">
    <link rel="stylesheet" href="assets/css/iso-components.css">
    <link rel="stylesheet" href="assets/css/iso-bootstrap.css">
    <style>
        .main-content {
            margin-left: 260px;
            padding: var(--space-8);
            min-height: 100vh;
        }
        
        .iso-card {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-lg);
            padding: var(--space-6);
            margin-bottom: var(--space-6);
            transition: transform 0.2s;
        }
        
        .iso-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .table th {
            background-color: var(--primary);
            color: white;
            border: none;
        }
        
        .btn-group .btn {
            margin: 0 2px;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: var(--space-4);
            }
        }
    </style>
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
        }
        .category-bus {
            background-color: #007bff;
            color: white;
        }
        .category-minibus {
            background-color: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Bus Management</h1>
                <div>
                    <span class="badge bg-primary me-2">Total: <?php echo $total_buses; ?></span>
                    <span class="badge bg-success">Active: <?php echo $active_buses; ?></span>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="iso-card">
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
                    <div class="iso-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0"><?php echo count(array_filter($buses, function($bus) { return $bus['category'] === 'Bus'; })); ?></h4>
                                    <p class="mb-0">Buses</p>
                                </div>
                                <i class="fas fa-bus fa-2x text-primary opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="iso-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0"><?php echo count(array_filter($buses, function($bus) { return $bus['category'] === 'Minibus'; })); ?></h4>
                                    <p class="mb-0">Minibuses</p>
                                </div>
                                <i class="fas fa-van-shuttle fa-2x text-success opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Alert Messages -->
            <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="iso-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Vehicle List</h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#busModal">
                        <i class="fas fa-plus me-2"></i>Add Vehicle
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($buses)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-bus fa-3x text-secondary mb-3"></i>
                        <p class="text-secondary">No vehicles currently available</p>
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
                                            <button class="btn btn-sm btn-outline-primary" onclick="editBus(<?php echo $bus['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteBus(<?php echo $bus['id']; ?>)">
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

    <!-- Bus Modal -->
    <div class="modal fade" id="busModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-bus"></i>
                        <?php echo $edit_bus ? 'Edit Vehicle' : 'Add Vehicle'; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="busForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?php echo $edit_bus ? 'edit' : 'add'; ?>">
                        <?php if ($edit_bus): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_bus['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bus Number *</label>
                                <input type="text" class="form-control" name="bus_number" id="busNumber" required 
                                       value="<?php echo htmlspecialchars($edit_bus['bus_number'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">License Plate *</label>
                                <input type="text" class="form-control" name="license_plate" id="licensePlate" required 
                                       value="<?php echo htmlspecialchars($edit_bus['license_plate'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category *</label>
                                <select class="form-select" name="category" id="category" required>
                                    <option value="">Select Category</option>
                                    <option value="Bus" <?php echo ($edit_bus['category'] ?? '') === 'Bus' ? 'selected' : ''; ?>>Bus</option>
                                    <option value="Minibus" <?php echo ($edit_bus['category'] ?? '') === 'Minibus' ? 'selected' : ''; ?>>Minibus</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="status">
                                    <option value="active" <?php echo ($edit_bus['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="maintenance" <?php echo ($edit_bus['status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                    <option value="inactive" <?php echo ($edit_bus['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Make</label>
                                <input type="text" class="form-control" name="make" id="make" 
                                       value="<?php echo htmlspecialchars($edit_bus['make'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Model</label>
                                <input type="text" class="form-control" name="model" id="model" 
                                       value="<?php echo htmlspecialchars($edit_bus['model'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Year</label>
                                <input type="number" class="form-control" name="year" id="year" min="1990" max="2030"
                                       value="<?php echo htmlspecialchars($edit_bus['year'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Capacity</label>
                                <input type="number" class="form-control" name="capacity" id="capacity" min="1"
                                       value="<?php echo htmlspecialchars($edit_bus['capacity'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Puissance Fiscale (CV)</label>
                                <input type="number" class="form-control" name="puissance_fiscale" id="puissanceFiscale" min="1"
                                       value="<?php echo htmlspecialchars($edit_bus['puissance_fiscale'] ?? ''); ?>">
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
        function editBus(id) {
            window.location.href = 'buses.php?edit=' + id;
        }
        
        function deleteBus(id) {
            if (confirm('Are you sure you want to delete this vehicle?')) {
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
