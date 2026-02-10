<?php
// FUTURE AUTOMOTIVE - Audit Report Generator
// إنشاء تقرير فحص شامل للنظام
require_once 'config.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'audit_report.php';
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

// Generate audit report
$report_data = [];
$db_connected = false;

try {
    $database = new Database();
    $conn = $database->connect();
    $db_connected = true;
    
    // Collect comprehensive audit data
    $report_data = [
        'system_info' => [
            'app_name' => APP_NAME,
            'audit_date' => date('Y-m-d H:i:s'),
            'audited_by' => $full_name,
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'
        ],
        'database_info' => [
            'connection_status' => $db_connected ? 'Connected' : 'Disconnected',
            'host' => $conn ? get_class($conn) : 'N/A'
        ],
        'tables_summary' => [],
        'data_summary' => [],
        'issues_found' => [],
        'recommendations' => []
    ];
    
    // Get all tables
    $stmt = $conn->query("SHOW TABLES");
    $all_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Analyze each table
    foreach ($all_tables as $table) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $stmt->fetch()['count'];
            
            $stmt = $conn->query("SHOW TABLE STATUS LIKE '$table'");
            $status = $stmt->fetch();
            
            $report_data['tables_summary'][$table] = [
                'record_count' => $count,
                'size_bytes' => $status['Data_length'] + $status['Index_length'],
                'engine' => $status['Engine'],
                'collation' => $status['Collation']
            ];
        } catch (Exception $e) {
            $report_data['issues_found'][] = [
                'type' => 'table_error',
                'table' => $table,
                'message' => $e->getMessage()
            ];
        }
    }
    
    // Check specific critical tables
    $critical_tables = ['users', 'drivers', 'buses', 'articles_catalogue', 'notifications'];
    foreach ($critical_tables as $table) {
        if (!in_array($table, $all_tables)) {
            $report_data['issues_found'][] = [
                'type' => 'missing_table',
                'table' => $table,
                'severity' => 'critical',
                'message' => "Critical table '$table' is missing"
            ];
            $report_data['recommendations'][] = "Create table '$table' using the setup script";
        } elseif ($report_data['tables_summary'][$table]['record_count'] === 0) {
            $report_data['issues_found'][] = [
                'type' => 'empty_table',
                'table' => $table,
                'severity' => 'warning',
                'message' => "Critical table '$table' is empty"
            ];
            $report_data['recommendations'][] = "Populate table '$table' with sample data";
        }
    }
    
    // Check data integrity
    try {
        // Check for invalid references
        $stmt = $conn->query("
            SELECT 'buses' as table_name, COUNT(*) as orphaned_count 
            FROM buses b 
            LEFT JOIN drivers d ON b.driver_id = d.id 
            WHERE b.driver_id IS NOT NULL AND d.id IS NULL
            UNION ALL
            SELECT 'breakdown_reports' as table_name, COUNT(*) as orphaned_count 
            FROM breakdown_reports br 
            LEFT JOIN buses b ON br.bus_id = b.id 
            WHERE b.id IS NULL
        ");
        $orphaned = $stmt->fetchAll();
        
        foreach ($orphaned as $record) {
            if ($record['orphaned_count'] > 0) {
                $report_data['issues_found'][] = [
                    'type' => 'orphaned_records',
                    'table' => $record['table_name'],
                    'count' => $record['orphaned_count'],
                    'severity' => 'warning',
                    'message' => "Found {$record['orphaned_count']} orphaned records in {$record['table_name']}"
                ];
                $report_data['recommendations'][] = "Clean up orphaned records in {$record['table_name']}";
            }
        }
    } catch (Exception $e) {
        $report_data['issues_found'][] = [
            'type' => 'integrity_check_error',
            'message' => $e->getMessage()
        ];
    }
    
    // Check file permissions
    $critical_files = [
        'config.php',
        'includes/functions.php',
        'includes/header.php',
        'includes/sidebar.php'
    ];
    
    foreach ($critical_files as $file) {
        $file_path = __DIR__ . '/' . $file;
        if (!file_exists($file_path)) {
            $report_data['issues_found'][] = [
                'type' => 'missing_file',
                'file' => $file,
                'severity' => 'critical',
                'message' => "Critical file '$file' is missing"
            ];
        } elseif (!is_readable($file_path)) {
            $report_data['issues_found'][] = [
                'type' => 'file_permission',
                'file' => $file,
                'severity' => 'warning',
                'message' => "File '$file' is not readable"
            ];
        }
    }
    
    // Calculate summary statistics
    $total_records = array_sum(array_column($report_data['tables_summary'], 'record_count'));
    $total_size = array_sum(array_column($report_data['tables_summary'], 'size_bytes'));
    $critical_issues = count(array_filter($report_data['issues_found'], function($issue) {
        return ($issue['severity'] ?? 'medium') === 'critical';
    }));
    $warning_issues = count(array_filter($report_data['issues_found'], function($issue) {
        return ($issue['severity'] ?? 'medium') === 'warning';
    }));
    
    $report_data['summary'] = [
        'total_tables' => count($all_tables),
        'total_records' => $total_records,
        'total_size_mb' => round($total_size / 1024 / 1024, 2),
        'critical_issues' => $critical_issues,
        'warning_issues' => $warning_issues,
        'overall_status' => $critical_issues === 0 ? 'healthy' : ($critical_issues <= 2 ? 'attention_needed' : 'critical')
    ];
    
} catch (Exception $e) {
    $report_data = [
        'error' => $e->getMessage(),
        'audit_date' => date('Y-m-d H:i:s'),
        'audited_by' => $full_name
    ];
}

// Export to different formats
$export_format = $_GET['format'] ?? 'html';

switch ($export_format) {
    case 'json':
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="audit_report_' . date('Y-m-d_H-i-s') . '.json"');
        echo json_encode($report_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
        
    case 'csv':
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="audit_report_' . date('Y-m-d_H-i-s') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Header
        fputcsv($output, ['Audit Report - ' . APP_NAME]);
        fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
        fputcsv($output, ['Audited by: ' . $full_name]);
        fputcsv($output, []);
        
        // Summary
        fputcsv($output, ['SUMMARY']);
        fputcsv($output, ['Metric', 'Value']);
        foreach ($report_data['summary'] as $key => $value) {
            fputcsv($output, [ucfirst(str_replace('_', ' ', $key)), $value]);
        }
        fputcsv($output, []);
        
        // Issues
        fputcsv($output, ['ISSUES FOUND']);
        fputcsv($output, ['Type', 'Severity', 'Table/File', 'Message']);
        foreach ($report_data['issues_found'] as $issue) {
            fputcsv($output, [
                $issue['type'] ?? 'Unknown',
                $issue['severity'] ?? 'medium',
                $issue['table'] ?? $issue['file'] ?? 'N/A',
                $issue['message'] ?? 'No message'
            ]);
        }
        
        fclose($output);
        exit;
        
    case 'txt':
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="audit_report_' . date('Y-m-d_H-i-s') . '.txt"');
        
        echo "=" . str_repeat("=", 50) . "\n";
        echo "AUDIT REPORT - " . APP_NAME . "\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
        
        echo "Generated: " . date('Y-m-d H:i:s') . "\n";
        echo "Audited by: " . $full_name . "\n\n";
        
        echo "SUMMARY:\n";
        echo str_repeat("-", 30) . "\n";
        foreach ($report_data['summary'] as $key => $value) {
            echo ucfirst(str_replace('_', ' ', $key)) . ": " . $value . "\n";
        }
        echo "\n";
        
        echo "ISSUES FOUND:\n";
        echo str_repeat("-", 30) . "\n";
        foreach ($report_data['issues_found'] as $i => $issue) {
            echo ($i + 1) . ". " . ($issue['type'] ?? 'Unknown') . "\n";
            echo "   Severity: " . ($issue['severity'] ?? 'medium') . "\n";
            echo "   Location: " . ($issue['table'] ?? $issue['file'] ?? 'N/A') . "\n";
            echo "   Message: " . ($issue['message'] ?? 'No message') . "\n\n";
        }
        
        echo "RECOMMENDATIONS:\n";
        echo str_repeat("-", 30) . "\n";
        foreach ($report_data['recommendations'] as $i => $rec) {
            echo ($i + 1) . ". " . $rec . "\n";
        }
        
        exit;
}

// HTML output (default)
$page_title = 'Audit Report';
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <!-- ISO 9001 Professional Theme -->
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
        
        .report-header {
            background: var(--primary);
            color: white;
            padding: var(--space-6);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-6);
        }
        
        .iso-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-4);
            margin-bottom: var(--space-6);
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
        
        .status-healthy { color: var(--success); }
        .status-warning { color: #ffc107; }
        .status-critical { color: #dc3545; }
        .metric-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
        }
        .metric-label {
            font-size: 0.875rem;
            color: #6c757d;
        }
        .issue-item {
            border-left: 4px solid #dc3545;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #fff5f5;
        }
        .issue-warning {
            border-left-color: #ffc107;
            background: #fffbf0;
        }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            
            <!-- Report Header -->
            <div class="report-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1><i class="fas fa-file-alt me-3"></i>Audit Report</h1>
                        <p class="mb-0">Comprehensive system audit report and analysis</p>
                    </div>
                    <div class="col-md-4 text-end no-print">
                        <div class="btn-group" role="group">
                            <a href="?format=json" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-code me-1"></i>JSON
                            </a>
                            <a href="?format=csv" class="btn btn-success btn-sm">
                                <i class="fas fa-file-csv me-1"></i>CSV
                            </a>
                            <a href="?format=txt" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-file-alt me-1"></i>TXT
                            </a>
                            <button class="btn btn-primary btn-sm" onclick="window.print()">
                                <i class="fas fa-print me-1"></i>Imprimer
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($report_data['error'])): ?>
            <div class="alert alert-danger">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>Report Generation Error</h5>
                <p><?php echo htmlspecialchars($report_data['error']); ?></p>
            </div>
            <?php else: ?>

            <!-- System Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle me-2"></i>System Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Application:</strong> <?php echo htmlspecialchars($report_data['system_info']['app_name']); ?><br>
                            <strong>Audit Date:</strong> <?php echo $report_data['system_info']['audit_date']; ?><br>
                            <strong>Audited by:</strong> <?php echo htmlspecialchars($report_data['system_info']['audited_by']); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>PHP Version:</strong> <?php echo $report_data['system_info']['php_version']; ?><br>
                            <strong>Database:</strong> <?php echo $report_data['database_info']['connection_status']; ?><br>
                            <strong>Server:</strong> <?php echo htmlspecialchars($report_data['system_info']['server_software']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Statistics -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-chart-pie me-2"></i>Summary Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="metric-box">
                                <div class="metric-value"><?php echo $report_data['summary']['total_tables']; ?></div>
                                <div class="metric-label">Total Tables</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="metric-box">
                                <div class="metric-value"><?php echo number_format($report_data['summary']['total_records']); ?></div>
                                <div class="metric-label">Total Records</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="metric-box">
                                <div class="metric-value"><?php echo $report_data['summary']['total_size_mb']; ?> MB</div>
                                <div class="metric-label">Database Size</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="metric-box">
                                <div class="metric-value status-critical"><?php echo $report_data['summary']['critical_issues']; ?></div>
                                <div class="metric-label">Critical Issues</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="metric-box">
                                <div class="metric-value status-warning"><?php echo $report_data['summary']['warning_issues']; ?></div>
                                <div class="metric-label">Warnings</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="metric-box">
                                <div class="metric-value status-<?php echo $report_data['summary']['overall_status']; ?>">
                                    <?php echo ucfirst($report_data['summary']['overall_status']); ?>
                                </div>
                                <div class="metric-label">Overall Status</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tables Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-table me-2"></i>Tables Summary</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Table Name</th>
                                    <th>Records</th>
                                    <th>Size</th>
                                    <th>Engine</th>
                                    <th>Collation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data['tables_summary'] as $table => $info): ?>
                                <tr>
                                    <td><code><?php echo $table; ?></code></td>
                                    <td><?php echo number_format($info['record_count']); ?></td>
                                    <td><?php echo number_format($info['size_bytes'] / 1024, 2); ?> KB</td>
                                    <td><?php echo $info['engine']; ?></td>
                                    <td><?php echo $info['collation']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Issues Found -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Issues Found</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($report_data['issues_found'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>No issues found! System is healthy.
                    </div>
                    <?php else: ?>
                    <?php foreach ($report_data['issues_found'] as $issue): ?>
                    <div class="issue-item <?php echo ($issue['severity'] ?? 'medium') === 'warning' ? 'issue-warning' : ''; ?>">
                        <h6>
                            <i class="fas fa-<?php echo ($issue['severity'] ?? 'medium') === 'critical' ? 'times-circle' : 'exclamation-triangle'; ?> me-2"></i>
                            <?php echo ucfirst(str_replace('_', ' ', $issue['type'] ?? 'Unknown')); ?>
                            <span class="badge bg-<?php echo ($issue['severity'] ?? 'medium') === 'critical' ? 'danger' : 'warning'; ?> ms-2">
                                <?php echo ucfirst($issue['severity'] ?? 'medium'); ?>
                            </span>
                        </h6>
                        <p class="mb-1">
                            <strong>Location:</strong> <?php echo htmlspecialchars($issue['table'] ?? $issue['file'] ?? 'N/A'); ?>
                        </p>
                        <p class="mb-0"><?php echo htmlspecialchars($issue['message']); ?></p>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recommendations -->
            <?php if (!empty($report_data['recommendations'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-lightbulb me-2"></i>Recommendations</h5>
                </div>
                <div class="card-body">
                    <ol>
                        <?php foreach ($report_data['recommendations'] as $rec): ?>
                        <li><?php echo htmlspecialchars($rec); ?></li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>
            <?php endif; ?>

            <!-- Report Footer -->
            <div class="text-center text-muted mt-4">
                <p class="mb-0">
                    Report generated on <?php echo date('Y-m-d H:i:s'); ?> by <?php echo htmlspecialchars($full_name); ?>
                </p>
                <p class="small">
                    FUTURE AUTOMOTIVE - Bus Management System Audit Report
                </p>
            </div>

            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
