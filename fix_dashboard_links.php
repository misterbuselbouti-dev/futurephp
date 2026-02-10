<?php
// FUTURE AUTOMOTIVE - Fix Dashboard Links
// Fix admin_breakdowns.php links in dashboard

echo "<!DOCTYPE html><html><head><title>Fix Dashboard Links</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:10px;}";
echo ".success{color:green;font-weight:bold;}";
echo ".error{color:red;font-weight:bold;}";
echo ".fixed{background:#d4edda;border-left:4px solid #28a745;}";
echo "</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Fix Dashboard Links</h1>";
echo "<h2>Fix admin_breakdowns.php links in dashboard.php</h2>";

$file = 'dashboard.php';

if (file_exists($file)) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Fix all admin_breakdowns.php links to ensure they work correctly
    $patterns = [
        '/href="admin\/admin_breakdowns\.php\?filter_status=nouveau"/' => 'href="admin/admin_breakdowns.php?filter_status=nouveau"',
        '/href="admin\/admin_breakdowns\.php"/' => 'href="admin/admin_breakdowns.php"'
    ];
    
    $changes = [];
    foreach ($patterns as $pattern => $replacement) {
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content);
            $changes[] = "Fixed: " . $pattern;
        }
    }
    
    // Also check if there are any relative path issues
    if (strpos($content, 'href="admin_breakdowns.php"') !== false) {
        $content = str_replace('href="admin_breakdowns.php"', 'href="admin/admin_breakdowns.php"', $content);
        $changes[] = "Fixed: Relative path to admin_breakdowns.php";
    }
    
    if (strpos($content, 'href="./admin_breakdowns.php"') !== false) {
        $content = str_replace('href="./admin_breakdowns.php"', 'href="admin/admin_breakdowns.php"', $content);
        $changes[] = "Fixed: Relative path ./admin_breakdowns.php";
    }
    
    if (strpos($content, 'href="../admin_breakdowns.php"') !== false) {
        $content = str_replace('href="../admin_breakdowns.php"', 'href="admin/admin_breakdowns.php"', $content);
        $changes[] = "Fixed: Relative path ../admin_breakdowns.php";
    }
    
    if ($content !== $originalContent) {
        if (file_put_contents($file, $content)) {
            echo "<div class='fixed'>";
            echo "<h3>✅ Links Fixed Successfully!</h3>";
            echo "<p><strong>Changes made:</strong></p>";
            echo "<ul>";
            foreach ($changes as $change) {
                echo "<li>$change</li>";
            }
            echo "</ul>";
            echo "</div>";
            
            // Show the fixed links
            echo "<h3>📋 Fixed Links:</h3>";
            echo "<pre style='background:#f8f9fa;padding:10px;border-radius:5px;'>";
            echo htmlspecialchars(substr($content, strpos($content, 'admin/admin_breakdowns.php'), 200));
            echo "</pre>";
            
        } else {
            echo "<p class='error'>❌ Failed to save file</p>";
        }
    } else {
        echo "<p class='success'>✅ All links are already correct!</p>";
    }
    
    // Show current links
    echo "<h3>🔍 Current admin_breakdowns.php Links:</h3>";
    preg_match_all('/href="[^"]*admin_breakdowns\.php[^"]*"/', $content, $matches);
    if (!empty($matches[0])) {
        echo "<ul>";
        foreach ($matches[0] as $match) {
            echo "<li>" . htmlspecialchars($match) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No admin_breakdowns.php links found</p>";
    }
    
} else {
    echo "<p class='error'>❌ File not found: $file</p>";
}

echo "<h3>🚀 Testing Instructions:</h3>";
echo "<ol>";
echo "<li>Clear browser cache (Ctrl+F5)</li>";
echo "<li>Go to dashboard.php</li>";
echo "<li>Click on 'Gestion pannes' button</li>";
echo "<li>Verify it goes to admin/admin_breakdowns.php</li>";
echo "<li>Test all other admin_breakdowns.php links</li>";
echo "</ol>";

echo "<h3>🌐 Live Site Testing:</h3>";
echo "<p>After fixing, test on: <a href='https://futureautomotive.net/dashboard.php' target='_blank'>https://futureautomotive.net/dashboard.php</a></p>";

echo "</div>";
echo "</body></html>";
?>
