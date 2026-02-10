<?php
// FUTURE AUTOMOTIVE - ISO 9001 Theme Migrator
// Professional Theme Migration Tool

require_once 'config.php';

// Check authentication
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

$user = get_logged_in_user();
$role = $_SESSION['role'] ?? '';

// Only admin can access this tool
if ($role !== 'admin') {
    http_response_code(403);
    echo 'Accès refusé.';
    exit();
}

$page_title = 'ISO 9001 Theme Migrator';

// Define ISO 9001 theme CSS files
$iso_theme_files = [
    'assets/css/iso-theme.css',
    'assets/css/iso-components.css', 
    'assets/css/iso-bootstrap.css'
];

// Define files to migrate in phases
$migration_phases = [
    'phase1_critical' => [
        'login.php',
        'index.php', 
        'dashboard.php',
        'buses.php',
        'dashboard_simple.php'
    ],
    'phase2_achat' => [
        'achat_da.php',
        'achat_dp.php',
        'achat_bc.php', 
        'achat_be.php',
        'achat_da_edit.php',
        'achat_dp_edit.php',
        'achat_bc_edit.php',
        'achat_da_view.php',
        'achat_dp_view.php',
        'achat_bc_view.php',
        'achat_be_view.php'
    ],
    'phase3_admin' => [
        'admin/dashboard.php',
        'admin/audit.php',
        'admin/work_order_view.php',
        'admin/work_order_edit.php',
        'admin/database_setup.php',
        'admin/simple_theme_update.php'
    ]
];

// Function to backup file
function backupFile($filepath) {
    if (!file_exists($filepath)) {
        return false;
    }
    
    $backup_path = $filepath . '.backup.' . date('Y-m-d-H-i-s');
    return copy($filepath, $backup_path);
}

// Function to migrate file to ISO theme
function migrateToISOTheme($filepath) {
    if (!file_exists($filepath)) {
        return ['success' => false, 'message' => 'File not found'];
    }
    
    // Create backup
    if (!backupFile($filepath)) {
        return ['success' => false, 'message' => 'Failed to create backup'];
    }
    
    $content = file_get_contents($filepath);
    $original_content = $content;
    
    // Remove old theme CSS includes
    $patterns_to_remove = [
        '/<link[^>]*href=["\'][^"\']*style\.css["\'][^>]*>/i',
        '/<link[^>]*href=["\'][^"\']*simple-theme\.css["\'][^>]*>/i',
        '/<link[^>]*href=["\'][^"\']*admin-theme\.css["\'][^>]*>/i',
        '/<link[^>]*href=["\'][^"\']*bootstrap-theme\.css["\'][^>]*>/i'
    ];
    
    foreach ($patterns_to_remove as $pattern) {
        $content = preg_replace($pattern, '', $content);
    }
    
    // Add ISO theme CSS files
    $iso_css_includes = '';
    foreach ($GLOBALS['iso_theme_files'] as $css_file) {
        if (file_exists(__DIR__ . '/' . $css_file)) {
            $iso_css_includes .= "\n    <link rel=\"stylesheet\" href=\"{$css_file}\">";
        }
    }
    
    // Insert ISO theme after Bootstrap CSS
    $content = preg_replace(
        '/(<link[^>]*bootstrap[^>]*>)/',
        '$1' . $iso_css_includes,
        $content
    );
    
    // Update component classes
    $class_mappings = [
        'workshop-card' => 'iso-card',
        'stats-grid' => 'iso-stats-grid',
        'page-header' => 'iso-page-header',
        'btn-primary-custom' => 'iso-btn-primary',
        'btn-secondary-custom' => 'iso-btn-secondary'
    ];
    
    foreach ($class_mappings as $old_class => $new_class) {
        $content = preg_replace("/class=\"[^\"]*\\b{$old_class}\\b[^\"]*\"/", 'class="' . $new_class . '"', $content);
    }
    
    // Update main-content margin and padding
    $content = preg_replace('/margin-left:\s*250px;/', 'margin-left: 260px;', $content);
    $content = preg_replace('/padding:\s*20px;/', 'padding: var(--space-8);', $content);
    
    // Save the migrated content
    if (file_put_contents($filepath, $content)) {
        return [
            'success' => true, 
            'message' => 'Successfully migrated',
            'changes' => $content !== $original_content
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to save file'];
    }
}

// Function to check if file uses ISO theme
function usesISOTheme($filepath) {
    if (!file_exists($filepath)) {
        return false;
    }
    
    $content = file_get_contents($filepath);
    return strpos($content, 'iso-theme.css') !== false;
}

// Handle migration
$migration_results = [];
$current_phase = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['migrate_phase'])) {
        $current_phase = $_POST['phase'];
        $files_to_migrate = $migration_phases[$current_phase] ?? [];
        
        foreach ($files_to_migrate as $file) {
            $filepath = __DIR__ . '/' . $file;
            $result = migrateToISOTheme($filepath);
            $result['file'] = $file;
            $migration_results[] = $result;
        }
    }
    
    if (isset($_POST['migrate_all'])) {
        $all_files = array_merge(...array_values($migration_phases));
        
        foreach ($all_files as $file) {
            $filepath = __DIR__ . '/' . $file;
            $result = migrateToISOTheme($filepath);
            $result['file'] = $file;
            $migration_results[] = $result;
        }
    }
}

// Check current status
$theme_status = [];
foreach ($migration_phases as $phase_name => $files) {
    $phase_status = ['total' => count($files), 'iso' => 0, 'other' => 0];
    
    foreach ($files as $file) {
        $filepath = __DIR__ . '/' . $file;
        if (file_exists($filepath)) {
            if (usesISOTheme($filepath)) {
                $phase_status['iso']++;
            } else {
                $phase_status['other']++;
            }
        }
    }
    
    $theme_status[$phase_name] = $phase_status;
}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo APP_NAME; ?></title>
    
    <!-- ISO 9001 Professional Theme -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- ISO 9001 Design System -->
    <link rel="stylesheet" href="assets/css/iso-theme.css">
    <link rel="stylesheet" href="assets/css/iso-components.css">
    <link rel="stylesheet" href="assets/css/iso-bootstrap.css">
    
    <style>
        .main-content {
            margin-left: 260px;
            padding: var(--space-8);
        }
        
        .phase-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-lg);
            padding: var(--space-6);
            margin-bottom: var(--space-6);
            transition: transform 0.2s;
        }
        
        .phase-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .status-iso {
            background: var(--success);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius);
            font-size: var(--text-sm);
        }
        
        .status-other {
            background: var(--warning);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius);
            font-size: var(--text-sm);
        }
        
        .migration-result {
            background: var(--bg-tertiary);
            border-left: 4px solid var(--primary);
            padding: var(--space-4);
            margin-bottom: var(--space-3);
            border-radius: var(--radius);
        }
        
        .progress-bar {
            background: var(--bg-quaternary);
            border-radius: var(--radius);
            height: 8px;
            overflow: hidden;
            margin: var(--space-2) 0;
        }
        
        .progress-fill {
            background: var(--primary);
            height: 100%;
            transition: width 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: var(--space-4);
            }
        }
    </style>
</head>
<body>
    <!-- Include header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Include sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="iso-page-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="fas fa-palette me-3"></i>
                            ISO 9001 Theme Migrator
                        </h1>
                        <p class="text-muted mb-0">Professional theme migration tool for unified corporate design</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <button class="iso-btn-secondary" onclick="window.location.href='dashboard_iso.php'">
                                <i class="fas fa-eye me-2"></i>Preview Theme
                            </button>
                            <button class="iso-btn-primary" onclick="window.location.href='dashboard_simple.php'">
                                <i class="fas fa-home me-2"></i>Dashboard
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Migration Results -->
            <?php if (!empty($migration_results)): ?>
                <div class="iso-card">
                    <h2 class="mb-4">📊 Migration Results</h2>
                    
                    <?php $success_count = array_sum(array_map(function($r) { return $r['success'] ? 1 : 0; }, $migration_results)); ?>
                    <?php $total_count = count($migration_results); ?>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Migration Progress</span>
                            <span><?php echo $success_count; ?>/<?php echo $total_count; ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo ($success_count / $total_count) * 100; ?>%"></div>
                        </div>
                    </div>
                    
                    <?php foreach ($migration_results as $result): ?>
                        <div class="migration-result">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($result['file']); ?></strong>
                                    <?php if (isset($result['changes']) && $result['changes']): ?>
                                        <span class="badge bg-info ms-2">Modified</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php if ($result['success']): ?>
                                        <span class="status-iso">✓ Success</span>
                                    <?php else: ?>
                                        <span class="status-other">✗ Failed</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!$result['success']): ?>
                                <small class="text-muted"><?php echo htmlspecialchars($result['message']); ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Theme Status Overview -->
            <div class="iso-card">
                <h2 class="mb-4">🎨 Theme Status Overview</h2>
                
                <div class="row">
                    <?php foreach ($theme_status as $phase_name => $status): ?>
                        <div class="col-md-4 mb-3">
                            <div class="phase-card">
                                <h5>
                                    <?php 
                                    $phase_titles = [
                                        'phase1_critical' => 'Phase 1: Critical Pages',
                                        'phase2_achat' => 'Phase 2: Achat Service', 
                                        'phase3_admin' => 'Phase 3: Admin Panel'
                                    ];
                                    echo $phase_titles[$phase_name] ?? $phase_name;
                                    ?>
                                </h5>
                                
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span>Progress</span>
                                        <span><?php echo $status['iso']; ?>/<?php echo $status['total']; ?></span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo ($status['iso'] / $status['total']) * 100; ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <span class="status-iso"><?php echo $status['iso']; ?> ISO</span>
                                    <span class="status-other"><?php echo $status['other']; ?> Other</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Migration Controls -->
            <div class="iso-card">
                <h2 class="mb-4">🚀 Migration Controls</h2>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Important:</strong> Each migration creates automatic backups. The ISO 9001 theme provides a professional corporate design with consistent colors, typography, and spacing.
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <h5>Phase 1: Critical Pages</h5>
                        <p class="text-muted small">Login, dashboard, and main navigation pages</p>
                        <form method="POST">
                            <input type="hidden" name="phase" value="phase1_critical">
                            <button type="submit" name="migrate_phase" class="iso-btn-primary w-100">
                                <i class="fas fa-rocket me-2"></i>Migrate Phase 1
                            </button>
                        </form>
                    </div>
                    
                    <div class="col-md-4">
                        <h5>Phase 2: Achat Service</h5>
                        <p class="text-muted small">All achat_*.php files (11 files)</p>
                        <form method="POST">
                            <input type="hidden" name="phase" value="phase2_achat">
                            <button type="submit" name="migrate_phase" class="iso-btn-primary w-100">
                                <i class="fas fa-shopping-cart me-2"></i>Migrate Phase 2
                            </button>
                        </form>
                    </div>
                    
                    <div class="col-md-4">
                        <h5>Phase 3: Admin Panel</h5>
                        <p class="text-muted small">Admin dashboard and management tools</p>
                        <form method="POST">
                            <input type="hidden" name="phase" value="phase3_admin">
                            <button type="submit" name="migrate_phase" class="iso-btn-primary w-100">
                                <i class="fas fa-cog me-2"></i>Migrate Phase 3
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="mt-4 text-center">
                    <form method="POST" class="d-inline-block">
                        <button type="submit" name="migrate_all" class="iso-btn-secondary btn-lg"
                                onclick="return confirm('This will migrate ALL phases at once. Are you sure?')">
                            <i class="fas fa-magic me-2"></i>Migrate All Phases
                        </button>
                    </form>
                </div>
            </div>

            <!-- File Details -->
            <div class="iso-card">
                <h2 class="mb-4">📁 Migration Details</h2>
                
                <?php foreach ($migration_phases as $phase_name => $files): ?>
                    <div class="mb-4">
                        <h5>
                            <?php 
                            $phase_titles = [
                                'phase1_critical' => 'Phase 1: Critical Pages',
                                'phase2_achat' => 'Phase 2: Achat Service', 
                                'phase3_admin' => 'Phase 3: Admin Panel'
                            ];
                            echo $phase_titles[$phase_name] ?? $phase_name;
                            ?>
                        </h5>
                        
                        <div class="row">
                            <?php foreach ($files as $file): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <code><?php echo htmlspecialchars($file); ?></code>
                                        <?php 
                                        $filepath = __DIR__ . '/' . $file;
                                        if (file_exists($filepath)) {
                                            if (usesISOTheme($filepath)) {
                                                echo '<span class="status-iso">ISO</span>';
                                            } else {
                                                echo '<span class="status-other">Other</span>';
                                            }
                                        } else {
                                            echo '<span class="badge bg-secondary">Missing</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
