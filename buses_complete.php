<?php
// FUTURE AUTOMOTIVE - Complete Bus Management System
// صفحة إدارة الحافلات المتكاملة

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
            redirect("buses_complete.php");
        }
        
        // Check license plate uniqueness
        $stmt = $pdo->prepare("SELECT id FROM buses WHERE license_plate = ?");
        $stmt->execute([$license_plate]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'License plate already exists';
            redirect("buses_complete.php");
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
        redirect("buses_complete.php");
        
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM buses WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        
        $_SESSION['message'] = 'Bus deleted successfully';
        redirect("buses_complete.php");
    } elseif ($action === 'bulk_delete') {
        $ids = $_POST['selected_buses'] ?? [];
        if (!empty($ids)) {
            $placeholders = str_repeat('?,', count($ids) - 1);
            $stmt = $pdo->prepare("DELETE FROM buses WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $_SESSION['message'] = count($ids) . ' buses deleted successfully';
        }
        redirect("buses_complete.php");
    }
}

// Get filters
$category_filter = sanitize_input($_GET['category'] ?? '');
$status_filter = sanitize_input($_GET['status'] ?? '');
$search = sanitize_input($_GET['search'] ?? '');

// Get buses from database
$buses = [];
$total_buses = 0;
$active_buses = 0;

try {
    $database = new Database();
    $pdo = $database->connect();

    // Build query with filters
    $sql = "SELECT * FROM buses WHERE 1=1";
    $params = [];
    
    if ($category_filter) {
        $sql .= " AND category = ?";
        $params[] = $category_filter;
    }
    
    if ($status_filter) {
        $sql .= " AND status = ?";
        $params[] = $status_filter;
    }
    
    if ($search) {
        $sql .= " AND (bus_number LIKE ? OR license_plate LIKE ? OR make LIKE ? OR model LIKE ?)";
        $search_term = "%$search%";
        $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    }
    
    $sql .= " ORDER BY category, bus_number";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?> Simple Clean Theme</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/simple-theme.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding-top: 70px;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .sidebar-collapsed .main-content {
            margin-left: 70px;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            background: white;
        }
        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            padding: 15px;
            font-weight: bold;
        }
        .table th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }
        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }
        .btn-primary {
            background: #007bff;
            color: white;
            border: 1px solid #007bff;
        }
        .btn-success {
            background: #28a745;
            color: white;
            border: 1px solid #28a745;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
            border: 1px solid #dc3545;
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
            border: 1px solid #ffc107;
        }
        .btn:hover {
            opacity: 0.8;
        }
        .form-control {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 8px 12px;
        }
        .form-label {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background: #28a745;
            color: white;
        }
        .badge-warning {
            background: #ffc107;
            color: #212529;
        }
        .badge-danger {
            background: #dc3545;
            color: white;
        }
        .text-center {
            text-align: center;
        }
        .mb-3 {
            margin-bottom: 15px;
        }
        .mb-4 {
            margin-bottom: 20px;
        }
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header_simple.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="container-fluid">
            <div class="mb-4">
                <h1><i class="fas fa-bus me-2"></i>Bus Management</h1>
                <p class="text-muted">Complete fleet management system</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3><?php echo $total_buses; ?></h3>
                            <p class="text-muted">Total Vehicles</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3><?php echo count(array_filter($buses, function($bus) { return $bus['category'] === 'Bus'; })); ?></h3>
                            <p class="text-muted">Buses</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3><?php echo count(array_filter($buses, function($bus) { return $bus['category'] === 'Minibus'; })); ?></h3>
                            <p class="text-muted">Minibuses</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3><?php echo $active_buses; ?></h3>
                            <p class="text-muted">Active</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Alert Messages -->
            <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show fade-in" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show fade-in" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show fade-in" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Filters Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" id="searchInput" placeholder="Search by number, plate, make, model..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Category</label>
                            <select class="form-select" id="categoryFilter">
                                <option value="">All Categories</option>
                                <option value="Bus" <?php echo $category_filter === 'Bus' ? 'selected' : ''; ?>>Bus</option>
                                <option value="Minibus" <?php echo $category_filter === 'Minibus' ? 'selected' : ''; ?>>Minibus</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="maintenance" <?php echo $status_filter === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Actions</label>
                            <div class="btn-group w-100">
                                <button class="btn btn-success" onclick="exportData()">
                                    <i class="fas fa-download"></i> Export
                                </button>
                                <button class="btn btn-danger" onclick="bulkDelete()" id="bulkDeleteBtn" style="display: none;">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#busModal">
                                <i class="fas fa-plus"></i> Add Vehicle
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Table -->
            <div class="card fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Vehicle Fleet
                        <span class="badge bg-secondary ms-2"><?php echo $total_buses; ?> vehicles</span>
                    </h5>
                    <div class="d-flex align-items-center">
                        <div class="form-check me-3">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label" for="selectAll">Select All</label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($buses)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bus fa-4x text-secondary mb-3"></i>
                        <h4 class="text-secondary">No vehicles found</h4>
                        <p class="text-muted">Try adjusting your filters or add your first vehicle</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#busModal">
                            <i class="fas fa-plus me-2"></i>Add Your First Vehicle
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="40px">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAllHeader">
                                        </div>
                                    </th>
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
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input bus-checkbox" type="checkbox" value="<?php echo $bus['id']; ?>">
                                        </div>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($bus['bus_number']); ?></strong></td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($bus['category']); ?></span>
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
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-primary" onclick="editBus(<?php echo $bus['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteBus(<?php echo $bus['id']; ?>, '<?php echo addslashes(htmlspecialchars($bus['bus_number'])); ?>')">
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
                            <i class="fas fa-save me-2"></i>Save Vehicle
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Select all functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.bus-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkDeleteButton();
        });
        
        document.getElementById('selectAllHeader').addEventListener('change', function() {
            document.getElementById('selectAll').checked = this.checked;
            const event = new Event('change', { bubbles: true });
            document.getElementById('selectAll').dispatchEvent(event);
        });
        
        // Update bulk delete button visibility
        function updateBulkDeleteButton() {
            const checkedBoxes = document.querySelectorAll('.bus-checkbox:checked');
            const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
            if (checkedBoxes.length > 0) {
                bulkDeleteBtn.style.display = 'inline-block';
            } else {
                bulkDeleteBtn.style.display = 'none';
            }
        }
        
        // Checkbox change listeners
        document.querySelectorAll('.bus-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkDeleteButton);
        });
        
        // Filter functionality
        document.getElementById('categoryFilter').addEventListener('change', function() {
            const url = new URL(window.location);
            url.searchParams.set('category', this.value);
            url.searchParams.delete('page');
            window.location.href = 'buses_complete.php?' + url.searchParams.toString();
        });
        
        document.getElementById('statusFilter').addEventListener('change', function() {
            const url = new URL(window.location);
            url.searchParams.set('status', this.value);
            url.searchParams.delete('page');
            window.location.href = 'buses_complete.php?' + url.searchParams.toString();
        });
        
        // Search functionality
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const url = new URL(window.location);
                url.searchParams.set('search', this.value);
                url.searchParams.delete('page');
                window.location.href = 'buses_complete.php?' + url.searchParams.toString();
            }, 500);
        });
        
        // Delete function
        function deleteBus(id, busNumber) {
            if (confirm('Are you sure you want to delete ' + busNumber + '? This action cannot be undone.')) {
                window.location.href = 'buses_complete.php?action=delete&id=' + id;
            }
        }
        
        // Bulk delete function
        function bulkDelete() {
            const checkedBoxes = document.querySelectorAll('.bus-checkbox:checked');
            const ids = Array.from(checkedBoxes).map(cb => cb.value);
            
            if (ids.length === 0) {
                alert('Please select at least one vehicle to delete');
                return;
            }
            
            if (confirm(`Are you sure you want to delete ${ids.length} vehicle(s)? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'buses_complete.php';
                form.innerHTML = '<input type="hidden" name="action" value="bulk_delete"><input type="hidden" name="selected_buses[]" value="' + ids.join('"><input type="hidden" name="selected_buses[]" value="' + ids.join('">') + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Export function
        function exportData() {
            const url = new URL(window.location);
            url.searchParams.set('export', 'csv');
            window.location.href = 'export_data.php?' + url.searchParams.toString();
        }
        
        // Reset form when modal is closed
        document.getElementById('busModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('busForm').reset();
        });
        
        // Add fade-in animation to cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('fade-in');
                }, index * 100);
            });
        });
    </script>
</body>
</html>
