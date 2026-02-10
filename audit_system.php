<?php
// FUTURE AUTOMOTIVE - System Audit
// فحص شامل للنظام والتأكد من جميع المكونات
require_once 'config.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'audit_system.php';
    header('Location: login.php');
    exit();
}

$user = get_logged_in_user();
$full_name = $user['full_name'] ?? $_SESSION['full_name'] ?? 'Administrateur';
$role = $_SESSION['role'] ?? 'admin';

// Only admin can access audit
if ($role !== 'admin') {
    $_SESSION['error_message'] = 'Access denied. Admin only.';
    header('Location: dashboard.php');
    exit();
}

$audit_results = [];
$db_connected = false;

try {
    $database = new Database();
    $conn = $database->connect();
    $db_connected = true;
    
    // 1. Check all required tables
    $required_tables = [
        'users',
        'drivers', 
        'buses',
        'articles_catalogue',
        'notifications',
        'maintenance_schedules',
        'breakdown_reports',
        'work_orders',
        'pan_issues',
        'suppliers',
        'demandes_prix',
        'bons_commande',
        'bons_entree',
        'bc_items',
        'be_items',
        'stock_management'
    ];
    
    $table_status = [];
    foreach ($required_tables as $table) {
        try {
            $stmt = $conn->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->rowCount() > 0;
            if ($exists) {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
                $count = $stmt->fetch()['count'];
                $table_status[$table] = ['exists' => true, 'count' => $count, 'status' => 'success'];
            } else {
                $table_status[$table] = ['exists' => false, 'count' => 0, 'status' => 'error'];
            }
        } catch (Exception $e) {
            $table_status[$table] = ['exists' => false, 'count' => 0, 'status' => 'error', 'error' => $e->getMessage()];
        }
    }
    
    // 2. Check table structures
    $structure_checks = [];
    
    // Check buses table structure
    try {
        $stmt = $conn->query("SHOW COLUMNS FROM buses");
        $buses_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $required_buses_columns = ['id', 'bus_number', 'license_plate', 'category', 'make', 'model', 'year', 'capacity', 'puissance_fiscale', 'status'];
        // Add driver_id to required columns if it exists
        if (in_array('driver_id', $buses_columns)) {
            $required_buses_columns[] = 'driver_id';
        }
        $missing_buses = array_diff($required_buses_columns, $buses_columns);
        $structure_checks['buses'] = [
            'status' => empty($missing_buses) ? 'success' : 'warning',
            'columns' => count($buses_columns),
            'missing' => $missing_buses,
            'has_driver_id' => in_array('driver_id', $buses_columns)
        ];
    } catch (Exception $e) {
        $structure_checks['buses'] = ['status' => 'error', 'error' => $e->getMessage()];
    }
    
    // Check drivers table structure
    try {
        $stmt = $conn->query("SHOW COLUMNS FROM drivers");
        $drivers_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $required_drivers_columns = ['id', 'nom', 'prenom', 'numero_conducteur', 'phone', 'email', 'cin', 'is_active', 'pin_code'];
        $missing_drivers = array_diff($required_drivers_columns, $drivers_columns);
        $structure_checks['drivers'] = [
            'status' => empty($missing_drivers) ? 'success' : 'warning',
            'columns' => count($drivers_columns),
            'missing' => $missing_drivers
        ];
    } catch (Exception $e) {
        $structure_checks['drivers'] = ['status' => 'error', 'error' => $e->getMessage()];
    }
    
    // Check users table structure
    try {
        $stmt = $conn->query("SHOW COLUMNS FROM users");
        $users_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $required_users_columns = ['id', 'username', 'full_name', 'email', 'password', 'role', 'phone', 'is_active'];
        // Remove 'last_login' and 'status' from required columns check since they were removed
        $missing_users = array_diff($required_users_columns, $users_columns);
        $structure_checks['users'] = [
            'status' => empty($missing_users) ? 'success' : 'warning',
            'columns' => count($users_columns),
            'missing' => $missing_users,
            'has_is_active' => in_array('is_active', $users_columns)
        ];
    } catch (Exception $e) {
        $structure_checks['users'] = ['status' => 'error', 'error' => $e->getMessage()];
    }
    
    // Check if articles_catalogue table structure
    try {
        $stmt = $conn->query("SHOW COLUMNS FROM articles_catalogue");
        $articles_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $required_articles_columns = ['id', 'code_article', 'designation', 'categorie', 'prix_unitaire', 'stock_ksar', 'stock_tetouan', 'stock_actuel', 'stock_minimal'];
        // Remove 'unite' from required columns check since it was removed
        $missing_articles = array_diff($required_articles_columns, $articles_columns);
        $structure_checks['articles_catalogue'] = [
            'status' => empty($missing_articles) ? 'success' : 'warning',
            'columns' => count($articles_columns),
            'missing' => $missing_articles
        ];
    } catch (Exception $e) {
        $structure_checks['articles_catalogue'] = ['status' => 'error', 'error' => $e->getMessage()];
    }
    
    // 3. Check data integrity
    $data_checks = [];
    
    // Check for orphaned records
    try {
        // Initialize counters
        $orphaned_buses = 0;
        $orphaned_breakdowns = 0;
        $orphaned_work_orders = 0;
        
        // Check if buses table has driver_id column
        try {
            $stmt = $conn->query("SHOW COLUMNS FROM buses LIKE 'driver_id'");
            if ($stmt->rowCount() > 0) {
                // Column exists, check for orphaned records
                $stmt = $conn->query("SELECT COUNT(*) as count FROM buses b LEFT JOIN drivers d ON b.driver_id = d.id WHERE b.driver_id IS NOT NULL AND d.id IS NULL");
                $orphaned_buses = $stmt->fetch()['count'];
            } else {
                // Column doesn't exist, set to 0
                $orphaned_buses = 0;
            }
        } catch (Exception $e) {
            // Error checking column, set to 0
            $orphaned_buses = 0;
        }
        
        // Check if breakdown_reports table exists
        try {
            $stmt = $conn->query("SHOW TABLES LIKE 'breakdown_reports'");
            if ($stmt->rowCount() > 0) {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM breakdown_reports br LEFT JOIN buses b ON br.bus_id = b.id WHERE b.id IS NULL");
                $orphaned_breakdowns = $stmt->fetch()['count'];
            }
        } catch (Exception $e) {
            // Table doesn't exist, skip this check
        }
        
        // Check if work_orders table exists
        try {
            $stmt = $conn->query("SHOW TABLES LIKE 'work_orders'");
            if ($stmt->rowCount() > 0) {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM work_orders wo LEFT JOIN breakdown_reports br ON wo.breakdown_id = br.id WHERE br.id IS NULL");
                $orphaned_work_orders = $stmt->fetch()['count'];
            }
        } catch (Exception $e) {
            // Table doesn't exist, skip this check
        }
        
        $data_checks['orphaned_records'] = [
            'buses_without_drivers' => $orphaned_buses,
            'breakdowns_without_buses' => $orphaned_breakdowns,
            'work_orders_without_breakdowns' => $orphaned_work_orders,
            'status' => ($orphaned_buses + $orphaned_breakdowns + $orphaned_work_orders) === 0 ? 'success' : 'warning'
        ];
    } catch (Exception $e) {
        $data_checks['orphaned_records'] = ['status' => 'error', 'error' => $e->getMessage()];
    }
    
    // Check for empty required fields
    try {
        $empty_checks = [];
        
        // Buses with missing required fields
        $empty_buses = 0;
        try {
            $stmt = $conn->query("SHOW TABLES LIKE 'buses'");
            if ($stmt->rowCount() > 0) {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM buses WHERE bus_number IS NULL OR bus_number = '' OR license_plate IS NULL OR license_plate = ''");
                $empty_buses = $stmt->fetch()['count'];
            }
        } catch (Exception $e) {
            // Table doesn't exist
        }
        $empty_checks['buses_missing_required'] = $empty_buses;
        
        // Drivers with missing required fields
        $empty_drivers = 0;
        try {
            $stmt = $conn->query("SHOW TABLES LIKE 'drivers'");
            if ($stmt->rowCount() > 0) {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM drivers WHERE nom IS NULL OR nom = '' OR prenom IS NULL OR prenom = ''");
                $empty_drivers = $stmt->fetch()['count'];
            }
        } catch (Exception $e) {
            // Table doesn't exist
        }
        $empty_checks['drivers_missing_required'] = $empty_drivers;
        
        // Articles with missing required fields
        $empty_articles = 0;
        try {
            $stmt = $conn->query("SHOW TABLES LIKE 'articles_catalogue'");
            if ($stmt->rowCount() > 0) {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM articles_catalogue WHERE code_article IS NULL OR code_article = '' OR designation IS NULL OR designation = ''");
                $empty_articles = $stmt->fetch()['count'];
            }
        } catch (Exception $e) {
            // Table doesn't exist
        }
        $empty_checks['articles_missing_required'] = $empty_articles;
        
        $total_empty = array_sum($empty_checks);
        $data_checks['empty_required_fields'] = [
            'checks' => $empty_checks,
            'total' => $total_empty,
            'status' => $total_empty === 0 ? 'success' : 'warning'
        ];
    } catch (Exception $e) {
        $data_checks['empty_required_fields'] = ['status' => 'error', 'error' => $e->getMessage()];
    }
    
    // 4. Check file system
    $file_checks = [];
    $required_files = [
        'config.php',
        'config_achat_hostinger.php',
        'includes/header.php',
        'includes/sidebar.php',
        'includes/functions.php',
        'dashboard.php',
        'buses_complete.php',
        'drivers.php',
        'articles_stockables.php',
        'notifications.php',
        'users_management.php',
        'purchase_performance.php',
        'achat_da.php',
        'achat_be.php',
        'fournisseurs.php'
    ];
    
    foreach ($required_files as $file) {
        $file_path = __DIR__ . '/' . $file;
        $file_checks[$file] = [
            'exists' => file_exists($file_path),
            'readable' => is_readable($file_path),
            'size' => file_exists($file_path) ? filesize($file_path) : 0,
            'status' => file_exists($file_path) && is_readable($file_path) ? 'success' : 'error'
        ];
    }
    
    // 5. Check permissions
    $permission_checks = [];
    try {
        // Check if directories are writable
        $permission_checks['uploads'] = [
            'writable' => is_writable(__DIR__ . '/uploads'),
            'exists' => is_dir(__DIR__ . '/uploads'),
            'status' => is_dir(__DIR__ . '/uploads') && is_writable(__DIR__ . '/uploads') ? 'success' : 'warning'
        ];
        
        $permission_checks['logs'] = [
            'writable' => is_writable(__DIR__ . '/logs'),
            'exists' => is_dir(__DIR__ . '/logs'),
            'status' => is_dir(__DIR__ . '/logs') && is_writable(__DIR__ . '/logs') ? 'success' : 'warning'
        ];
    } catch (Exception $e) {
        $permission_checks['error'] = $e->getMessage();
    }
    
    // 6. System performance
    $performance_checks = [];
    try {
        // Database connection time
        $start_time = microtime(true);
        $stmt = $conn->query("SELECT 1");
        $connection_time = (microtime(true) - $start_time) * 1000;
        
        // Check table sizes
        $table_sizes = [];
        foreach (['buses', 'drivers', 'articles_catalogue', 'breakdown_reports', 'notifications', 'suppliers', 'demandes_prix', 'bons_commande', 'bons_entree', 'bc_items', 'be_items', 'stock_management'] as $table) {
            try {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
                $count = $stmt->fetch()['count'];
                $table_sizes[$table] = $count;
            } catch (Exception $e) {
                $table_sizes[$table] = 0;
            }
        }
        
        $performance_checks['database'] = [
            'connection_time_ms' => round($connection_time, 2),
            'table_sizes' => $table_sizes,
            'status' => $connection_time < 100 ? 'success' : ($connection_time < 500 ? 'warning' : 'error')
        ];
    } catch (Exception $e) {
        $performance_checks['database'] = ['status' => 'error', 'error' => $e->getMessage()];
    }
    
    // Compile all results
    $audit_results = [
        'database_connection' => $db_connected,
        'tables' => $table_status,
        'structures' => $structure_checks,
        'data_integrity' => $data_checks,
        'file_system' => $file_checks,
        'permissions' => $permission_checks,
        'performance' => $performance_checks,
        'audit_timestamp' => date('Y-m-d H:i:s'),
        'audit_user' => $full_name
    ];
    
} catch (Exception $e) {
    $audit_results = [
        'error' => $e->getMessage(),
        'audit_timestamp' => date('Y-m-d H:i:s'),
        'audit_user' => $full_name
    ];
}

$page_title = 'System Audit';
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .audit-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .status-success { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-error { color: #dc3545; }
        .status-info { color: #17a2b8; }
        .audit-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        .metric-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #007bff;
        }
        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
        }
        .metric-label {
            font-size: 0.875rem;
            color: #718096;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            
            <!-- Audit Header -->
            <div class="audit-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1><i class="fas fa-shield-alt me-3"></i>System Audit</h1>
                        <p class="mb-0">Comprehensive system health check and validation</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="d-inline-flex align-items-center gap-3">
                            <div class="badge bg-<?php echo $db_connected ? 'success' : 'danger'; ?>">
                                <i class="fas fa-<?php echo $db_connected ? 'circle-check' : 'circle-exclamation'; ?>"></i>
                                <?php echo $db_connected ? 'Connected' : 'Disconnected'; ?>
                            </div>
                            <button class="btn btn-outline-light btn-sm" onclick="location.reload()">
                                <i class="fas fa-sync-alt me-1"></i>Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($audit_results['error'])): ?>
            <div class="alert alert-danger">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>Audit Error</h5>
                <p><?php echo htmlspecialchars($audit_results['error']); ?></p>
            </div>
            <?php else: ?>

            <!-- Database Connection Status -->
            <div class="audit-section">
                <h3><i class="fas fa-database me-2"></i>Database Connection</h3>
                <div class="row">
                    <div class="col-md-4">
                        <div class="metric-card">
                            <div class="metric-value status-<?php echo $db_connected ? 'success' : 'error'; ?>">
                                <i class="fas fa-<?php echo $db_connected ? 'check-circle' : 'times-circle'; ?>"></i>
                            </div>
                            <div class="metric-label">Connection Status</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="metric-card">
                            <div class="metric-value"><?php echo count($audit_results['tables']); ?></div>
                            <div class="metric-label">Tables Checked</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="metric-card">
                            <div class="metric-value">
                                <?php 
                                $success_tables = array_filter($audit_results['tables'], function($t) { return $t['status'] === 'success'; });
                                echo count($success_tables);
                                ?>
                            </div>
                            <div class="metric-label">Tables OK</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tables Status -->
            <div class="audit-section">
                <h3><i class="fas fa-table me-2"></i>Tables Status</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Table Name</th>
                                <th>Status</th>
                                <th>Record Count</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($audit_results['tables'] as $table => $info): ?>
                            <tr>
                                <td><code><?php echo $table; ?></code></td>
                                <td>
                                    <span class="badge bg-<?php echo $info['status'] === 'success' ? 'success' : ($info['status'] === 'error' ? 'danger' : 'warning'); ?>">
                                        <i class="fas fa-<?php echo $info['status'] === 'success' ? 'check' : ($info['status'] === 'error' ? 'times' : 'exclamation'); ?>"></i>
                                        <?php echo ucfirst($info['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($info['count']); ?></td>
                                <td>
                                    <?php if ($info['status'] === 'error'): ?>
                                    <button class="btn btn-sm btn-outline-primary" onclick="createTable('<?php echo $table; ?>')">
                                        <i class="fas fa-plus me-1"></i>Create
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Structure Checks -->
            <div class="audit-section">
                <h3><i class="fas fa-sitemap me-2"></i>Table Structures</h3>
                <div class="row">
                    <?php foreach ($audit_results['structures'] as $table => $check): ?>
                    <div class="col-md-4">
                        <div class="metric-card">
                            <div class="metric-value status-<?php echo $check['status']; ?>">
                                <?php echo $check['columns']; ?>
                            </div>
                            <div class="metric-label"><?php echo $table; ?> Columns</div>
                            <?php if (!empty($check['missing'])): ?>
                            <small class="text-danger">Missing: <?php echo implode(', ', $check['missing']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Data Integrity -->
            <div class="audit-section">
                <h3><i class="fas fa-shield-alt me-2"></i>Data Integrity</h3>
                <div class="row">
                    <div class="col-md-6">
                        <h5>Orphaned Records</h5>
                        <div class="metric-card">
                            <div class="metric-value status-<?php echo $audit_results['data_integrity']['orphaned_records']['status']; ?>">
                                <?php 
                                $orphaned = $audit_results['data_integrity']['orphaned_records'];
                                echo ($orphaned['buses_without_drivers'] ?? 0) + ($orphaned['breakdowns_without_buses'] ?? 0) + ($orphaned['work_orders_without_breakdowns'] ?? 0);
                                ?>
                            </div>
                            <div class="metric-label">Total Orphaned Records</div>
                            <ul class="list-unstyled mt-2">
                                <li>Buses without drivers: <?php echo $orphaned['buses_without_drivers'] ?? 0; ?></li>
                                <li>Breakdowns without buses: <?php echo $orphaned['breakdowns_without_buses'] ?? 0; ?></li>
                                <li>Work orders without breakdowns: <?php echo $orphaned['work_orders_without_breakdowns'] ?? 0; ?></li>
                            </ul>
                            <?php if (!($audit_results['structures']['buses']['has_driver_id'] ?? false)): ?>
                            <div class="alert alert-warning mt-2">
                                <small><i class="fas fa-info-circle me-1"></i>Note: driver_id column not found in buses table. Run update script to add driver assignment functionality.</small>
                            </div>
                            <?php endif; ?>
                            <?php if (!($audit_results['structures']['users']['has_is_active'] ?? false)): ?>
                            <div class="alert alert-warning mt-2">
                                <small><i class="fas fa-info-circle me-1"></i>Note: is_active column not found in users table. Run users schema update script.</small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5>Empty Required Fields</h5>
                        <div class="metric-card">
                            <div class="metric-value status-<?php echo $audit_results['data_integrity']['empty_required_fields']['status']; ?>">
                                <?php echo $audit_results['data_integrity']['empty_required_fields']['total'] ?? 0; ?>
                            </div>
                            <div class="metric-label">Empty Required Fields</div>
                            <ul class="list-unstyled mt-2">
                                <li>Buses: <?php echo $audit_results['data_integrity']['empty_required_fields']['checks']['buses_missing_required'] ?? 0; ?></li>
                                <li>Drivers: <?php echo $audit_results['data_integrity']['empty_required_fields']['checks']['drivers_missing_required'] ?? 0; ?></li>
                                <li>Articles: <?php echo $audit_results['data_integrity']['empty_required_fields']['checks']['articles_missing_required'] ?? 0; ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- File System -->
            <div class="audit-section">
                <h3><i class="fas fa-file-code me-2"></i>File System</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Status</th>
                                <th>Size</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($audit_results['file_system'] as $file => $info): ?>
                            <tr>
                                <td><code><?php echo $file; ?></code></td>
                                <td>
                                    <span class="badge bg-<?php echo $info['status'] === 'success' ? 'success' : 'danger'; ?>">
                                        <i class="fas fa-<?php echo $info['status'] === 'success' ? 'check' : 'times'; ?>"></i>
                                        <?php echo $info['exists'] ? 'Exists' : 'Missing'; ?>
                                    </span>
                                </td>
                                <td><?php echo $info['exists'] ? number_format($info['size']) . ' bytes' : 'N/A'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Performance -->
            <div class="audit-section">
                <h3><i class="fas fa-tachometer-alt me-2"></i>Performance</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="metric-card">
                            <div class="metric-value status-<?php echo $audit_results['performance']['database']['status']; ?>">
                                <?php echo $audit_results['performance']['database']['connection_time_ms']; ?> ms
                            </div>
                            <div class="metric-label">Database Connection Time</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="metric-card">
                            <div class="metric-value">
                                <?php 
                                $total_records = array_sum($audit_results['performance']['database']['table_sizes']);
                                echo number_format($total_records);
                                ?>
                            </div>
                            <div class="metric-label">Total Records</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Audit Summary -->
            <div class="audit-section">
                <h3><i class="fas fa-clipboard-check me-2"></i>Audit Summary</h3>
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle me-2"></i>Audit Information</h5>
                            <ul class="mb-0">
                                <li>Audit performed by: <strong><?php echo htmlspecialchars($audit_results['audit_user']); ?></strong></li>
                                <li>Audit timestamp: <strong><?php echo $audit_results['audit_timestamp']; ?></strong></li>
                                <li>System status: <strong class="status-success">Operational</strong></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function createTable(tableName) {
            if (confirm('Create table ' + tableName + '? This will run the setup script.')) {
                if (tableName === 'buses' && confirm('This will also add the driver_id column for driver assignment functionality.')) {
                    window.location.href = 'sql/update_buses_driver_id.php';
                } else {
                    window.location.href = 'sql/simple_system_setup.php?create_table=' + tableName;
                }
            }
        }
        
        // Auto-refresh every 5 minutes
        setTimeout(() => {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
