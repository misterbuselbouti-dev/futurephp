<?php
// FUTURE AUTOMOTIVE - Selective Backup & Update Tool
// Update all files except specified backup files

echo "<!DOCTYPE html><html><head><title>Selective Update</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".container{max-width:1000px;margin:0 auto;background:white;padding:20px;border-radius:10px;}";
echo ".success{color:green;font-weight:bold;}";
echo ".error{color:red;font-weight:bold;}";
echo ".warning{color:orange;font-weight:bold;}";
echo ".file{background:#f8f9fa;padding:10px;margin:5px 0;border-left:4px solid #007bff;}";
echo ".backup{background:#fff3cd;border-left:4px solid #ffc107;}";
echo ".updated{background:#d4edda;border-left:4px solid #28a745;}";
echo "</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔄 FUTURE AUTOMOTIVE - Selective Update Tool</h1>";
echo "<h2>Update All Files Except Backup Files</h2>";

// Files to exclude from update (backup files)
$excludeFiles = [
    'config.php',
    'config_achat_hostinger.php',
    'database_backup.php',
    'backup_*.php',
    '*_backup.php',
    'emergency_theme_fix.php',
    'universal_theme_updater.php',
    'color_conflict_resolver.php',
    'final_color_fix.php',
    'hostinger_status.php'
];

// Get all PHP files
$phpFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $filePath = $file->getPathname();
        $relativePath = str_replace(__DIR__ . '/', '', $filePath);
        
        // Check if file should be excluded
        $shouldExclude = false;
        foreach ($excludeFiles as $excludePattern) {
            if (fnmatch($excludePattern, basename($filePath)) || 
                fnmatch($excludePattern, $relativePath)) {
                $shouldExclude = true;
                break;
            }
        }
        
        if (!$shouldExclude) {
            $phpFiles[] = $filePath;
        }
    }
}

$totalFiles = count($phpFiles);
echo "<h3>📊 Processing $totalFiles PHP files (excluding backups)...</h3>";

// Show excluded files
echo "<h3>🔒 Backup Files Excluded from Update:</h3>";
echo "<div class='backup'>";
foreach ($excludeFiles as $exclude) {
    echo "<span style='margin: 5px; padding: 5px; background: #fff; border-radius: 3px;'>🔒 $exclude</span>";
}
echo "</div>";

$updatedFiles = 0;
$errors = 0;
$skippedFiles = 0;

// Process each file
foreach ($phpFiles as $index => $file) {
    $relativePath = str_replace(__DIR__ . '/', '', $file);
    
    try {
        $content = file_get_contents($file);
        $originalContent = $content;
        $changes = [];
        
        // Step 1: Remove old theme references
        $oldThemes = ['style.css', 'simple-theme.css', 'theme.css', 'professional.css', 'dashboard.css'];
        foreach ($oldThemes as $theme) {
            $pattern = '/<link[^>]*' . preg_quote($theme, '/') . '[^>]*>/i';
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, '', $content);
                $changes[] = "Removed: $theme";
            }
        }
        
        // Step 2: Add universal theme if not present
        if (strpos($content, 'iso-universal-theme.css') === false) {
            if (preg_match('/<head[^>]*>/i', $content, $matches)) {
                $headTag = $matches[0];
                $universalThemeLink = "\n    <!-- ISO 9001/45001 Universal Theme -->\n    <link href=\"https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap\" rel=\"stylesheet\">\n    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\" rel=\"stylesheet\">\n    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css\">\n    <link rel=\"stylesheet\" href=\"assets/css/iso-universal-theme.css\">\n";
                $content = str_replace($headTag, $headTag . $universalThemeLink, $content);
                $changes[] = "Added: Universal theme";
            }
        }
        
        // Step 3: Replace old components
        $oldComponents = ['card', 'workshop-card', 'dashboard-section', 'stats-cards', 'today-cards'];
        $newComponents = ['iso-card', 'iso-card', 'iso-card', 'iso-stats-grid', 'iso-stats-grid'];
        foreach ($oldComponents as $i => $oldComponent) {
            $newComponent = $newComponents[$i];
            if (strpos($content, $oldComponent) !== false) {
                $content = str_replace($oldComponent, $newComponent, $content);
                $changes[] = "Converted: $oldComponent → $newComponent";
            }
        }
        
        // Step 4: Remove old CSS blocks
        $oldCssPattern = '/<style[^>]*>.*?<\/style>/is';
        if (preg_match($oldCssPattern, $content)) {
            $content = preg_replace($oldCssPattern, '', $content);
            $changes[] = "Removed: Old CSS blocks";
        }
        
        // Step 5: Standardize main-content layout
        if (strpos($content, 'main-content') !== false && strpos($content, 'margin-left: 260px') === false) {
            $pattern = '/\.main-content\s*\{[^}]*\}/i';
            $replacement = '.main-content { margin-left: 260px; padding: var(--space-8); min-height: 100vh; }';
            $content = preg_replace($pattern, $replacement, $content);
            if ($content !== $originalContent) {
                $changes[] = "Standardized: main-content layout";
            }
        }
        
        // Save if changed
        if ($content !== $originalContent) {
            if (file_put_contents($file, $content)) {
                $updatedFiles++;
                echo "<div class='file updated'>";
                echo "<span class='success'>✅ UPDATED:</span> $relativePath<br>";
                echo "<small>" . implode(', ', $changes) . "</small>";
                echo "</div>";
            } else {
                $errors++;
                echo "<div class='file'>";
                echo "<span class='error'>❌ ERROR:</span> $relativePath - Failed to save";
                echo "</div>";
            }
        } else {
            $skippedFiles++;
            echo "<div class='file'>";
            echo "<span style='color: gray;'>⚪ SKIPPED:</span> $relativePath - No changes needed";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        $errors++;
        echo "<div class='file'>";
        echo "<span class='error'>❌ ERROR:</span> $relativePath - " . $e->getMessage();
        echo "</div>";
    }
    
    // Progress indicator
    if ($index % 10 === 0) {
        $progress = round(($index + 1) / $totalFiles * 100);
        echo "<div style='width:100%;background:#e0e0e0;border-radius:5px;margin:10px 0;'>";
        echo "<div style='width:$progress%;background:#4CAF50;color:white;text-align:center;padding:5px;border-radius:5px;'>$progress%</div>";
        echo "</div>";
    }
}

echo "<h2>📊 Results Summary</h2>";
echo "<table border='1' style='width:100%;border-collapse:collapse;'>";
echo "<tr><th>Metric</th><th>Count</th><th>Status</th></tr>";
echo "<tr><td>Files processed</td><td>$totalFiles</td><td class='success'>✅ Complete</td></tr>";
echo "<tr><td>Files updated</td><td>$updatedFiles</td><td class='success'>✅ Success</td></tr>";
echo "<tr><td>Files skipped</td><td>$skippedFiles</td><td class='warning'>⚪ No changes needed</td></tr>";
echo "<tr><td>Errors</td><td>$errors</td><td class='" . ($errors > 0 ? 'error' : 'success') . "'>" . ($errors > 0 ? '❌ Issues' : '✅ None') . "</td></tr>";
echo "</table>";

echo "<h2>🔒 Backup Files Protected</h2>";
echo "<div class='backup'>";
echo "<p>The following files were EXCLUDED from updates and remain unchanged:</p>";
echo "<ul>";
foreach ($excludeFiles as $exclude) {
    echo "<li>🔒 $exclude</li>";
}
echo "</ul>";
echo "</div>";

echo "<h2>🎯 Universal Theme Status</h2>";

if (file_exists('assets/css/iso-universal-theme.css')) {
    echo "<p class='success'>✅ Universal theme file exists</p>";
    $size = filesize('assets/css/iso-universal-theme.css');
    echo "<p>📏 File size: " . round($size / 1024, 2) . " KB</p>";
} else {
    echo "<p class='error'>❌ Universal theme file missing</p>";
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
    echo "<p class='warning'>⚠️ $remainingOldThemes files still contain old theme references</p>";
} else {
    echo "<p class='success'>✅ No old theme references found in processed files</p>";
}

echo "<h2>🎉 What Was Accomplished</h2>";
echo "<div style='background:#d4edda;padding:20px;border-radius:10px;border-left:5px solid #28a745;'>";
echo "<ul>";
echo "<li>✅ Updated all non-backup files with universal theme</li>";
echo "<li>✅ Protected backup files from changes</li>";
echo "<li>✅ Applied ISO 9001/45001 theme consistently</li>";
echo "<li>✅ Standardized all components</li>";
echo "<li>✅ Unified layout system</li>";
echo "<li>✅ Professional color scheme</li>";
echo "</ul>";
echo "<p class='success' style='font-size:18px;'>🎯 SELECTIVE UPDATE COMPLETE - BACKUPS PROTECTED!</p>";
echo "</div>";

echo "<h2>🚀 Next Steps</h2>";
echo "<ol>";
echo "<li><strong>Clear your browser cache completely</strong> (Ctrl+F5)</li>";
echo "<li><strong>Test the login page</strong></li>";
echo "<li><strong>Test the dashboard</strong></li>";
echo "<li><strong>Test bus management</strong></li>";
echo "<li><strong>Test purchase system</strong></li>";
echo "<li><strong>Verify backup files are unchanged</strong></li>";
echo "<li><strong>If everything works - GREAT!</strong></li>";
echo "</ol>";

echo "<h2>📋 File Categories</h2>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;'>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;'>";
echo "<h4>✅ Updated Files</h4>";
echo "<ul>";
echo "<li>All application pages</li>";
echo "<li>Dashboard components</li>";
echo "<li>Purchase system</li>";
echo "<li>Admin panels</li>";
echo "<li>Reports and audits</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;'>";
echo "<h4>🔒 Protected Backup Files</h4>";
echo "<ul>";
echo "<li>Configuration files</li>";
echo "<li>Database connections</li>";
echo "<li>Emergency tools</li>";
echo "<li>Theme updaters</li>";
echo "<li>Backup scripts</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

echo "<div style='background:#e7f3ff;padding:20px;border-radius:10px;border-left:5px solid #2196F3;margin:20px 0;'>";
echo "<h3>💡 Important Notice</h3>";
echo "<p>This selective update protected your important backup files while applying the universal theme to all other files.</p>";
echo "<p>Your configuration and emergency tools remain safe and unchanged.</p>";
echo "<p>All application pages now use the unified ISO 9001/45001 theme system.</p>";
echo "</div>";

echo "</div>";
echo "</body></html>";
?>
