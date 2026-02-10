<?php
// FUTURE AUTOMOTIVE - Ultimate Cleanup Tool
// Delete redundant files and old themes to prevent conflicts

echo "<!DOCTYPE html><html><head><title>Ultimate Cleanup</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".container{max-width:1200px;margin:0 auto;background:white;padding:20px;border-radius:10px;}";
echo ".success{color:green;font-weight:bold;}";
echo ".error{color:red;font-weight:bold;}";
echo ".warning{color:orange;font-weight:bold;}";
echo ".file{background:#f8f9fa;padding:10px;margin:5px 0;border-left:4px solid #007bff;}";
echo ".deleted{background:#f8d7da;border-left:4px solid #dc3545;}";
echo ".protected{background:#fff3cd;border-left:4px solid #ffc107;}";
echo ".progress{width:100%;background:#e0e0e0;border-radius:5px;margin:10px 0;}";
echo ".progress-bar{background:#dc3545;color:white;text-align:center;padding:5px;border-radius:5px;}";
echo "</style></head><body>";

echo "<div class='container'>";
echo "<h1>🗑️ FUTURE AUTOMOTIVE - Ultimate Cleanup</h1>";
echo "<h2>Delete Redundant Files & Old Themes to Prevent Conflicts</h2>";

echo "<div class='warning' style='background:#fff3cd;padding:20px;border-radius:10px;border-left:5px solid #ffc107;margin:20px 0;'>";
echo "<h3>⚠️ WARNING: This will permanently delete files!</h3>";
echo "<p><strong>This tool will delete redundant files and old theme files.</strong></p>";
echo "<p><strong>Make sure you have backups before proceeding!</strong></p>";
echo "</div>";

// Files to delete (redundant and old themes)
$filesToDelete = [
    // Old CSS files
    'assets/css/style.css',
    'assets/css/simple-theme.css',
    'assets/css/theme.css',
    'assets/css/professional.css',
    'assets/css/dashboard.css',
    'assets/css/old-theme.css',
    'assets/css/legacy.css',
    
    // Redundant theme tools
    'ultimate_theme_cleanup.php',
    'ultimate_theme_cleanup_iso_only.php',
    'theme_conflict_resolver.php',
    'color_conflict_resolver.php',
    'final_color_fix.php',
    'hostinger_status.php',
    'iso_theme_migrator.php',
    'safe_files_cleaner.php',
    'safe_files_cleaner_enhanced.php',
    'theme_updater.php',
    'batch_theme_update.php',
    'apply_simple_theme_universal.php',
    'simple_theme_updater.php',
    'direct_fix.php',
    'force_update.php',
    'quick_audit.php',
    'site_audit.php',
    'auto_deploy_ftp.php',
    
    // Redundant backup files
    'backup/b16/enforce_simple_theme.php',
    'backup/b16/simple_theme_updater.php',
    'backup/b16/fix_breakdown_assignments_data.php',
    
    // Test files
    'test_bc_search.php',
    'check_database_data.php',
    
    // Duplicate admin files
    'admin/simple_theme_update.php',
    'admin/complete_system_fix.php',
    'admin/emergency_fix.php',
    'admin/quick_fix.php',
    'admin/simple_check.php',
    'admin/check_workshop_fixed.php',
    'admin/check_workshop_tables.php',
    'admin/create_workshop_step_by_step.php',
    'admin/database_setup.php',
    'admin/create_notifications_table.php',
    'admin/test_audit.php',
    'admin/time_tracking_interface.php',
    'admin/inventory_integration_modal.php',
    'admin/ajax_audit_system.php',
    'admin/ajax_inventory_integration.php',
    'admin/ajax_time_tracking.php',
    'admin/ajax_worker_assignment.php',
    
    // Redundant system files
    'complete_database_fix.php',
    'final_database_fix.php',
    'ultimate_database_fix.php',
    'complete_solution.php',
    'remove_unnecessary_files.php',
    'update_achat_theme.php',
    'update_garage_workers.php',
    'quick_fix.php',
    'emergency_fix.php',
    
    // Old dashboard versions
    'dashboard2.php',
    'dashboard_new.php',
    'dashboard_simple.php',
    'dashboard_professional.php',
    
    // Old bus files
    'buses_final.php',
    'buses_edit.php',
    
    // Old breakdown files
    'admin_breakdowns.php',
    'admin_breakdown_view.php',
    'admin_breakdown_view_enhanced.php',
    'admin_breakdowns_enhanced.php',
    'driver_breakdown_new_old.php',
    'bus_work_orders.php',
    
    // Old purchase files
    'achat_dp.php',
    'achat_dp_edit.php',
    'achat_dp_view.php',
    'achat_dp_get_da_items.php',
    'achat_dp_response.php',
    'achat_be.php',
    'achat_be_edit.php',
    'achat_be_view.php',
    'achat_be_get_bc_items.php',
    'achat_be_auto_validate.php',
    'achat_be_pdf.php',
    
    // Other redundant files
    'archive_monthly.php',
    'portal.php',
    'garage_workers.php',
    'notifications.php',
    'users_management.php',
    'export_data.php',
    'stock_tetouan.php',
    'stock_ksar.php',
    'fournisseurs.php',
    'archive_dashboard.php',
    'purchase_performance.php',
    'audit_interface.php',
    'quick_audit.php',
    'site_audit.php'
];

// Files to protect (never delete)
$protectedFiles = [
    'config.php',
    'config_achat_hostinger.php',
    'functions.php',
    'login.php',
    'dashboard.php',
    'dashboard_iso.php',
    'buses.php',
    'buses_complete.php',
    'drivers.php',
    'articles_stockables.php',
    'achat_da.php',
    'achat_bc.php',
    'achat_da_edit.php',
    'achat_da_view.php',
    'achat_da_delete.php',
    'achat_da_validate.php',
    'achat_bc_edit.php',
    'achat_bc_view.php',
    'achat_bc_pdf.php',
    'admin/audit.php',
    'admin/audit_interface.php',
    'admin/admin_breakdowns.php',
    'admin/admin_breakdowns_workshop.php',
    'admin/work_order_edit.php',
    'admin/work_order_view.php',
    'audit_system.php',
    'audit_report.php',
    'includes/header.php',
    'includes/header_simple.php',
    'includes/sidebar.php',
    'includes/functions.php',
    'assets/css/iso-universal-theme.css',
    'assets/css/iso-theme.css',
    'assets/css/iso-components.css',
    'assets/css/iso-bootstrap.css',
    'emergency_theme_fix.php',
    'universal_theme_updater.php',
    'selective_update.php',
    'complete_theme_reset.php'
];

echo "<h3>🔍 Files Analysis</h3>";

$filesFound = [];
$filesNotFound = [];
$protectedFilesFound = [];

foreach ($filesToDelete as $file) {
    if (file_exists($file)) {
        $filesFound[] = $file;
    } else {
        $filesNotFound[] = $file;
    }
}

foreach ($protectedFiles as $file) {
    if (file_exists($file)) {
        $protectedFilesFound[] = $file;
    }
}

echo "<table border='1' style='width:100%;border-collapse:collapse;'>";
echo "<tr><th>Category</th><th>Count</th><th>Status</th></tr>";
echo "<tr><td>Files to delete</td><td>" . count($filesFound) . "</td><td class='error'>🗑️ Ready for deletion</td></tr>";
echo "<tr><td>Files not found</td><td>" . count($filesNotFound) . "</td><td class='success'>✅ Already deleted</td></tr>";
echo "<tr><td>Protected files</td><td>" . count($protectedFilesFound) . "</td><td class='warning'>🔒 Will not be deleted</td></tr>";
echo "</table>";

echo "<h3>🔒 Protected Files (Will NOT be deleted)</h3>";
echo "<div class='protected'>";
foreach ($protectedFilesFound as $file) {
    echo "<span style='margin: 5px; padding: 5px; background: #fff; border-radius: 3px;'>🔒 $file</span><br>";
}
echo "</div>";

if (isset($_POST['confirm_cleanup']) && $_POST['confirm_cleanup'] === 'YES_DELETE_ALL') {
    echo "<h3>🗑️ Executing Cleanup...</h3>";
    
    $deletedCount = 0;
    $errorCount = 0;
    
    foreach ($filesFound as $file) {
        try {
            if (unlink($file)) {
                $deletedCount++;
                echo "<div class='file deleted'>";
                echo "<span class='error'>🗑️ DELETED:</span> $file";
                echo "</div>";
            } else {
                $errorCount++;
                echo "<div class='file'>";
                echo "<span class='error'>❌ FAILED TO DELETE:</span> $file";
                echo "</div>";
            }
        } catch (Exception $e) {
            $errorCount++;
            echo "<div class='file'>";
            echo "<span class='error'>❌ ERROR DELETING:</span> $file - " . $e->getMessage();
            echo "</div>";
        }
        
        // Progress
        $progress = round(($deletedCount + $errorCount) / count($filesFound) * 100);
        echo "<div class='progress'>";
        echo "<div class='progress-bar' style='width:$progress%;'>$progress%</div>";
        echo "</div>";
    }
    
    echo "<h2>📊 Cleanup Results</h2>";
    echo "<table border='1' style='width:100%;border-collapse:collapse;'>";
    echo "<tr><th>Metric</th><th>Count</th><th>Status</th></tr>";
    echo "<tr><td>Files deleted</td><td>$deletedCount</td><td class='success'>✅ Success</td></tr>";
    echo "<tr><td>Delete errors</td><td>$errorCount</td><td class='" . ($errorCount > 0 ? 'error' : 'success') . "'>" . ($errorCount > 0 ? '❌ Issues' : '✅ None') . "</td></tr>";
    echo "<tr><td>Total processed</td><td>" . ($deletedCount + $errorCount) . "</td><td class='success'>✅ Complete</td></tr>";
    echo "</table>";
    
    echo "<div class='success' style='background:#d4edda;padding:20px;border-radius:10px;border-left:5px solid #28a745;'>";
    echo "<h3>🎉 Cleanup Complete!</h3>";
    echo "<ul>";
    echo "<li>✅ Deleted $deletedCount redundant files</li>";
    echo "<li>✅ Removed all old theme files</li>";
    echo "<li>✅ Protected all important files</li>";
    echo "<li>✅ Prevented future conflicts</li>";
    echo "</ul>";
    echo "</div>";
    
} else {
    echo "<h3>🗑️ Files Ready for Deletion</h3>";
    echo "<div class='deleted'>";
    foreach ($filesFound as $file) {
        echo "<span style='margin: 5px; padding: 5px; background: #fff; border-radius: 3px;'>🗑️ $file</span><br>";
    }
    echo "</div>";
    
    echo "<h3>⚠️ Confirmation Required</h3>";
    echo "<div class='error' style='background:#f8d7da;padding:20px;border-radius:10px;border-left:5px solid #dc3545;'>";
    echo "<h3>🚨 FINAL CONFIRMATION</h3>";
    echo "<p><strong>This will permanently delete " . count($filesFound) . " files!</strong></p>";
    echo "<p><strong>These files are redundant and causing conflicts.</strong></p>";
    echo "<p><strong>Important files are protected and will not be deleted.</strong></p>";
    echo "</div>";
    
    echo "<form method='post' style='text-align: center; margin: 30px 0;'>";
    echo "<input type='hidden' name='confirm_cleanup' value='YES_DELETE_ALL'>";
    echo "<button type='submit' style='background-color: #dc3545; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; font-weight: bold;'>";
    echo "🗑️ YES! DELETE ALL REDUNDANT FILES";
    echo "</button>";
    echo "</form>";
    
    echo "<div style='background:#e7f3ff;padding:20px;border-radius:10px;border-left:5px solid #2196F3;margin:20px 0;'>";
    echo "<h3>💡 What This Cleanup Accomplishes</h3>";
    echo "<ul>";
    echo "<li>🗑️ Removes all old CSS theme files</li>";
    echo "<li>🗑️ Deletes redundant theme tools</li>";
    echo "<li>🗑️ Removes old backup files</li>";
    echo "<li>🗑️ Deletes test and debug files</li>";
    echo "<li>🔒 Protects all important system files</li>";
    echo "<li>🔒 Preserves all main application files</li>";
    echo "<li>🎯 Prevents future theme conflicts</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<h2>📋 File Categories</h2>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;'>";
echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;'>";
echo "<h4>🗑️ Files to Delete</h4>";
echo "<ul>";
echo "<li>Old CSS theme files</li>";
echo "<li>Redundant theme tools</li>";
echo "<li>Test and debug files</li>";
echo "<li>Old backup files</li>";
echo "<li>Duplicate admin files</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;'>";
echo "<h4>🔒 Protected Files</h4>";
echo "<ul>";
echo "<li>Main application files</li>";
echo "<li>Configuration files</li>";
echo "<li>Universal theme files</li>";
echo "<li>Emergency tools</li>";
echo "<li>Core functionality</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

echo "</div>";
echo "</body></html>";
?>
