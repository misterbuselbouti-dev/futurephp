<?php
// INTELLIGENT CLEANUP MANAGER - Smart File Analysis & Cleanup
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧠 Intelligent Cleanup Manager</h1>";
echo "<p>Smart analysis and cleanup of redundant files...</p>";

// File categories for intelligent cleanup
$cleanup_categories = [
    'test_files' => [
        'pattern' => 'test_',
        'description' => 'Test files for debugging',
        'files' => []
    ],
    'fix_files' => [
        'pattern' => 'fix_',
        'description' => 'Temporary fix files',
        'files' => []
    ],
    'debug_files' => [
        'pattern' => 'debug_',
        'description' => 'Debug diagnostic files',
        'files' => []
    ],
    'quick_files' => [
        'pattern' => 'quick_',
        'description' => 'Quick fix temporary files',
        'files' => []
    ],
    'duplicate_dashboards' => [
        'pattern' => 'dashboard',
        'description' => 'Duplicate dashboard files',
        'files' => []
    ],
    'old_versions' => [
        'pattern' => '_old',
        'description' => 'Old version files',
        'files' => []
    ],
    'convert_scripts' => [
        'pattern' => 'convert_',
        'description' => 'Data conversion scripts',
        'files' => []
    ]
];

// Scan directory intelligently
$directory = __DIR__;
$files_scanned = [];

foreach (scandir($directory) as $file) {
    if ($file === '.' || $file === '..') continue;
    if (!is_file($directory . '/' . $file)) continue;
    
    $file_path = $directory . '/' . $file;
    $file_size = filesize($file_path);
    $file_modified = filemtime($file_path);
    
    $files_scanned[] = [
        'name' => $file,
        'size' => $file_size,
        'modified' => $file_modified,
        'path' => $file_path
    ];
    
    // Categorize files
    foreach ($cleanup_categories as $category => &$info) {
        if (strpos($file, $info['pattern']) === 0) {
            $info['files'][] = $file;
            break;
        }
    }
}

echo "<h2>📊 Analysis Results</h2>";
echo "<p>Total files scanned: " . count($files_scanned) . "</p>";

// Display analysis
$total_redundant_files = 0;
$total_redundant_size = 0;

foreach ($cleanup_categories as $category => $info) {
    $file_count = count($info['files']);
    $category_size = 0;
    
    foreach ($info['files'] as $file) {
        $file_path = $directory . '/' . $file;
        if (file_exists($file_path)) {
            $category_size += filesize($file_path);
        }
    }
    
    $total_redundant_files += $file_count;
    $total_redundant_size += $category_size;
    
    echo "<div style='border:1px solid #e2e8f0; padding:15px; margin:10px 0; border-radius:8px;'>";
    echo "<h3 style='color:#1e3a8a; margin-top:0;'>{$info['description']}</h3>";
    echo "<p><strong>Files:</strong> $file_count | <strong>Size:</strong> " . number_format($category_size / 1024, 2) . " KB</p>";
    
    if ($file_count > 0) {
        echo "<ul style='margin:10px 0; padding-left:20px;'>";
        foreach ($info['files'] as $file) {
            echo "<li style='color:#64748b;'>$file</li>";
        }
        echo "</ul>";
    }
    echo "</div>";
}

echo "<div style='background:#1e3a8a; color:white; padding:20px; border-radius:8px; margin:20px 0;'>";
echo "<h2 style='margin-top:0;'>🎯 Cleanup Summary</h2>";
echo "<p><strong>Redundant Files:</strong> $total_redundant_files</p>";
echo "<p><strong>Space to Recover:</strong> " . number_format($total_redundant_size / 1024, 2) . " KB</p>";
echo "<p><strong>Core Files Remaining:</strong> " . (count($files_scanned) - $total_redundant_files) . "</p>";
echo "</div>";

// Intelligent cleanup execution
if (isset($_POST['execute_cleanup'])) {
    echo "<h2>🚀 Executing Intelligent Cleanup...</h2>";
    
    $files_removed = 0;
    $space_recovered = 0;
    
    foreach ($cleanup_categories as $category => $info) {
        foreach ($info['files'] as $file) {
            $file_path = $directory . '/' . $file;
            if (file_exists($file_path)) {
                $file_size = filesize($file_path);
                if (unlink($file_path)) {
                    $files_removed++;
                    $space_recovered += $file_size;
                    echo "<p style='color:#059669;'>✅ Removed: $file (" . number_format($file_size / 1024, 2) . " KB)</p>";
                }
            }
        }
    }
    
    echo "<div style='background:#059669; color:white; padding:15px; border-radius:8px; margin:20px 0;'>";
    echo "<h3 style='margin-top:0;'>✨ Cleanup Complete!</h3>";
    echo "<p>Files removed: $files_removed</p>";
    echo "<p>Space recovered: " . number_format($space_recovered / 1024, 2) . " KB</p>";
    echo "</div>";
    
    echo "<p><a href='?' style='background:#1e3a8a; color:white; padding:10px; text-decoration:none; border-radius:5px;'>Refresh Analysis</a></p>";
    
} else {
    echo "<form method='POST' style='margin:30px 0;'>";
    echo "<input type='hidden' name='execute_cleanup' value='1'>";
    echo "<input type='submit' value='🧠 Execute Intelligent Cleanup' style='background:#dc2626; color:white; padding:15px 30px; border:none; border-radius:8px; font-size:16px; cursor:pointer;'>";
    echo "</form>";
}

// Show core files that will remain
echo "<h2>📁 Core Files That Will Remain</h2>";
$core_files = array_filter($files_scanned, function($file) {
    $name = $file['name'];
    foreach ($cleanup_categories as $category => $info) {
        if (strpos($name, $info['pattern']) === 0) {
            return false;
        }
    }
    return true;
});

echo "<div style='display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:10px;'>";
foreach ($core_files as $file) {
    echo "<div style='border:1px solid #e2e8f0; padding:10px; border-radius:5px; background:#f8fafc;'>";
    echo "<strong style='color:#1e3a8a;'>{$file['name']}</strong><br>";
    echo "<small style='color:#64748b;'>" . number_format($file['size'] / 1024, 2) . " KB</small>";
    echo "</div>";
}
echo "</div>";
?>
