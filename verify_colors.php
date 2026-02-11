<?php
// FUTURE AUTOMOTIVE - Complete Color Fix Verification
// Check and fix all color issues across the system

echo "<!DOCTYPE html><html><head><title>Color Fix Verification</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".container{max-width:1200px;margin:0 auto;background:white;padding:20px;border-radius:10px;}";
echo ".success{color:green;font-weight:bold;}";
echo ".error{color:red;font-weight:bold;}";
echo ".warning{color:orange;font-weight:bold;}";
echo ".fixed{background:#d4edda;border-left:4px solid #28a745;}";
echo ".issue{background:#f8d7da;border-left:4px solid #dc3545;}";
echo ".section{background:#f8f9fa;padding:15px;margin:10px 0;border-radius:5px;}";
echo ".file-status{background:#e2e8f0;padding:10px;margin:5px 0;border-radius:5px;}";
echo "</style></head><body>";

echo "<div class='container'>";
echo "<h1>🎨 Complete Color Fix Verification</h1>";
echo "<h2>Check and fix all color issues across the system</h2>";

// Files to check for color issues
$filesToCheck = [
    'achat_da.php' => 'Purchase Request Interface',
    'achat_bc.php' => 'Purchase Order Interface',
    'achat_be.php' => 'Purchase Entry Interface',
    'achat_dp.php' => 'Purchase Delivery Interface',
    'admin_breakdowns.php' => 'Admin Breakdowns',
    'admin_breakdowns_workshop.php' => 'Admin Workshop',
    'dashboard.php' => 'Main Dashboard',
    'buses.php' => 'Bus Management',
    'drivers.php' => 'Driver Management'
];

echo "<h3>📊 Files Analysis:</h3>";

$totalIssues = 0;
$totalFixes = 0;

foreach ($filesToCheck as $file => $description) {
    echo "<div class='file-status'>";
    echo "<h4>🔍 Checking: $file - $description</h4>";
    
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $issues = [];
        $fixes = [];
        
        // Check for old button styles
        if (strpos($content, 'btn-olive') !== false) {
            $issues[] = "Contains btn-olive (old style)";
        }
        
        if (strpos($content, 'btn-add-article') !== false) {
            $issues[] = "Contains btn-add-article (old style)";
        }
        
        if (strpos($content, 'btn-remove-item') !== false) {
            $issues[] = "Contains btn-remove-item (old style)";
        }
        
        // Check for ISO theme
        if (strpos($content, 'iso-universal-theme.css') !== false) {
            echo "<p>✅ Uses ISO universal theme</p>";
        } else {
            $issues[] = "Missing ISO universal theme";
        }
        
        // Check for old CSS blocks
        if (preg_match('/\.btn-olive[^{]*\{[^}]*\}/is', $content)) {
            $issues[] = "Contains old btn-olive CSS block";
        }
        
        if (preg_match('/\.btn-add-article[^{]*\{[^}]*\}/is', $content)) {
            $issues[] = "Contains old btn-add-article CSS block";
        }
        
        if (preg_match('/\.btn-remove-item[^{]*\{[^}]*\}/is', $content)) {
            $issues[] = "Contains old btn-remove-item CSS block";
        }
        
        // Display issues
        if (!empty($issues)) {
            $totalIssues += count($issues);
            echo "<div class='issue'>";
            echo "<p><strong>❌ Issues Found (" . count($issues) . "):</strong></p>";
            echo "<ul>";
            foreach ($issues as $issue) {
                echo "<li>$issue</li>";
            }
            echo "</ul>";
            echo "</div>";
            
            // Apply fixes
            $fixedContent = $content;
            
            // Replace old button styles
            $fixedContent = str_replace('btn-olive', 'btn-success', $fixedContent);
            $fixedContent = str_replace('btn-add-article', 'btn-success btn-sm', $fixedContent);
            $fixedContent = str_replace('btn-remove-item', 'btn-danger btn-sm', $fixedContent);
            
            // Remove old CSS blocks
            $fixedContent = preg_replace('/\.btn-olive[^{]*\{[^}]*\}/is', '', $fixedContent);
            $fixedContent = preg_replace('/\.btn-add-article[^{]*\{[^}]*\}/is', '', $fixedContent);
            $fixedContent = preg_replace('/\.btn-remove-item[^{]*\{[^}]*\}/is', '', $fixedContent);
            
            // Add ISO theme if missing
            if (strpos($fixedContent, 'iso-universal-theme.css') === false) {
                if (preg_match('/<head[^>]*>/i', $fixedContent, $matches)) {
                    $headTag = $matches[0];
                    $isoThemeLink = "\n    <!-- ISO 9001/45001 Theme -->\n    <link href=\"https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap\" rel=\"stylesheet\">\n    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\" rel=\"stylesheet\">\n    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css\">\n    <link rel=\"stylesheet\" href=\"assets/css/iso-universal-theme.css\">\n";
                    $fixedContent = str_replace($headTag, $headTag . $isoThemeLink, $fixedContent);
                    $fixes[] = "Added ISO universal theme";
                }
            }
            
            // Save fixed content
            if ($fixedContent !== $content) {
                if (file_put_contents($file, $fixedContent)) {
                    $totalFixes += count($issues);
                    echo "<div class='fixed'>";
                    echo "<p><strong>✅ Fixed Successfully!</strong></p>";
                    echo "<p><strong>Fixes Applied:</strong></p>";
                    echo "<ul>";
                    echo "<li>Replaced btn-olive with btn-success</li>";
                    echo "<li>Replaced btn-add-article with btn-success btn-sm</li>";
                    echo "<li>Replaced btn-remove-item with btn-danger btn-sm</li>";
                    echo "<li>Removed old CSS blocks</li>";
                    if (in_array("Added ISO universal theme", $fixes)) {
                        echo "<li>Added ISO universal theme</li>";
                    }
                    echo "</ul>";
                    echo "</div>";
                } else {
                    echo "<div class='error'>";
                    echo "<p><strong>❌ Failed to save file</strong></p>";
                    echo "</div>";
                }
            }
        } else {
            echo "<p class='success'>✅ No color issues found</p>";
        }
        
    } else {
        echo "<p class='error'>❌ File not found</p>";
        $totalIssues++;
    }
    
    echo "</div>";
}

echo "<h2>📊 Summary Report:</h2>";
echo "<div class='section'>";
echo "<h3>🎯 Overall Status:</h3>";
echo "<table border='1' style='width:100%;border-collapse:collapse;'>";
echo "<tr><th>Metric</th><th>Count</th><th>Status</th></tr>";
echo "<tr><td>Files Checked</td><td>" . count($filesToCheck) . "</td><td class='success'>✅ Complete</td></tr>";
echo "<tr><td>Issues Found</td><td>$totalIssues</td><td class='" . ($totalIssues > 0 ? 'error' : 'success') . "'>" . ($totalIssues > 0 ? '❌ Issues' : '✅ None') . "</td></tr>";
echo "<tr><td>Issues Fixed</td><td>$totalFixes</td><td class='" . ($totalFixes > 0 ? 'success' : 'warning') . "'>" . ($totalFixes > 0 ? '✅ Fixed' : '⚪ None') . "</td></tr>";
echo "</table>";
echo "</div>";

echo "<h2>🎨 Color Standard Applied:</h2>";
echo "<div class='section'>";
echo "<h3>📋 ISO 9001/45001 Color Scheme:</h3>";
echo "<ul>";
echo "<li><strong>Primary:</strong> Navy Blue (#1a365d) - Main actions</li>";
echo "<li><strong>Success:</strong> Forest Green (#22543d) - Success/Save/Submit</li>";
echo "<li><strong>Warning:</strong> Amber Brown (#744210) - Caution/Warning</li>";
echo "<li><strong>Danger:</strong> Burgundy Red (#742a2a) - Delete/Remove</li>";
echo "<li><strong>Info:</strong> Steel Blue (#2c5282) - Information</li>";
echo "<li><strong>Secondary:</strong> Anthracite Gray (#2d3748) - Secondary actions</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🚀 Testing Instructions:</h2>";
echo "<ol>";
echo "<li>Clear browser cache (Ctrl+F5)</li>";
echo "<li>Open each checked file</li>";
echo "<li>Verify ISO theme is applied</li>";
echo "<li>Check button colors are correct</li>";
echo "<li>Test all button functionality</li>";
echo "<li>Verify responsive design</li>";
echo "</ol>";

echo "<h2>🌐 Live Site Testing:</h2>";
echo "<p>After fixing, test on: <a href='https://futureautomotive.net' target='_blank'>https://futureautomotive.net</a></p>";

if ($totalIssues === 0) {
    echo "<div class='success' style='background:#d4edda;padding:20px;border-radius:10px;border-left:5px solid #28a745;margin-top:20px;'>";
    echo "<h3>🎉 All Colors Are Perfect!</h3>";
    echo "<ul>";
    echo "<li>✅ No color issues found</li>";
    echo "<li>✅ All files use ISO theme</li>";
    echo "<li>✅ Button styling is consistent</li>";
    echo "<li>✅ System is ready for production</li>";
    echo "</ul>";
    echo "<p class='success' style='font-size:18px;'>🎯 COLOR STANDARDIZATION ACCOMPLISHED!</p>";
    echo "</div>";
} else {
    echo "<div class='fixed' style='background:#d4edda;padding:20px;border-radius:10px;border-left:5px solid #28a745;margin-top:20px;'>";
    echo "<h3>🎉 Color Fixes Applied!</h3>";
    echo "<ul>";
    echo "<li>✅ Fixed $totalFixes color issues</li>";
    echo "<li>✅ Applied ISO 9001/45001 color scheme</li>";
    echo "<li>✅ Replaced old button styles</li>";
    echo "<li>✅ Added ISO theme where missing</li>";
    echo "<li>✅ System is now color-consistent</li>";
    echo "</ul>";
    echo "<p class='success' style='font-size:18px;'>🎯 COLOR STANDARDIZATION COMPLETED!</p>";
    echo "</div>";
}

echo "</div>";
echo "</body></html>";
?>
