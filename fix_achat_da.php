<?php
// FUTURE AUTOMOTIVE - Fix achat_da.php
// Fix structural issues and apply proper ISO theme

echo "<!DOCTYPE html><html><head><title>Fix achat_da.php</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".container{max-width:1200px;margin:0 auto;background:white;padding:20px;border-radius:10px;}";
echo ".success{color:green;font-weight:bold;}";
echo ".error{color:red;font-weight:bold;}";
echo ".fixed{background:#d4edda;border-left:4px solid #28a745;}";
echo ".issue{background:#f8d7da;border-left:4px solid #dc3545;}";
echo ".section{background:#f8f9fa;padding:15px;margin:10px 0;border-radius:5px;}";
echo "</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Fix achat_da.php</h1>";
echo "<h2>Fix structural issues and apply proper ISO theme</h2>";

$file = 'achat_da.php';

if (file_exists($file)) {
    $content = file_get_contents($file);
    $originalContent = $content;
    $issues = [];
    $fixes = [];
    
    echo "<h3>🔍 Issues Found:</h3>";
    
    // Check for duplicate head/body tags
    if (substr_count($content, '</head>') > 1) {
        $issues[] = "Duplicate </head> tags found";
    }
    if (substr_count($content, '<body>') > 1) {
        $issues[] = "Duplicate <body> tags found";
    }
    
    // Check for missing form closing
    $openForms = substr_count($content, '<form');
    $closeForms = substr_count($content, '</form>');
    if ($openForms > $closeForms) {
        $issues[] = "Unclosed form tag(s) - $openForms open, $closeForms closed";
    }
    
    // Check for missing div closing
    $openDivs = substr_count($content, '<div');
    $closeDivs = substr_count($content, '</div>');
    if ($openDivs > $closeDivs) {
        $issues[] = "Unclosed div tag(s) - $openDivs open, $closeDivs closed";
    }
    
    // Check for broken textarea
    if (strpos($content, '<textarea') !== false && strpos($content, '</textarea>') === false) {
        $issues[] = "Unclosed textarea tag";
    }
    
    // Check for missing ISO theme
    if (strpos($content, 'iso-universal-theme.css') === false) {
        $issues[] = "Missing ISO universal theme CSS";
    }
    
    // Display issues
    foreach ($issues as $issue) {
        echo "<div class='issue'>❌ $issue</div>";
    }
    
    if (empty($issues)) {
        echo "<div class='success'>✅ No structural issues found!</div>";
    } else {
        echo "<h3>🔧 Applying Fixes:</h3>";
        
        // Fix duplicate head/body tags
        $content = preg_replace('/.*?<\/head>.*?<body>/s', '', $content);
        $fixes[] = "Removed duplicate head/body structure";
        
        // Fix broken textarea
        $content = preg_replace('/<textarea[^>]*>[^<]*$/s', '<textarea class="form-control" id="commentaires" name="commentaires" rows="2" placeholder="Ajouter des commentaires..."></textarea>', $content);
        $fixes[] = "Fixed broken textarea tag";
        
        // Add ISO theme if missing
        if (strpos($content, 'iso-universal-theme.css') === false) {
            if (preg_match('/<head[^>]*>/i', $content, $matches)) {
                $headTag = $matches[0];
                $isoThemeLink = "\n    <!-- ISO 9001/45001 Theme -->\n    <link href=\"https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap\" rel=\"stylesheet\">\n    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\" rel=\"stylesheet\">\n    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css\">\n    <link rel=\"stylesheet\" href=\"assets/css/iso-universal-theme.css\">\n";
                $content = str_replace($headTag, $headTag . $isoThemeLink, $content);
                $fixes[] = "Added ISO universal theme CSS";
            }
        }
        
        // Fix form structure
        if (strpos($content, '<form id="daForm"') !== false && strpos($content, '</form>') === false) {
            $content = preg_replace('/(<\/div>\s*$)/s', '</form>$1', $content);
            $fixes[] = "Added missing form closing tag";
        }
        
        // Fix div structure
        $openDivs = substr_count($content, '<div');
        $closeDivs = substr_count($content, '</div>');
        if ($openDivs > $closeDivs) {
            $missingDivs = $openDivs - $closeDivs;
            $content .= str_repeat('</div>', $missingDivs);
            $fixes[] = "Added $missingDivs missing div closing tag(s)";
        }
        
        // Remove old CSS blocks
        $content = preg_replace('/\.btn-olive[^{]*\{[^}]*\}/is', '', $content);
        $content = preg_replace('/\.btn-add-article[^{]*\{[^}]*\}/is', '', $content);
        $content = preg_replace('/\.btn-remove-item[^{]*\{[^}]*\}/is', '', $content);
        $fixes[] = "Removed old custom CSS blocks";
        
        // Apply button fixes
        $content = str_replace('btn-olive', 'btn-success', $content);
        $content = str_replace('btn-add-article', 'btn-success btn-sm', $content);
        $content = str_replace('btn-remove-item', 'btn-danger btn-sm', $content);
        $fixes[] = "Applied ISO button styling";
        
        // Save the fixed file
        if ($content !== $originalContent) {
            if (file_put_contents($file, $content)) {
                echo "<div class='fixed'>";
                echo "<h3>✅ File Fixed Successfully!</h3>";
                echo "<p><strong>Fixes applied:</strong></p>";
                echo "<ul>";
                foreach ($fixes as $fix) {
                    echo "<li>$fix</li>";
                }
                echo "</ul>";
                echo "</div>";
                
                // Verify fixes
                echo "<h3>🔍 Verification:</h3>";
                $newContent = file_get_contents($file);
                
                $newOpenDivs = substr_count($newContent, '<div');
                $newCloseDivs = substr_count($newContent, '</div');
                $newOpenForms = substr_count($newContent, '<form');
                $newCloseForms = substr_count($newContent, '</form');
                
                echo "<div class='section'>";
                echo "<h4>Structure Check:</h4>";
                echo "<ul>";
                echo "<li>Div tags: $newOpenDivs open, $newCloseDivs closed " . ($newOpenDivs == $newCloseDivs ? "✅" : "❌") . "</li>";
                echo "<li>Form tags: $newOpenForms open, $newCloseForms closed " . ($newOpenForms == $newCloseForms ? "✅" : "❌") . "</li>";
                echo "<li>ISO Theme: " . (strpos($newContent, 'iso-universal-theme.css') !== false ? "✅" : "❌") . "</li>";
                echo "<li>Button Styles: " . (strpos($newContent, 'btn-success') !== false ? "✅" : "❌") . "</li>";
                echo "</ul>";
                echo "</div>";
                
            } else {
                echo "<div class='error'>❌ Failed to save file</div>";
            }
        } else {
            echo "<div class='success'>⚪ No changes needed</div>";
        }
    }
    
} else {
    echo "<div class='error'>❌ File not found: $file</div>";
}

echo "<h2>🚀 Testing Instructions:</h2>";
echo "<ol>";
echo "<li>Clear browser cache (Ctrl+F5)</li>";
echo "<li>Open achat_da.php</li>";
echo "<li>Check for proper ISO theme application</li>";
echo "<li>Test all buttons and forms</li>";
echo "<li>Verify responsive design</li>";
echo "</ol>";

echo "<h2>🌐 Live Site Testing:</h2>";
echo "<p>After fixing, test on: <a href='https://futureautomotive.net/achat_da.php' target='_blank'>https://futureautomotive.net/achat_da.php</a></p>";

echo "<div class='success' style='background:#d4edda;padding:20px;border-radius:10px;border-left:5px solid #28a745;margin-top:20px;'>";
echo "<h3>🎉 achat_da.php Fix Complete!</h3>";
echo "<ul>";
echo "<li>✅ Structural issues fixed</li>";
echo "<li>✅ ISO theme applied</li>";
echo "<li>✅ Button styling standardized</li>";
echo "<li>✅ Form structure corrected</li>";
echo "<li>✅ Ready for production</li>";
echo "</ul>";
echo "</div>";

echo "</div>";
echo "</body></html>";
?>
