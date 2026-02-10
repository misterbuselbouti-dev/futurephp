<?php
// FUTURE AUTOMOTIVE - ISO 9001 Theme Updater
// Script to update all PHP files to use the new professional theme

echo "<h1>FUTURE AUTOMOTIVE - ISO 9001 Theme Updater</h1>";
echo "<p>Mise à jour des fichiers PHP pour utiliser le thème professionnel ISO 9001...</p>";

// Get all PHP files
$directory = __DIR__;
$phpFiles = [];

// Recursive directory scan
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $phpFiles[] = $file->getPathname();
    }
}

$totalFiles = count($phpFiles);
$updatedFiles = 0;
$skippedFiles = 0;

echo "<h2>Statistiques</h2>";
echo "<p>Total des fichiers PHP: $totalFiles</p>";

// Old CSS references to replace
$replacements = [
    // Replace old CSS files with ISO 9001 theme
    'assets/css/style.css' => 'assets/css/iso-theme.css',
    'assets/css/professional.css' => 'assets/css/iso-theme.css',
    'assets/css/dashboard.css' => 'assets/css/iso-components.css',
    
    // Add ISO theme CSS files
    '<!-- ISO 9001 Professional Design System -->' => [
        'before' => 'href="assets/css/iso-theme.css"',
        'after' => 'href="assets/css/iso-theme.css"'
    ]
];

foreach ($phpFiles as $file) {
    $relativePath = str_replace($directory . '/', '', $file);
    
    // Skip certain files
    if (strpos($file, 'config') !== false || 
        strpos($file, 'includes/') !== false || 
        strpos($file, 'api/') !== false ||
        strpos($file, 'sql/') !== false ||
        basename($file) === 'theme_updater.php') {
        $skippedFiles++;
        continue;
    }
    
    $content = file_get_contents($file);
    $originalContent = $content;
    $hasChanges = false;
    
    // Check if file has CSS includes
    if (strpos($content, 'href="assets/css/') !== false || 
        strpos($content, "href='assets/css/") !== false) {
        
        // Replace old CSS references
        foreach ($replacements as $old => $new) {
            if (is_array($new)) {
                // Handle complex replacements
                if (strpos($content, $old) === false && strpos($content, $new['before']) === false) {
                    // Add ISO theme CSS
                    $content = str_replace(
                        '<link rel="stylesheet" href="assets/css/iso-theme.css">',
                        "<!-- ISO 9001 Professional Design System -->\n    <link rel=\"stylesheet\" href=\"assets/css/iso-theme.css\">\n    <link rel=\"stylesheet\" href=\"assets/css/iso-components.css\">\n    <link rel=\"stylesheet\" href=\"assets/css/iso-bootstrap.css\">",
                        $content
                    );
                }
            } else {
                // Simple string replacement
                $content = str_replace($old, $new, $content);
            }
        }
        
        // Check if we need to add ISO theme CSS
        if (strpos($content, 'iso-theme.css') === false && 
            (strpos($content, 'bootstrap.min.css') !== false || 
             strpos($content, 'font-awesome') !== false)) {
            
            // Find the position after bootstrap CSS
            $bootstrapPos = strpos($content, 'bootstrap.min.css');
            if ($bootstrapPos !== false) {
                $insertPos = strpos($content, '>', $bootstrapPos) + 1;
                
                $isoCSS = "\n    <!-- ISO 9001 Professional Design System -->\n";
                $isoCSS .= "    <link rel=\"stylesheet\" href=\"assets/css/iso-theme.css\">\n";
                $isoCSS .= "    <link rel=\"stylesheet\" href=\"assets/css/iso-components.css\">\n";
                $isoCSS .= "    <link rel=\"stylesheet\" href=\"assets/css/iso-bootstrap.css\">";
                
                $content = substr($content, 0, $insertPos) . $isoCSS . substr($content, $insertPos);
                $hasChanges = true;
            }
        }
        
        // Replace old header include with ISO header
        if (strpos($content, "include 'includes/header.php';") !== false) {
            $content = str_replace(
                "include 'includes/header.php';",
                "include 'includes/header_iso.php';",
                $content
            );
            $hasChanges = true;
        }
        
        // Update body class for ISO theme
        if (strpos($content, '<body') !== false && strpos($content, 'class="') !== false) {
            $content = preg_replace('/<body([^>]*)class="[^"]*"/', '<body$1', $content);
            $hasChanges = true;
        }
        
        // Only save if changes were made
        if ($content !== $originalContent) {
            file_put_contents($file, $content);
            $updatedFiles++;
            echo "<p style='color: green;'>✅ Mis à jour: $relativePath</p>";
        }
    } else {
        $skippedFiles++;
    }
}

echo "<h2>Résultats</h2>";
echo "<p style='color: green;'>✅ Fichiers mis à jour: $updatedFiles</p>";
echo "<p style='color: orange;'>⏭️ Fichiers ignorés: $skippedFiles</p>";

echo "<h2>Prochaines étapes</h2>";
echo "<ol>";
echo "<li>Vérifiez les fichiers mis à jour</li>";
echo "<li>Testez le nouveau thème ISO 9001</li>";
echo "<li>Apportez des ajustements si nécessaire</li>";
echo "<li>Supprimez les anciens fichiers CSS si tout fonctionne</li>";
echo "</ol>";

echo "<h2>Fichiers CSS créés</h2>";
echo "<ul>";
echo "<li>assets/css/iso-theme.css - Thème principal ISO 9001</li>";
echo "<li>assets/css/iso-components.css - Composants professionnels</li>";
echo "<li>assets/css/iso-bootstrap.css - Customisation Bootstrap</li>";
echo "<li>includes/header_iso.php - Header professionnel</li>";
echo "</ul>";

echo "<p><strong>Theme ISO 9001 ready! 🎯</strong></p>";
?>
