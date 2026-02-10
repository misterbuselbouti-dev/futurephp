<?php
// SMART CLEANUP - Independent File Removal System
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define constants
define('APP_NAME', 'FUTURE AUTOMOTIVE');

echo "<!DOCTYPE html>";
echo "<html lang='fr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Nettoyage Intelligent - " . APP_NAME . "</title>";
echo "<style>";
echo "
:root {
    --primary: #1e3a8a;
    --secondary: #059669;
    --danger: #dc2626;
    --bg-primary: #ffffff;
    --bg-secondary: #f8fafc;
    --text-primary: #1e293b;
    --text-secondary: #475569;
    --text-muted: #64748b;
    --border: #e2e8f0;
    --radius: 0.5rem;
    --shadow: 0 1px 3px rgba(0,0,0,0.1);
}
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--bg-secondary);
    color: var(--text-primary);
    margin: 0;
    padding: 0;
}
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}
.card {
    background: var(--bg-primary);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    margin-bottom: 2rem;
    overflow: hidden;
}
.card-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border);
    background: var(--bg-primary);
}
.card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}
.card-body {
    padding: 1.5rem;
}
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    font-weight: 500;
    text-decoration: none;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    transition: all 0.2s ease;
}
.btn-primary {
    background-color: var(--primary);
    color: white;
}
.btn-danger {
    background-color: var(--danger);
    color: white;
}
.btn:hover {
    opacity: 0.9;
}
.alert {
    padding: 1rem;
    border-radius: var(--radius);
    margin-bottom: 1rem;
}
.alert-info {
    background-color: rgba(2, 132, 199, 0.1);
    border: 1px solid rgba(2, 132, 199, 0.2);
    color: #0284c7;
}
.alert-success {
    background-color: rgba(5, 150, 105, 0.1);
    border: 1px solid rgba(5, 150, 105, 0.2);
    color: var(--secondary);
}
h1 {
    color: var(--primary);
    margin-bottom: 2rem;
    font-size: 2rem;
}
h2 {
    color: var(--text-primary);
    margin-bottom: 1rem;
}
h3 {
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}
p {
    margin-bottom: 1rem;
}
.text-success { color: var(--secondary); }
.text-danger { color: var(--danger); }
.text-muted { color: var(--text-muted); }
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}
.bg-box {
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: var(--radius);
    margin-bottom: 1rem;
}
summary {
    cursor: pointer;
    color: var(--text-secondary);
}
ul {
    margin: 0.5rem 0;
    padding-left: 1.5rem;
}
li {
    margin-bottom: 0.25rem;
}
.text-center { text-align: center; }
";
echo "</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";
echo "<h1>🧠 Nettoyage Intelligent du Système</h1>";

// Smart file classification
$files_to_remove = [
    'test_files' => [
        'test_auth_access.php',
        'test_breakdown_assignments.php',
        'test_breakdown_reports.php',
        'test_da_status_change.php',
        'test_da_system.php',
        'test_driver_breakdown.php',
        'test_drivers_simple.php'
    ],
    'fix_files' => [
        'fix_breakdown_assignments_data.php',
        'fix_breakdown_assignments_structure.php',
        'fix_breakdown_columns.php',
        'fix_breakdown_reports.php',
        'fix_da_database.php',
        'fix_da_ref_renumber.php',
        'fix_remaining_da_issues.php',
        'fix_report_id_references.php',
        'fix_script.php',
        'fix_technician_access.php'
    ],
    'debug_files' => [
        'debug_articles.php',
        'debug_drivers.php',
        'da_status_diagnostic.php',
        'da_system_status.php'
    ],
    'quick_files' => [
        'quick_fix.php',
        'quick_login.php'
    ],
    'duplicate_files' => [
        'dashboard2.php',
        'dashboard_new.php',
        'driver_breakdown_new_old.php'
    ],
    'convert_files' => [
        'convert_all_excel_to_sql.php',
        'convert_excel_to_sql.php'
    ],
    'temp_files' => [
        'cleanup_manager.php',
        'intelligent_cleanup.php',
        'smart_cleanup.php'
    ]
];

// Execute cleanup if requested
if (isset($_POST['execute_cleanup'])) {
    echo "<div class='alert alert-info'>";
    echo "<h3>🚀 Exécution du Nettoyage Intelligent...</h3>";
    echo "</div>";
    
    $total_removed = 0;
    $total_size = 0;
    
    foreach ($files_to_remove as $category => $files) {
        echo "<div style='margin-bottom: 1.5rem;'>";
        echo "<h4 style='color: var(--text-secondary); margin-bottom: 0.5rem;'>Nettoyage: " . ucfirst($category) . "</h4>";
        
        foreach ($files as $file) {
            $file_path = __DIR__ . '/' . $file;
            if (file_exists($file_path)) {
                $file_size = filesize($file_path);
                if (unlink($file_path)) {
                    echo "<p class='text-success' style='margin: 0.25rem 0;'>✅ Supprimé: $file (" . number_format($file_size / 1024, 2) . " KB)</p>";
                    $total_removed++;
                    $total_size += $file_size;
                }
            }
        }
        echo "</div>";
    }
    
    echo "<div class='card'>";
    echo "<div class='card-header'>";
    echo "<h3 class='card-title'>✨ Nettoyage Terminé!</h3>";
    echo "</div>";
    echo "<div class='card-body'>";
    echo "<p><strong>Fichiers supprimés:</strong> $total_removed</p>";
    echo "<p><strong>Espace récupéré:</strong> " . number_format($total_size / 1024, 2) . " KB</p>";
    echo "<div style='margin-top: 1rem;'>";
    echo "<a href='dashboard_professional.php' class='btn btn-primary'>🎯 Aller au Tableau de Bord</a>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
} else {
    // Show analysis
    echo "<div class='card'>";
    echo "<div class='card-header'>";
    echo "<h2 class='card-title'>📊 Analyse des Fichiers à Supprimer</h2>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    $total_files = 0;
    $total_size = 0;
    
    foreach ($files_to_remove as $category => $files) {
        $category_count = 0;
        $category_size = 0;
        
        foreach ($files as $file) {
            $file_path = __DIR__ . '/' . $file;
            if (file_exists($file_path)) {
                $file_size = filesize($file_path);
                $category_count++;
                $category_size += $file_size;
            }
        }
        
        $total_files += $category_count;
        $total_size += $category_size;
        
        echo "<div class='bg-box'>";
        echo "<h4 style='color: var(--primary); margin-bottom: 0.5rem;'>" . ucfirst(str_replace('_', ' ', $category)) . "</h4>";
        echo "<p style='margin: 0.5rem 0;'><strong>Fichiers:</strong> $category_count | <strong>Taille:</strong> " . number_format($category_size / 1024, 2) . " KB</p>";
        
        if ($category_count > 0) {
            echo "<details style='margin-top: 0.5rem;'>";
            echo "<summary>Voir les fichiers</summary>";
            echo "<ul>";
            foreach ($files as $file) {
                if (file_exists(__DIR__ . '/' . $file)) {
                    echo "<li class='text-muted'>$file</li>";
                }
            }
            echo "</ul>";
            echo "</details>";
        }
        echo "</div>";
    }
    
    echo "<div style='background: var(--primary); color: white; padding: 1.5rem; border-radius: var(--radius); margin-top: 1rem;'>";
    echo "<h3 style='margin-top: 0;'>📈 Résumé du Nettoyage</h3>";
    echo "<p><strong>Total des fichiers à supprimer:</strong> $total_files</p>";
    echo "<p><strong>Espace total à récupérer:</strong> " . number_format($total_size / 1024, 2) . " KB</p>";
    echo "</div>";
    
    echo "</div>";
    echo "</div>";
    
    // Execute cleanup button
    echo "<div class='text-center' style='margin: 3rem 0;'>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='execute_cleanup' value='1'>";
    echo "<input type='submit' value='🧠 Exécuter le Nettoyage Intelligent' class='btn btn-danger' style='font-size: 1.1rem; font-weight: 600; padding: 1rem 2rem;'>";
    echo "</form>";
    echo "</div>";
}

echo "</div>";
echo "</body>";
echo "</html>";
?>
