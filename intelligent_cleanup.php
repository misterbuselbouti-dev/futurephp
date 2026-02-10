<?php
// INTELLIGENT CLEANUP - Smart File Removal System
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define constants if not already defined
if (!defined('APP_NAME')) define('APP_NAME', 'FUTURE AUTOMOTIVE');

echo "<!DOCTYPE html>";
echo "<html lang='fr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Nettoyage Intelligent - " . APP_NAME . "</title>";
echo "<link rel='stylesheet' href='assets/css/professional.css'>";
echo "</head>";
echo "<body>";
echo "<div class='container' style='padding: 2rem;'>";
echo "<h1 style='color: var(--primary); margin-bottom: 2rem;'>🧠 Nettoyage Intelligent du Système</h1>";

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
        'fix_breakdown_columns.php'
    ]
];

// Execute cleanup if requested
if (isset($_POST['execute_cleanup'])) {
    echo "<div class='alert alert-info' style='margin-bottom: 2rem;'>";
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
                    echo "<p style='color: var(--success); margin: 0.25rem 0;'>✅ Supprimé: $file (" . number_format($file_size / 1024, 2) . " KB)</p>";
                    $total_removed++;
                    $total_size += $file_size;
                }
            }
        }
        echo "</div>";
    }
    
    echo "<div class='card' style='margin: 2rem 0;'>";
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
    echo "<div class='card' style='margin-bottom: 2rem;'>";
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
        
        echo "<div style='margin-bottom: 1.5rem; padding: 1rem; background: var(--bg-secondary); border-radius: var(--radius);'>";
        echo "<h4 style='color: var(--primary); margin-bottom: 0.5rem;'>" . ucfirst(str_replace('_', ' ', $category)) . "</h4>";
        echo "<p style='margin: 0.5rem 0;'><strong>Fichiers:</strong> $category_count | <strong>Taille:</strong> " . number_format($category_size / 1024, 2) . " KB</p>";
        
        if ($category_count > 0) {
            echo "<details style='margin-top: 0.5rem;'>";
            echo "<summary style='cursor: pointer; color: var(--text-secondary);'>Voir les fichiers</summary>";
            echo "<ul style='margin: 0.5rem 0; padding-left: 1.5rem;'>";
            foreach ($files as $file) {
                if (file_exists(__DIR__ . '/' . $file)) {
                    echo "<li style='color: var(--text-muted);'>$file</li>";
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
    
    // Show remaining core files
    echo "<div class='card'>";
    echo "<div class='card-header'>";
    echo "<h2 class='card-title'>📁 Fichiers Principaux Conservés</h2>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    $core_files = [
        'dashboard_professional.php' => 'Tableau de bord professionnel',
        'login.php' => 'Page de connexion',
        'logout.php' => 'Déconnexion',
        'profile.php' => 'Profil utilisateur',
        'settings.php' => 'Paramètres',
        'config.php' => 'Configuration principale',
        'index.php' => 'Page d\'accueil',
        'admin.php' => 'Administration',
        'employees.php' => 'Gestion des employés',
        'fournisseurs.php' => 'Gestion des fournisseurs',
        'invoices.php' => 'Facturation',
        'notifications.php' => 'Notifications',
        'users_management.php' => 'Gestion des utilisateurs',
        'work_orders.php' => 'Ordres de travail',
        'bus_work_orders.php' => 'Ordres de travail bus',
        'purchase_performance.php' => 'Performance des achats',
        'export_data.php' => 'Export de données',
        'audit_system.php' => 'Audit du système',
        'audit_report.php' => 'Rapport d\'audit',
        'articles_stockables.php' => 'Articles stockables',
        'stock_ksar.php' => 'Stock KSAR',
        'stock_tetouan.php' => 'Stock Tétouan',
        'web_check_password.php' => 'Vérification mot de passe',
        'check_admin_password.php' => 'Vérification admin'
    ];
    
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;'>";
    foreach ($core_files as $file => $description) {
        if (file_exists(__DIR__ . '/' . $file)) {
            echo "<div style='padding: 1rem; border: 1px solid var(--border); border-radius: var(--radius); background: var(--bg-primary);'>";
            echo "<strong style='color: var(--primary);'>$file</strong><br>";
            echo "<small style='color: var(--text-muted);'>$description</small>";
            echo "</div>";
        }
    }
    echo "</div>";
    
    echo "</div>";
    echo "</div>";
    
    // Execute cleanup button
    echo "<div style='text-align: center; margin: 3rem 0;'>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='execute_cleanup' value='1'>";
    echo "<input type='submit' value='🧠 Exécuter le Nettoyage Intelligent' style='background: var(--danger); color: white; padding: 1rem 2rem; border: none; border-radius: var(--radius); font-size: 1.1rem; cursor: pointer; font-weight: 600;'>";
    echo "</form>";
    echo "</div>";
}

echo "</div>";
echo "</body>";
echo "</html>";
?>
