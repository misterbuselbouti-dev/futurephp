<?php
// Simple Cleanup - Remove unnecessary files
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Files to remove
$files_to_remove = [
    'test_auth_access.php',
    'test_breakdown_assignments.php',
    'test_breakdown_reports.php',
    'test_da_status_change.php',
    'test_da_system.php',
    'test_driver_breakdown.php',
    'test_drivers_simple.php',
    'fix_breakdown_assignments_data.php',
    'fix_breakdown_assignments_structure.php',
    'fix_breakdown_columns.php',
    'fix_breakdown_reports.php',
    'fix_da_database.php',
    'fix_da_ref_renumber.php',
    'fix_remaining_da_issues.php',
    'fix_report_id_references.php',
    'fix_script.php',
    'fix_technician_access.php',
    'debug_articles.php',
    'debug_drivers.php',
    'da_status_diagnostic.php',
    'da_system_status.php',
    'quick_fix.php',
    'quick_login.php',
    'dashboard2.php',
    'dashboard_new.php',
    'driver_breakdown_new_old.php',
    'convert_all_excel_to_sql.php',
    'convert_excel_to_sql.php',
    'cleanup_manager.php',
    'intelligent_cleanup.php',
    'smart_cleanup.php',
    'cleanup_files.php'
];

// Execute cleanup
if (isset($_GET['cleanup']) && $_GET['cleanup'] === 'yes') {
    $removed = 0;
    $total_size = 0;
    
    foreach ($files_to_remove as $file) {
        if (file_exists($file)) {
            $size = filesize($file);
            if (unlink($file)) {
                $removed++;
                $total_size += $size;
            }
        }
    }
    
    echo "<!DOCTYPE html><html><head><title>Cleanup Complete</title></head><body>";
    echo "<h1>Cleanup Complete</h1>";
    echo "<p>Files removed: $removed</p>";
    echo "<p>Space recovered: " . number_format($total_size / 1024, 2) . " KB</p>";
    echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
    echo "</body></html>";
    exit;
}

// Show cleanup page
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cleanup Files</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f8fafc; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #1e3a8a; }
        .btn { background: #1e3a8a; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #1e40af; }
        .file-list { background: #f8fafc; padding: 20px; border-radius: 4px; margin: 20px 0; }
        .file-list ul { list-style: none; padding: 0; }
        .file-list li { padding: 5px 0; color: #64748b; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 Cleanup Unnecessary Files</h1>
        <p>This will remove <?php echo count($files_to_remove); ?> unnecessary files to clean up your project.</p>
        
        <div class="file-list">
            <h3>Files to be removed:</h3>
            <ul>
                <?php foreach ($files_to_remove as $file): ?>
                    <?php if (file_exists($file)): ?>
                        <li>✓ <?php echo $file; ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <p style="color: #dc2626;"><strong>Warning:</strong> This action cannot be undone.</p>
        
        <a href="?cleanup=yes" class="btn" onclick="return confirm('Are you sure you want to delete these files?')">
            🧹 Execute Cleanup
        </a>
        
        <p style="margin-top: 30px;">
            <a href="dashboard.php" style="color: #64748b;">← Back to Dashboard</a>
        </p>
    </div>
</body>
</html>
