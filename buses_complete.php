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
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: none;
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
        }
        .table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            font-weight: 600;
        }
        .btn-group .btn {
            margin: 0 2px;
            border-radius: 6px;
        }
        .category-badge {
            font-weight: 600;
            font-size: 0.75em;
            padding: 4px 8px;
            border-radius: 12px;
        }
        .category-bus {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .category-minibus {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-3px);
        }
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin: -20px -20px 30px -20px;
            border-radius: 0 0 20px 20px;
        }
        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-edit {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            border: none;
            color: white;
        }
        .btn-edit:hover {
            background: linear-gradient(135deg, #5a6268 0%, #343a40 100%);
            color: white;
        }
        .btn-delete {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            color: white;
        }
        .btn-delete:hover {
            background: linear-gradient(135deg, #bd2130 0%, #a71e2a 100%);
            color: white;
        }
        .btn-export {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
        }
        .btn-export:hover {
            background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
            color: white;
        }
        .checkbox-custom {
            width: 18px;
            height: 18px;
        }
        .table-actions {
            min-width: 120px;
        }
        .search-box {
            position: relative;
        }
        .search-box input {
            padding-left: 40px;
            border-radius: 20px;
            border: 2px solid #e9ecef;
        }
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .breadcrumb {
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
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="container-fluid">
            <!-- Header Section -->
            <div class="header-section">
                <div class="container">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home me-2"></i>Home</a></li>
                            <li class="breadcrumb-item active"><i class="fas fa-bus me-2"></i>Bus Management</li>
                        </ol>
                    </nav>
                    <h1 class="display-4 fw-bold"><i class="fas fa-bus me-3"></i>Bus Management System</h1>
                    <p class="lead mb-0">Complete fleet management with advanced features</p>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row g-3 mb-4 fade-in">
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-0"><?php echo $total_buses; ?></h2>
                                    <p class="mb-0">Total Vehicles</p>
                                </div>
                                <i class="fas fa-bus fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-0"><?php echo count(array_filter($buses, function($bus) { return $bus['category'] === 'Bus'; })); ?></h2>
                                    <p class="mb-0">Buses</p>
                                </div>
                                <i class="fas fa-bus fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-0"><?php echo count(array_filter($buses, function($bus) { return $bus['category'] === 'Minibus'; })); ?></h2>
                                    <p class="mb-0">Minibuses</p>
                                </div>
                                <i class="fas fa-van-shuttle fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-0"><?php echo $active_buses; ?></h2>
                                    <p class="mb-0">Active</p>
                                </div>
                                <i class="fas fa-check-circle fa-2x opacity-75"></i>
                            </div>
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
            <div class="filter-section fade-in">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" class="form-control" id="searchInput" placeholder="Search by number, plate, make, model..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
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
                            <button class="btn btn-export" onclick="exportData()">
                                <i class="fas fa-download me-2"></i>Export
                            </button>
                            <button class="btn btn-danger" onclick="bulkDelete()" id="bulkDeleteBtn" style="display: none;">
                                <i class="fas fa-trash me-2"></i>Delete
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#busModal">
                            <i class="fas fa-plus me-2"></i>Add Vehicle
                        </button>
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
                            <input class="form-check-input checkbox-custom" type="checkbox" id="selectAll">
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
                                            <input class="form-check-input checkbox-custom" type="checkbox" id="selectAllHeader">
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
                                    <th class="table-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($buses as $bus): ?>
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input checkbox-custom bus-checkbox" type="checkbox" value="<?php echo $bus['id']; ?>">
                                        </div>
                                    </td>
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
                                    <td class="table-actions">
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
        function deleteBus(id) {
            if (confirm('Are you sure you want to delete this vehicle? This action cannot be undone.')) {
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
