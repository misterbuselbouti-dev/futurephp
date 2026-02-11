<?php
// FUTURE AUTOMOTIVE - bc_document.php Status Check
// Verify file exists and is accessible

echo "<!DOCTYPE html><html><head><title>bc_document.php Status</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:10px;}";
echo ".success{color:green;font-weight:bold;}";
echo ".error{color:red;font-weight:bold;}";
echo ".warning{color:orange;font-weight:bold;}";
echo ".file-info{background:#e3f2fd;padding:15px;margin:10px 0;border-radius:5px;}";
echo ".code-block{background:#f5f5f5;padding:10px;border-radius:5px;font-family:monospace;}";
echo "</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔍 bc_document.php Status Check</h1>";

$filePath = __DIR__ . '/includes/bc_document.php';

echo "<h2>📁 File Status:</h2>";

if (file_exists($filePath)) {
    echo "<div class='file-info'>";
    echo "<p class='success'>✅ File exists: " . htmlspecialchars($filePath) . "</p>";
    echo "<p>📊 File size: " . filesize($filePath) . " bytes</p>";
    echo "<p>📅 Last modified: " . date('Y-m-d H:i:s', filemtime($filePath)) . "</p>";
    echo "<p>🔐 Permissions: " . substr(sprintf('%o', fileperms($filePath)), -4) . "</p>";
    echo "</div>";
    
    // Check file content
    $content = file_get_contents($filePath);
    if (strpos($content, 'load_bc_document') !== false) {
        echo "<p class='success'>✅ Function load_bc_document found</p>";
    } else {
        echo "<p class='error'>❌ Function load_bc_document NOT found</p>";
    }
    
    if (strpos($content, 'DatabaseAchat') !== false) {
        echo "<p class='success'>✅ DatabaseAchat class usage found</p>";
    } else {
        echo "<p class='warning'>⚠️ DatabaseAchat class usage NOT found</p>";
    }
    
    // Show first few lines
    echo "<h3>📄 File Content Preview:</h3>";
    echo "<div class='code-block'>";
    $lines = file($filePath);
    for ($i = 0; $i < min(10, count($lines)); $i++) {
        echo htmlspecialchars($lines[$i]) . "<br>";
    }
    echo "...</div>";
    
} else {
    echo "<div class='error'>";
    echo "<p class='error'>❌ File NOT found: " . htmlspecialchars($filePath) . "</p>";
    echo "</div>";
}

echo "<h2>🌐 Server Path Check:</h2>";
$serverPath = '/home/u442210176/domains/futureautomotive.net/public_html/includes/bc_document.php';
echo "<p>🔍 Expected server path: " . htmlspecialchars($serverPath) . "</p>";

echo "<h2>🔧 Git Status:</h2>";
echo "<div class='file-info'>";
echo "<p>📊 Git repository status:</p>";
echo "<div class='code-block'>";
$gitStatus = shell_exec('git status 2>&1');
echo htmlspecialchars($gitStatus);
echo "</div>";
echo "</div>";

echo "<h2>🚀 Deployment Check:</h2>";
echo "<div class='file-info'>";
echo "<p>📋 To ensure file is deployed:</p>";
echo "<ol>";
echo "<li>Check if file exists in server: " . htmlspecialchars($serverPath) . "</li>";
echo "<li>Verify file permissions (should be 644 or 755)</li>";
echo "<li>Clear server cache if needed</li>";
echo "<li>Restart web server if needed</li>";
echo "</ol>";
echo "</div>";

echo "<h2>🔍 Debug Information:</h2>";
echo "<div class='file-info'>";
echo "<p>📁 Current directory: " . __DIR__ . "</p>";
echo "<p>📁 Includes directory: " . __DIR__ . '/includes' . "</p>";
echo "<p>📄 Script name: " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p>🌐 Server name: " . $_SERVER['SERVER_NAME'] . "</p>";
echo "</div>";

echo "<h2>💡 Solution:</h2>";
echo "<div class='file-info'>";
echo "<p>If file exists locally but not on server:</p>";
echo "<ol>";
echo "<li>Upload file manually to server via FTP/SFTP</li>";
echo "<li>Check git deployment status</li>";
echo "<li>Verify file sync with remote repository</li>";
echo "<li>Contact hosting provider if permissions issue</li>";
echo "</ol>";
echo "</div>";

echo "</div>";
echo "</body></html>";
?>
