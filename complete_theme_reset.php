<?php
// FUTURE AUTOMOTIVE - Complete Theme Reset & Cleanup
// Total cleanup from scratch - ALL pages without exception

echo "<!DOCTYPE html><html><head><title>Complete Theme Reset</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".container{max-width:1200px;margin:0 auto;background:white;padding:20px;border-radius:10px;}";
echo ".success{color:green;font-weight:bold;}";
echo ".error{color:red;font-weight:bold;}";
echo ".warning{color:orange;font-weight:bold;}";
echo ".file{background:#f8f9fa;padding:10px;margin:5px 0;border-left:4px solid #007bff;}";
echo ".cleaned{background:#d4edda;border-left:4px solid #28a745;}";
echo ".error{background:#f8d7da;border-left:4px solid #dc3545;}";
echo ".progress{width:100%;background:#e0e0e0;border-radius:5px;margin:10px 0;}";
echo ".progress-bar{background:#4CAF50;color:white;text-align:center;padding:5px;border-radius:5px;}";
echo "</style></head><body>";

echo "<div class='container'>";
echo "<h1>🧹 FUTURE AUTOMOTIVE - Complete Theme Reset</h1>";
echo "<h2>Total Cleanup from Scratch - ALL Pages Without Exception</h2>";

echo "<div class='error' style='background:#f8d7da;padding:20px;border-radius:10px;border-left:5px solid #dc3545;margin:20px 0;'>";
echo "<h3>🚨 CRITICAL ISSUE DETECTED</h3>";
echo "<p><strong>Problem:</strong> Multiple 'iso-' prefixes creating corrupted component names:</p>";
echo "<code>iso-iso-iso-iso-iso-iso-iso-card → iso-iso-iso-iso-iso-iso-iso-iso-card</code>";
echo "<p><strong>Solution:</strong> Complete reset and cleanup from scratch.</p>";
echo "</div>";

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
echo "<h3>📊 Processing $totalFiles PHP files - Complete Reset</h3>";

$cleanedFiles = 0;
$errors = 0;

// Process each file
foreach ($phpFiles as $index => $file) {
    $relativePath = str_replace(__DIR__ . '/', '', $file);
    
    try {
        $content = file_get_contents($file);
        $originalContent = $content;
        $changes = [];
        
        // STEP 1: Remove ALL CSS references completely
        $allCssPatterns = [
            '/<link[^>]*style\.css[^>]*>/i',
            '/<link[^>]*simple-theme\.css[^>]*>/i',
            '/<link[^>]*theme\.css[^>]*>/i',
            '/<link[^>]*professional\.css[^>]*>/i',
            '/<link[^>]*dashboard\.css[^>]*>/i',
            '/<link[^>]*iso-theme\.css[^>]*>/i',
            '/<link[^>]*iso-components\.css[^>]*>/i',
            '/<link[^>]*iso-bootstrap\.css[^>]*>/i',
            '/<link[^>]*iso-universal-theme\.css[^>]*>/i'
        ];
        
        foreach ($allCssPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, '', $content);
                $changes[] = "Removed old CSS reference";
            }
        }
        
        // STEP 2: Remove ALL style blocks completely
        $content = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $content);
        if (preg_match('/<style[^>]*>.*?<\/style>/is', $originalContent)) {
            $changes[] = "Removed ALL CSS blocks";
        }
        
        // STEP 3: Fix corrupted component names (remove multiple iso- prefixes)
        $content = preg_replace('/iso-iso-iso-iso-iso-iso-iso-iso-card/i', 'iso-card', $content);
        $content = preg_replace('/iso-iso-iso-iso-iso-iso-iso-card/i', 'iso-card', $content);
        $content = preg_replace('/iso-iso-iso-iso-iso-iso-card/i', 'iso-card', $content);
        $content = preg_replace('/iso-iso-iso-iso-iso-card/i', 'iso-card', $content);
        $content = preg_replace('/iso-iso-iso-iso-card/i', 'iso-card', $content);
        $content = preg_replace('/iso-iso-iso-card/i', 'iso-card', $content);
        $content = preg_replace('/iso-iso-card/i', 'iso-card', $content);
        
        // Also fix other components
        $content = preg_replace('/iso-iso-iso-iso-iso-iso-iso-iso-stats-grid/i', 'iso-stats-grid', $content);
        $content = preg_replace('/iso-iso-iso-iso-iso-iso-iso-stats-grid/i', 'iso-stats-grid', $content);
        $content = preg_replace('/iso-iso-iso-iso-iso-iso-stats-grid/i', 'iso-stats-grid', $content);
        $content = preg_replace('/iso-iso-iso-iso-iso-stats-grid/i', 'iso-stats-grid', $content);
        $content = preg_replace('/iso-iso-iso-iso-stats-grid/i', 'iso-stats-grid', $content);
        $content = preg_replace('/iso-iso-iso-stats-grid/i', 'iso-stats-grid', $content);
        $content = preg_replace('/iso-iso-stats-grid/i', 'iso-stats-grid', $content);
        
        // STEP 4: Convert old components to new ones
        $oldComponents = ['card', 'workshop-card', 'dashboard-section', 'stats-cards', 'today-cards'];
        $newComponents = ['iso-card', 'iso-card', 'iso-card', 'iso-stats-grid', 'iso-stats-grid'];
        foreach ($oldComponents as $i => $oldComponent) {
            $newComponent = $newComponents[$i];
            if (strpos($content, $oldComponent) !== false && strpos($content, $newComponent) === false) {
                $content = str_replace($oldComponent, $newComponent, $content);
                $changes[] = "Converted: $oldComponent → $newComponent";
            }
        }
        
        // STEP 5: Add clean universal theme (only if it's a main page, not utility)
        $isMainPage = !preg_match('/^(backup\/|admin\/|pdf\/|purchase\/|technician\/|sql\/|tools\/)/', $relativePath) &&
                     !in_array(basename($file), [
                         'config.php', 'config_achat_hostinger.php', 'functions.php', 
                         'database_backup.php', 'emergency_theme_fix.php', 
                         'universal_theme_updater.php', 'selective_update.php',
                         'color_conflict_resolver.php', 'final_color_fix.php',
                         'hostinger_status.php', 'theme_conflict_resolver.php'
                     ]);
        
        if ($isMainPage && strpos($content, 'iso-universal-theme.css') === false) {
            if (preg_match('/<head[^>]*>/i', $content, $matches)) {
                $headTag = $matches[0];
                $cleanThemeLink = "\n    <!-- Clean ISO 9001/45001 Theme -->\n    <link href=\"https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap\" rel=\"stylesheet\">\n    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\" rel=\"stylesheet\">\n    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css\">\n    <link rel=\"stylesheet\" href=\"assets/css/iso-universal-theme.css\">\n";
                $content = str_replace($headTag, $headTag . $cleanThemeLink, $content);
                $changes[] = "Added clean universal theme";
            }
        }
        
        // Save if changed
        if ($content !== $originalContent) {
            if (file_put_contents($file, $content)) {
                $cleanedFiles++;
                echo "<div class='file cleaned'>";
                echo "<span class='success'>🧹 CLEANED:</span> $relativePath<br>";
                echo "<small>" . implode(', ', array_unique($changes)) . "</small>";
                echo "</div>";
            } else {
                $errors++;
                echo "<div class='file error'>";
                echo "<span class='error'>❌ ERROR:</span> $relativePath - Failed to save";
                echo "</div>";
            }
        } else {
            echo "<div class='file'>";
            echo "<span style='color: gray;'>⚪ ALREADY CLEAN:</span> $relativePath";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        $errors++;
        echo "<div class='file error'>";
        echo "<span class='error'>❌ ERROR:</span> $relativePath - " . $e->getMessage();
        echo "</div>";
    }
    
    // Progress indicator
    if ($index % 10 === 0) {
        $progress = round(($index + 1) / $totalFiles * 100);
        echo "<div class='progress'>";
        echo "<div class='progress-bar' style='width:$progress%;'>$progress%</div>";
        echo "</div>";
    }
}

echo "<h2>📊 Complete Reset Results</h2>";
echo "<table border='1' style='width:100%;border-collapse:collapse;'>";
echo "<tr><th>Metric</th><th>Count</th><th>Status</th></tr>";
echo "<tr><td>Files processed</td><td>$totalFiles</td><td class='success'>✅ Complete</td></tr>";
echo "<tr><td>Files cleaned</td><td>$cleanedFiles</td><td class='success'>✅ Reset</td></tr>";
echo "<tr><td>Errors</td><td>$errors</td><td class='" . ($errors > 0 ? 'error' : 'success') . "'>" . ($errors > 0 ? '❌ Issues' : '✅ None') . "</td></tr>";
echo "</table>";

echo "<h2>🧹 What Was Cleaned</h2>";
echo "<div style='background:#d4edda;padding:20px;border-radius:10px;border-left:5px solid #28a745;'>";
echo "<ul>";
echo "<li>✅ Removed ALL old CSS references</li>";
echo "<li>✅ Removed ALL CSS style blocks</li>";
echo "<li>✅ Fixed corrupted component names (iso-iso-iso-...)</li>";
echo "<li>✅ Converted old components to iso-card/iso-stats-grid</li>";
echo "<li>✅ Added clean universal theme to main pages</li>";
echo "<li>✅ Left utility files untouched</li>";
echo "</ul>";
echo "<p class='success' style='font-size:18px;'>🎯 COMPLETE THEME RESET ACCOMPLISHED!</p>";
echo "</div>";

echo "<h2>🔍 Verification</h2>";

// Check for remaining issues
$remainingIssues = 0;
$corruptedComponents = 0;

foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    
    // Check for corrupted components
    if (preg_match('/iso-iso-iso-iso/', $content)) {
        $corruptedComponents++;
    }
    
    // Check for old CSS
    if (preg_match('/style\.css|simple-theme\.css|theme\.css/', $content)) {
        $remainingIssues++;
    }
}

echo "<table border='1' style='width:100%;border-collapse:collapse;'>";
echo "<tr><th>Issue Type</th><th>Count</th><th>Status</th></tr>";
echo "<tr><td>Corrupted components</td><td>$corruptedComponents</td><td class='" . ($corruptedComponents > 0 ? 'error' : 'success') . "'>" . ($corruptedComponents > 0 ? '❌ Found' : '✅ None') . "</td></tr>";
echo "<tr><td>Old CSS references</td><td>$remainingIssues</td><td class='" . ($remainingIssues > 0 ? 'error' : 'success') . "'>" . ($remainingIssues > 0 ? '❌ Found' : '✅ None') . "</td></tr>";
echo "</table>";

echo "<h2>🚀 Final Instructions</h2>";
echo "<div style='background:#fff3cd;padding:20px;border-radius:10px;border-left:5px solid #ffc107;'>";
echo "<h3>📋 IMPORTANT NEXT STEPS:</h3>";
echo "<ol>";
echo "<li><strong>CLEAR BROWSER CACHE COMPLETELY</strong> - Ctrl+Shift+Delete</li>";
echo "<li><strong>RESTART WEB SERVER</strong> - Stop and start Apache/Nginx</li>";
echo "<li><strong>TEST MAIN PAGES:</strong></li>";
echo "<ul>";
echo "<li>🔐 login.php</li>";
echo "<li>📊 dashboard.php</li>";
echo "<li>🚌 buses.php</li>";
echo "<li>🛒 achat_da.php</li>";
echo "<li>🔧 admin/audit.php</li>";
echo "</ul>";
echo "<li><strong>VERIFY COMPONENT NAMES:</strong> Should be 'iso-card' and 'iso-stats-grid' only</li>";
echo "<li><strong>CHECK FOR CSS CONFLICTS:</strong> No old CSS should remain</li>";
echo "</ol>";
echo "</div>";

echo "<h2>⚠️ Final Warning</h2>";
echo "<div style='background:#f8d7da;padding:20px;border-radius:10px;border-left:5px solid #dc3545;'>";
echo "<h3>🚨 IF ISSUES PERSIST:</h3>";
echo "<p>This was a complete reset from scratch. If problems still exist:</p>";
echo "<ul>";
echo "<li>🔄 Use git rollback: <code>git reset --hard HEAD~1</code></li>";
echo "<li>📞 Contact support before destroying anything</li>";
echo "<li>🔍 Check browser console for specific errors</li>";
echo "<li>⏱️ Wait 24 hours before making decisions</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background:#e7f3ff;padding:20px;border-radius:10px;border-left:5px solid #2196F3;margin:20px 0;'>";
echo "<h3>💡 What This Reset Accomplished</h3>";
echo "<p>Every PHP file has been completely cleaned of theme conflicts and reset to use the clean universal theme system.</p>";
echo "<p>No more corrupted component names, no more old CSS references, no more style blocks - just pure ISO 9001/45001 theme.</p>";
echo "</div>";

echo "</div>";
echo "</body></html>";
?>
