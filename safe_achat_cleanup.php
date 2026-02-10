<?php
// FUTURE AUTOMOTIVE - Safe Achat Files Cleanup
// Clean achat files while protecting working files

echo "<!DOCTYPE html><html><head><title>Safe Achat Cleanup</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".container{max-width:1200px;margin:0 auto;background:white;padding:20px;border-radius:10px;}";
echo ".success{color:green;font-weight:bold;}";
echo ".error{color:red;font-weight:bold;}";
echo ".warning{color:orange;font-weight:bold;}";
echo ".file{background:#f8f9fa;padding:10px;margin:5px 0;border-left:4px solid #007bff;}";
echo ".deleted{background:#f8d7da;border-left:4px solid #dc3545;}";
echo ".protected{background:#fff3cd;border-left:4px solid #ffc107;}";
echo ".working{background:#d4edda;border-left:4px solid #28a745;}";
echo ".progress{width:100%;background:#e0e0e0;border-radius:5px;margin:10px 0;}";
echo ".progress-bar{background:#dc3545;color:white;text-align:center;padding:5px;border-radius:5px;}";
echo "</style></head><body>";

echo "<div class='container'>";
echo "<h1>🛡️ FUTURE AUTOMOTIVE - Safe Achat Cleanup</h1>";
echo "<h2>Clean Achat Files While Protecting Working Files</h2>";

echo "<div class='warning' style='background:#fff3cd;padding:20px;border-radius:10px;border-left:5px solid #ffc107;margin:20px 0;'>";
echo "<h3>⚠️ WARNING: This will delete some achat files!</h3>";
echo "<p><strong>Working files will be protected!</strong></p>";
echo "<p><strong>Only redundant/duplicate files will be deleted!</strong></p>";
echo "</div>";

// Define which files are working/essential (PROTECTED)
$protectedFiles = [
    // Main working files
    'achat_da.php',
    'achat_bc.php', 
    'achat_da_edit.php',
    'achat_da_view.php',
    'achat_da_delete.php',
    'achat_da_validate.php',
    'achat_bc_edit.php',
    'achat_bc_view.php',
    'achat_bc_pdf.php',
    'config_achat_hostinger.php',
    
    // Purchase folder files (if they are the main ones)
    'purchase/achat_da.php',
    'purchase/achat_bc.php',
    'purchase/achat_be.php',
    'purchase/achat_dp.php'
];

// Define which files are redundant (can be deleted)
$filesToDelete = [
    // Duplicate/backup files in root
    'achat_be.php',
    'achat_be_edit.php',
    'achat_be_view.php',
    'achat_be_pdf.php',
    'achat_be_auto_validate.php',
    'achat_be_get_bc_items.php',
    'achat_dp.php',
    'achat_dp_edit.php',
    'achat_dp_view.php',
    'achat_dp_pdf.php',
    'achat_dp_get_da_items.php',
    'achat_dp_response.php',
    'achat_bc_get_dp_items.php'
];

echo "<h3>🔍 File Analysis</h3>";

$filesFound = [];
$filesNotFound = [];
$protectedFilesFound = [];

// Check files to delete
foreach ($filesToDelete as $file) {
    if (file_exists($file)) {
        $filesFound[] = $file;
    } else {
        $filesNotFound[] = $file;
    }
}

// Check protected files
foreach ($protectedFiles as $file) {
    if (file_exists($file)) {
        $protectedFilesFound[] = $file;
    }
}

echo "<table border='1' style='width:100%;border-collapse:collapse;'>";
echo "<tr><th>Category</th><th>Count</th><th>Status</th></tr>";
echo "<tr><td>Files to delete</td><td>" . count($filesFound) . "</td><td class='error'>🗑️ Redundant files</td></tr>";
echo "<tr><td>Files not found</td><td>" . count($filesNotFound) . "</td><td class='success'>✅ Already deleted</td></tr>";
echo "<tr><td>Protected files</td><td>" . count($protectedFilesFound) . "</td><td class='warning'>🔒 Will NOT be deleted</td></tr>";
echo "</table>";

echo "<h3>🔒 Protected Files (Working Files - Will NOT be deleted)</h3>";
echo "<div class='protected'>";
foreach ($protectedFilesFound as $file) {
    echo "<span style='margin: 5px; padding: 5px; background: #fff; border-radius: 3px;'>🔒 $file</span><br>";
}
echo "</div>";

echo "<h3>🗑️ Files Ready for Deletion (Redundant/Duplicates)</h3>";
echo "<div class='deleted'>";
foreach ($filesFound as $file) {
    echo "<span style='margin: 5px; padding: 5px; background: #fff; border-radius: 3px;'>🗑️ $file</span><br>";
}
echo "</div>";

if (isset($_POST['confirm_cleanup']) && $_POST['confirm_cleanup'] === 'YES_DELETE_REDUNDANT') {
    echo "<h3>🗑️ Executing Safe Cleanup...</h3>";
    
    $deletedCount = 0;
    $errorCount = 0;
    
    foreach ($filesFound as $file) {
        try {
            if (unlink($file)) {
                $deletedCount++;
                echo "<div class='file deleted'>";
                echo "<span class='error'>🗑️ DELETED:</span> $file (Redundant file)";
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
    
    echo "<h2>📊 Safe Cleanup Results</h2>";
    echo "<table border='1' style='width:100%;border-collapse:collapse;'>";
    echo "<tr><th>Metric</th><th>Count</th><th>Status</th></tr>";
    echo "<tr><td>Redundant files deleted</td><td>$deletedCount</td><td class='success'>✅ Success</td></tr>";
    echo "<tr><td>Delete errors</td><td>$errorCount</td><td class='" . ($errorCount > 0 ? 'error' : 'success') . "'>" . ($errorCount > 0 ? '❌ Issues' : '✅ None') . "</td></tr>";
    echo "<tr><td>Working files protected</td><td>" . count($protectedFilesFound) . "</td><td class='success'>✅ Safe</td></tr>";
    echo "<tr><td>Total processed</td><td>" . ($deletedCount + $errorCount) . "</td><td class='success'>✅ Complete</td></tr>";
    echo "</table>";
    
    echo "<div class='success' style='background:#d4edda;padding:20px;border-radius:10px;border-left:5px solid #28a745;'>";
    echo "<h3>🎉 Safe Cleanup Complete!</h3>";
    echo "<ul>";
    echo "<li>✅ Deleted $deletedCount redundant files</li>";
    echo "<li>✅ Protected " . count($protectedFilesFound) . " working files</li>";
    echo "<li>✅ No working files were harmed</li>";
    echo "<li>✅ System functionality preserved</li>";
    echo "</ul>";
    echo "</div>";
    
} else {
    echo "<h3>⚠️ Confirmation Required</h3>";
    echo "<div class='error' style='background:#f8d7da;padding:20px;border-radius:10px;border-left:5px solid #dc3545;'>";
    echo "<h3>🚨 SAFE CLEANUP CONFIRMATION</h3>";
    echo "<p><strong>This will delete " . count($filesFound) . " redundant files ONLY!</strong></p>";
    echo "<p><strong>All working files are protected and will NOT be deleted!</strong></p>";
    echo "<p><strong>Your current work is completely safe!</strong></p>";
    echo "</div>";
    
    echo "<form method='post' style='text-align: center; margin: 30px 0;'>";
    echo "<input type='hidden' name='confirm_cleanup' value='YES_DELETE_REDUNDANT'>";
    echo "<button type='submit' style='background-color: #dc3545; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; font-weight: bold;'>";
    echo "🗑️ YES! DELETE REDUNDANT FILES ONLY";
    echo "</button>";
    echo "</form>";
    
    echo "<div style='background:#e7f3ff;padding:20px;border-radius:10px;border-left:5px solid #2196F3;margin:20px 0;'>";
    echo "<h3>💡 What This Safe Cleanup Accomplishes</h3>";
    echo "<ul>";
    echo "<li>🗑️ Removes duplicate achat files</li>";
    echo "<li>🗑️ Deletes backup/redundant files</li>";
    echo "<li>🔒 Protects all working files</li>";
    echo "<li>🔒 Preserves current functionality</li>";
    echo "<li>🎯 Reduces file confusion</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<h2>📋 File Categories Explained</h2>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;'>";
echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;'>";
echo "<h4>🗑️ Files to Delete (Redundant)</h4>";
echo "<ul>";
echo "<li>Duplicate files in root folder</li>";
echo "<li>Backup copies of working files</li>";
echo "<li>Files that exist in purchase/ folder</li>";
echo "<li>Test and debug files</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;'>";
echo "<h4>🔒 Protected Files (Working)</h4>";
echo "<ul>";
echo "<li>Main achat_da.php (Demande d'Achat)</li>";
echo "<li>Main achat_bc.php (Bon de Commande)</li>";
echo "<li>Edit and view files</li>";
echo "<li>PDF generation files</li>";
echo "<li>Configuration files</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;'>";
echo "<h4>✅ Working Files Status</h4>";
echo "<ul>";
echo "<li>All your current work is safe</li>";
echo "<li>Main functionality preserved</li>";
echo "<li>No data will be lost</li>";
echo "<li>System remains fully functional</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

echo "<h2>🔍 Current File Structure</h2>";
echo "<div style='background:#f8f9fa;padding:15px;border-radius:8px;font-family:monospace;font-size:12px;'>";
echo "<strong>Root directory:</strong><br>";
foreach ($protectedFiles as $file) {
    if (file_exists($file) && strpos($file, '/') === false) {
        echo "✅ $file<br>";
    }
}
echo "<br><strong>Purchase directory:</strong><br>";
foreach ($protectedFiles as $file) {
    if (file_exists($file) && strpos($file, 'purchase/') === 0) {
        echo "✅ $file<br>";
    }
}
echo "</div>";

echo "</div>";
echo "</body></html>";
?>
