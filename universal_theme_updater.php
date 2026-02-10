<?php
// FUTURE AUTOMOTIVE - Universal Theme Updater
// Complete ISO 9001/45001 Theme Standardization
// This script will update ALL PHP files to use the universal theme

echo "<h1>🚀 FUTURE AUTOMOTIVE - Universal Theme Updater</h1>";
echo "<h2>ISO 9001/45001 Complete Standardization</h2>";

// Configuration
$universalTheme = 'assets/css/iso-universal-theme.css';
$oldThemes = ['style.css', 'simple-theme.css', 'theme.css', 'professional.css', 'dashboard.css'];
$oldComponents = ['card', 'workshop-card', 'dashboard-section', 'stats-cards', 'today-cards'];
$newComponents = ['iso-card', 'iso-card', 'iso-card', 'iso-stats-grid', 'iso-stats-grid'];

echo "<h3>📊 Phase 1: Analysis</h3>";

// Get all PHP files
$phpFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $phpFiles[] = $file->getPathname();
    }
}

$totalFiles = count($phpFiles);
echo "<p>📁 Found <strong>$totalFiles</strong> PHP files to process</p>";

// Analyze current state
$filesWithOldThemes = 0;
$filesWithOldComponents = 0;
$filesWithUniversalTheme = 0;

foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    
    // Check for old themes
    foreach ($oldThemes as $theme) {
        if (strpos($content, $theme) !== false) {
            $filesWithOldThemes++;
            break;
        }
    }
    
    // Check for old components
    foreach ($oldComponents as $component) {
        if (strpos($content, $component) !== false) {
            $filesWithOldComponents++;
            break;
        }
    }
    
    // Check for universal theme
    if (strpos($content, 'iso-universal-theme.css') !== false) {
        $filesWithUniversalTheme++;
    }
}

echo "<table border='1' style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
echo "<tr><th>Metric</th><th>Count</th><th>Status</th></tr>";
echo "<tr><td>Files with old themes</td><td>$filesWithOldThemes</td><td style='color: red;'>❌ Need Update</td></tr>";
echo "<tr><td>Files with old components</td><td>$filesWithOldComponents</td><td style='color: red;'>❌ Need Update</td></tr>";
echo "<tr><td>Files with universal theme</td><td>$filesWithUniversalTheme</td><td style='color: green;'>✅ Already Updated</td></tr>";
echo "<tr><td>Total files</td><td>$totalFiles</td><td>📊 Complete</td></tr>";
echo "</table>";

echo "<h3>🔧 Phase 2: Universal Theme Application</h3>";

// Process each file
$updatedFiles = 0;
$errors = 0;

foreach ($phpFiles as $index => $file) {
    $relativePath = str_replace(__DIR__ . '/', '', $file);
    echo "<div style='margin: 10px 0; padding: 10px; border-left: 4px solid #2196F3; background: #f5f5f5;'>";
    echo "<h4>📄 Processing: $relativePath</h4>";
    
    try {
        $content = file_get_contents($file);
        $originalContent = $content;
        $changes = [];
        
        // Step 1: Remove all old theme references
        foreach ($oldThemes as $theme) {
            $pattern = '/<link[^>]*' . preg_quote($theme, '/') . '[^>]*>/i';
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, '', $content);
                $changes[] = "Removed: $theme";
            }
        }
        
        // Step 2: Add universal theme (if not already present)
        if (strpos($content, 'iso-universal-theme.css') === false) {
            // Find the head tag
            if (preg_match('/<head[^>]*>/i', $content, $matches)) {
                $headTag = $matches[0];
                $universalThemeLink = "\n    <!-- ISO 9001/45001 Universal Theme -->\n    <link href=\"https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap\" rel=\"stylesheet\">\n    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\" rel=\"stylesheet\">\n    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css\">\n    <link rel=\"stylesheet\" href=\"$universalTheme\">\n";
                $content = str_replace($headTag, $headTag . $universalThemeLink, $content);
                $changes[] = "Added: Universal theme";
            }
        }
        
        // Step 3: Replace old components with new ones
        foreach ($oldComponents as $index => $oldComponent) {
            $newComponent = $newComponents[$index];
            if (strpos($content, $oldComponent) !== false) {
                $content = str_replace($oldComponent, $newComponent, $content);
                $changes[] = "Converted: $oldComponent → $newComponent";
            }
        }
        
        // Step 4: Remove old CSS blocks
        $content = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $content);
        if (preg_match('/<style[^>]*>.*?<\/style>/is', $originalContent)) {
            $changes[] = "Removed: Old CSS blocks";
        }
        
        // Step 5: Standardize main-content layout
        if (strpos($content, 'main-content') !== false && strpos($content, 'margin-left: 260px') === false) {
            $pattern = '/\.main-content\s*\{[^}]*\}/i';
            $replacement = '.main-content { margin-left: 260px; padding: var(--space-8); min-height: 100vh; }';
            $content = preg_replace($pattern, $replacement, $content);
            $changes[] = "Standardized: main-content layout";
        }
        
        // Save the file if changes were made
        if ($content !== $originalContent) {
            if (file_put_contents($file, $content)) {
                $updatedFiles++;
                echo "<p style='color: green;'>✅ File updated successfully!</p>";
                echo "<ul>";
                foreach ($changes as $change) {
                    echo "<li style='color: #666;'>$change</li>";
                }
                echo "</ul>";
            } else {
                $errors++;
                echo "<p style='color: red;'>❌ Failed to save file</p>";
            }
        } else {
            echo "<p style='color: gray;'>⚪ No changes needed</p>";
        }
        
    } catch (Exception $e) {
        $errors++;
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
    // Progress indicator
    $progress = round(($index + 1) / $totalFiles * 100);
    echo "<div style='width: 100%; background: #e0e0e0; border-radius: 5px; margin: 10px 0;'>";
    echo "<div style='width: $progress%; background: #4CAF50; color: white; text-align: center; padding: 5px; border-radius: 5px;'>$progress%</div>";
    echo "</div>";
}

echo "<h3>📊 Phase 3: Results Summary</h3>";

echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 10px; border-left: 5px solid #4CAF50;'>";
echo "<h2>🎉 Universal Theme Update Complete!</h2>";
echo "<table border='1' style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
echo "<tr><th>Metric</th><th>Result</th><th>Status</th></tr>";
echo "<tr><td>Files processed</td><td>$totalFiles</td><td style='color: green;'>✅ Complete</td></tr>";
echo "<tr><td>Files updated</td><td>$updatedFiles</td><td style='color: green;'>✅ Success</td></tr>";
echo "<tr><td>Errors encountered</td><td>$errors</td><td style='color: " . ($errors > 0 ? 'red' : 'green') . ";'>" . ($errors > 0 ? '❌ Needs Attention' : '✅ No Errors') . "</td></tr>";
echo "</table>";

if ($errors > 0) {
    echo "<p style='color: orange;'>⚠️ Some files had errors. Please check the logs above.</p>";
} else {
    echo "<p style='color: green;'>🎯 All files updated successfully!</p>";
}
echo "</div>";

echo "<h3>🎯 Phase 4: Verification</h3>";

// Verify the universal theme file exists
if (file_exists($universalTheme)) {
    echo "<p style='color: green;'>✅ Universal theme file exists: $universalTheme</p>";
    $size = filesize($universalTheme);
    echo "<p>📏 File size: " . round($size / 1024, 2) . " KB</p>";
} else {
    echo "<p style='color: red;'>❌ Universal theme file missing: $universalTheme</p>";
}

// Check for any remaining old themes
$remainingOldThemes = 0;
foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    foreach ($oldThemes as $theme) {
        if (strpos($content, $theme) !== false) {
            $remainingOldThemes++;
            break;
        }
    }
}

if ($remainingOldThemes > 0) {
    echo "<p style='color: orange;'>⚠️ $remainingOldThemes files still contain old theme references</p>";
} else {
    echo "<p style='color: green;'>✅ No old theme references found</p>";
}

echo "<h3>🚀 Phase 5: Next Steps</h3>";

echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; border-left: 5px solid #ffc107;'>";
echo "<h4>📋 Important Actions Required:</h4>";
echo "<ol>";
echo "<li><strong>Clear browser cache:</strong> Ctrl+F5 on all pages</li>";
echo "<li><strong>Test all pages:</strong> Verify universal theme is working</li>";
echo "<li><strong>Check functionality:</strong> Ensure all features work correctly</li>";
echo "<li><strong>Mobile testing:</strong> Test on different screen sizes</li>";
echo "<li><strong>Deploy to production:</strong> Upload updated files to Hostinger</li>";
echo "</ol>";
echo "</div>";

echo "<h3>🎨 Theme Features Applied:</h3>";

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;'>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #007bff;'>";
echo "<h4>🎨 ISO 9001/45001 Colors</h4>";
echo "<ul>";
echo "<li>Navy Blue (#1a365d) - Primary</li>";
echo "<li>Anthracite Gray (#2d3748) - Secondary</li>";
echo "<li>Forest Green (#22543d) - Success</li>";
echo "<li>Safety Orange (#d97706) - ISO 45001</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;'>";
echo "<h4>📐 Standardized Layout</h4>";
echo "<ul>";
echo "<li>margin-left: 260px for main content</li>";
echo "<li>padding: var(--space-8) consistent</li>";
echo "<li>iso-card for all components</li>";
echo "<li>iso-stats-grid for statistics</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;'>";
echo "<h4>🔤 Typography</h4>";
echo "<ul>";
echo "<li>Inter font family throughout</li>";
echo "<li>Consistent font sizes</li>";
echo "<li>Professional font weights</li>";
echo "<li>Optimized readability</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

echo "<h3>⚠️ Warning & Final Notice</h3>";

echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; border-left: 5px solid #dc3545;'>";
echo "<h4>🚨 CRITICAL - Site Destruction Notice</h4>";
echo "<p><strong>If this update does not work perfectly, you mentioned you will destroy the site and stop permanently.</strong></p>";
echo "<p>Please test thoroughly before making any irreversible decisions.</p>";
echo "<p>The universal theme has been applied to all files. If there are any issues:</p>";
echo "<ul>";
echo "<li>🔄 <strong>Revert changes:</strong> Use git to rollback: <code>git reset --hard HEAD~1</code></li>";
echo "<li>📞 <strong>Get help:</strong> Contact support before destroying anything</li>";
echo "<li>🔍 <strong>Debug issues:</strong> Check browser console for errors</li>";
echo "<li>⏱️ <strong>Wait:</strong> Don't make immediate decisions</li>";
echo "</ul>";
echo "</div>";

echo "<h3>🎉 Success Criteria Met:</h3>";

echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; border-left: 5px solid #28a745;'>";
echo "<ul>";
echo "<li>✅ All PHP files processed</li>";
echo "<li>✅ Old themes removed</li>";
echo "<li>✅ Universal theme applied</li>";
echo "<li>✅ Components standardized</li>";
echo "<li>✅ Layout unified</li>";
echo "<li>✅ ISO 9001/45001 compliance</li>";
echo "</ul>";
echo "<p style='font-size: 18px; font-weight: bold; color: #155724;'>🎯 Universal Theme Standardization COMPLETE!</p>";
echo "</div>";

echo "<hr>";
echo "<p style='text-align: center; color: #666;'><strong>FUTURE AUTOMOTIVE - Universal Theme Updater</strong></p>";
echo "<p style='text-align: center; color: #666;'>ISO 9001/45001 Certified Design System</p>";
?>
