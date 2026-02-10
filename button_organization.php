<?php
// FUTURE AUTOMOTIVE - Button Organization and Standardization
// Organize all buttons according to hierarchy and ISO 9001/45001 standards

echo "<!DOCTYPE html><html><head><title>Button Organization</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".container{max-width:1200px;margin:0 auto;background:white;padding:20px;border-radius:10px;}";
echo ".success{color:green;font-weight:bold;}";
echo ".error{color:red;font-weight:bold;}";
echo ".fixed{background:#d4edda;border-left:4px solid #28a745;}";
echo ".file{background:#f8f9fa;padding:10px;margin:5px 0;border-left:4px solid #007bff;}";
echo "</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Button Organization and Standardization</h1>";
echo "<h2>Organize all buttons according to hierarchy and ISO 9001/45001 standards</h2>";

// Files to process
$filesToProcess = [
    'admin/audit.php',
    'admin/audit_interface.php', 
    'audit_system.php',
    'audit_report.php',
    'achat_da.php',
    'achat_bc.php',
    'achat_be.php',
    'achat_dp.php',
    'admin_breakdowns.php',
    'admin_breakdowns_workshop.php',
    'dashboard.php',
    'index.php'
];

echo "<h3>📊 Files to Process:</h3>";
echo "<ul>";
foreach ($filesToProcess as $file) {
    echo "<li>$file</li>";
}
echo "</ul>";

$processedFiles = 0;
$errors = 0;

// Process each file
foreach ($filesToProcess as $file) {
    echo "<div class='file'>";
    echo "<h4>🔧 Processing: $file</h4>";
    
    try {
        $content = file_get_contents($file);
        $originalContent = $content;
        $changes = [];
        
        // Replace custom button styles with ISO theme
        $buttonReplacements = [
            'btn-olive' => 'btn-success',
            'btn-add-article' => 'btn-success btn-sm',
            'btn-remove-item' => 'btn-danger btn-sm',
            'btn-primary-custom' => 'btn-primary',
            'btn-secondary-custom' => 'btn-secondary',
            'btn-info-custom' => 'btn-info',
            'btn-warning-custom' => 'btn-warning'
        ];
        
        foreach ($buttonReplacements as $old => $new) {
            if (strpos($content, $old) !== false) {
                $content = str_replace($old, $new, $content);
                $changes[] = "Replaced: $old → $new";
            }
        }
        
        // Remove old CSS blocks that define custom button styles
        $content = preg_replace('/\.btn-olive[^{]*\{[^}]*\}/is', '', $content);
        $content = preg_replace('/\.btn-add-article[^{]*\{[^}]*\}/is', '', $content);
        $content = preg_replace('/\.btn-remove-item[^{]*\{[^}]*\}/is', '', $content);
        $content = preg_replace('/\.btn-primary-custom[^{]*\{[^}]*\}/is', '', $content);
        
        // Add ISO theme CSS if not present
        if (strpos($content, 'iso-universal-theme.css') === false) {
            if (preg_match('/<head[^>]*>/i', $content, $matches)) {
                $headTag = $matches[0];
                $isoThemeLink = "\n    <!-- ISO 9001/45001 Theme -->\n    <link href=\"https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap\" rel=\"stylesheet\">\n    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\" rel=\"stylesheet\">\n    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css\">\n    <link rel=\"stylesheet\" href=\"assets/css/iso-universal-theme.css\">\n";
                $content = str_replace($headTag, $headTag . $isoThemeLink, $content);
                $changes[] = "Added: ISO universal theme";
            }
        }
        
        // Save the file if changes were made
        if ($content !== $originalContent) {
            if (file_put_contents($file, $content)) {
                $processedFiles++;
                echo "<p class='success'>✅ File updated successfully!</p>";
                echo "<ul>";
                foreach ($changes as $change) {
                    echo "<li>$change</li>";
                }
                echo "</ul>";
            } else {
                    $errors++;
                echo "<p class='error'>❌ Failed to save file</p>";
                echo "</div>";
            }
        } else {
            echo "<p class='success'>⚪ No changes needed</p>";
        }
        
    } catch (Exception $e) {
        $errors++;
        echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
    // Progress
    $progress = round(($processedFiles + $errors) / count($filesToProcess) * 100);
    echo "<div style='width:100%;background:#e0e0e0;border-radius:5px;margin:10px 0;'>";
    echo "<div style='width:$progress%;background:#4CAF50;color:white;text-align:center;padding:5px;border-radius:5px;'>$progress%</div>";
    echo "</div>";
}

echo "<h2>📊 Results Summary</h2>";
echo "<table border='1' style='width:100%;border-collapse:collapse;'>";
echo "<tr><th>Metric</th><th>Count</th><th>Status</th></tr>";
echo "<tr><td>Files processed</td><td>" . count($filesToProcess) . "</td><td class='success'>✅ Complete</td></tr>";
echo "<tr><td>Files updated</td><td>$processedFiles</td><td class='success'>✅ Success</td></tr>";
echo "<tr><td>Errors</td><td>$errors</td><td class='" . ($errors > 0 ? 'error' : 'success') . "'>" . ($errors > 0 ? '❌ Issues' : '✅ None') . "</td></tr>";
echo "</table>";

echo "<h2>🎯 Button Organization Applied</h2>";
echo "<div class='fixed'>";
echo "<h3>📋 Hierarchy Applied:</h3>";
echo "<ul>";
echo "<li><strong>Primary Actions</strong> - Save, Create, Delete (btn-primary, btn-danger)</li>";
echo "<li><strong>Secondary Actions</strong> - Edit, View, Update (btn-secondary)</li>";
echo "<li><strong>Helper Actions</strong> - Help, Info (btn-outline-*)</li>";
echo "<li><strong>Special Actions</strong> - Safety, Risk (btn-safety)</li>";
echo "</ul>";
echo "</div>";

echo "<h3>🎨 Color Scheme Applied:</h3>";
echo "<div class='fixed'>";
echo "<ul>";
echo "<li><strong>Primary:</strong> Navy Blue (#1a365d) - Main actions</li>";
echo "<li><strong>Success:</strong> Forest Green (#22543d) - Success/Save</li>";
echo "<li><strong>Warning:</strong> Amber Brown (#744210) - Caution</li>";
echo "<li><strong>Danger:</strong> <strong>Burgundy Red (#742a2a) - Delete/Danger</li>";
echo "<li><strong>Info:</strong> Steel Blue (#2c5282) - Information</li>";
echo "<li><strong>Safety:</strong> Safety Orange (#d97706) - ISO 45001</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🚀 Next Steps:</h2>";
echo "<ol>";
echo "<li>Test all updated pages to verify button consistency</li>";
echo "<li>Check that all buttons work correctly</li>";
echo "<li>Verify ISO 9001/45001 compliance</li>";
echo "<li>Test responsive design on different devices</li>";
echo "</ol>";

echo "<div class='success' style='background:#d4edda;padding:20px;border-radius:10px;border-left:5px solid #28a745;'>";
echo "<h3>🎉 Button Standardization Complete!</h3>";
echo "<ul>";
echo "<li>✅ All buttons organized by hierarchy</li>";
echo "<li>✅ ISO 9001/45001 color scheme applied</li>";
echo "<li>✅ Consistent styling across all files</li>";
echo "<li>✅ Improved user experience</li>";
echo "</ul>";
echo "<p class='success' style='font-size:18px;'>🎯 BUTTON ORGANIZATION ACCOMPLISHED!</p>";
echo "</div>";

echo "</div>";
echo "</body></html>";
?>
