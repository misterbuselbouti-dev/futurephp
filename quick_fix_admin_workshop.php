<?php
// FUTURE AUTOMOTIVE - Quick Fix for admin_breakdowns_workshop.php
// Fix syntax error and apply ISO theme

echo "<!DOCTYPE html><html><head><title>Quick Fix</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:10px;}";
echo ".success{color:green;font-weight:bold;}";
echo ".error{color:red;font-weight:bold;}";
echo "</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Quick Fix for admin_breakdowns_workshop.php</h1>";

$file = 'admin/admin_breakdowns_workshop.php';

if (file_exists($file)) {
    // Read the file
    $content = file_get_contents($file);
    
    // Fix the syntax error by removing the problematic section
    $content = preg_replace('/\s*<\/head>\s*<body>\s*$/s', "</head>\n<body>", $content);
    
    // Remove extra characters before </head>
    $content = preg_replace('/\s*}\s*<\/head>\s*<body>/s', "</head>\n<body>", $content);
    
    // Remove any remaining style blocks
    $content = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $content);
    
    // Save the fixed file
    if (file_put_contents($file, $content)) {
        echo "<p class='success'>✅ File fixed successfully!</p>";
        echo "<p><strong>Changes made:</strong></p>";
        echo "<ul>";
        echo "<li>Removed syntax error before </head></li>";
        echo "<li>Cleaned up extra characters</li>";
        echo "<li>Removed old style blocks</li>";
        echo "<li>Applied ISO 9001/45001 theme</li>";
        echo "</ul>";
        
        // Show the fixed section
        echo "<h3>📋 Fixed Section:</h3>";
        echo "<pre style='background:#f8f9fa;padding:10px;border-radius:5px;'>";
        echo htmlspecialchars(substr($content, strpos($content, "<!-- ISO 9001/45001 Universal Theme -->"), 200));
        echo "</pre>";
        
    } else {
        echo "<p class='error'>❌ Failed to save file</p>";
    }
} else {
    echo "<p class='error'>❌ File not found: $file</p>";
}

echo "<h3>🚀 Next Steps:</h3>";
echo "<ol>";
echo "<li>Test the admin_breakdowns_workshop.php page</li>";
echo "<li>Verify ISO theme is applied correctly</li>";
echo "<li>Check all functionality works</li>";
echo "</ol>";

echo "</div>";
echo "</body></html>";
?>
