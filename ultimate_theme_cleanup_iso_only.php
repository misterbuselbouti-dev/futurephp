<?php
// FUTURE AUTOMOTIVE - Ultimate Theme Cleanup - ISO Only
// حذف جميع الثيمات باستثناء ISO 9001 وتعميمه على جميع الصفحات

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

$page_title = 'Ultimate Theme Cleanup - ISO Only';

// Define ISO theme files (KEEP THESE)
$iso_theme_files = [
    'assets/css/iso-theme.css' => 'ISO 9001 Theme (KEEP)',
    'assets/css/iso-components.css' => 'ISO Components (KEEP)',
    'assets/css/iso-bootstrap.css' => 'ISO Bootstrap (KEEP)'
];

// Define all other theme files (DELETE THESE)
$theme_files_to_delete = [
    'assets/css/style.css' => 'Old Theme',
    'assets/css/simple-theme.css' => 'Simple Theme',
    'assets/css/admin-theme.css' => 'Admin Theme',
    'assets/css/bootstrap-theme.css' => 'Bootstrap Theme',
    'assets/css/professional.css' => 'Professional Theme'
];

// Define old CSS patterns to remove from PHP files
$old_css_patterns = [
    '/<link[^>]*href=["\'][^"\']*style\.css["\'][^>]*>/i',
    '/<link[^>]*href=["\'][^"\']*simple-theme\.css["\'][^>]*>/i',
    '/<link[^>]*href=["\'][^"\']*admin-theme\.css["\'][^>]*>/i',
    '/<link[^>]*href=["\'][^"\']*bootstrap-theme\.css["\'][^>]*>/i',
    '/<link[^>]*href=["\'][^"\']*professional\.css["\'][^>]*>/i'
];

// Function to backup file
function backupFile($filepath) {
    if (!file_exists($filepath)) {
        return false;
    }
    
    $backup_path = $filepath . '.backup.' . date('Y-m-d-H-i-s');
    return copy($filepath, $backup_path);
}

// Function to apply ISO theme to PHP file
function applyISOTheme($filepath) {
    if (!file_exists($filepath)) {
        return ['success' => false, 'message' => 'File not found'];
    }
    
    // Create backup
    if (!backupFile($filepath)) {
        return ['success' => false, 'message' => 'Failed to create backup'];
    }
    
    $content = file_get_contents($filepath);
    $original_content = $content;
    
    // Remove all old CSS includes
    foreach ($GLOBALS['old_css_patterns'] as $pattern) {
        $content = preg_replace($pattern, '', $content);
    }
    
    // Ensure ISO theme is included
    if (strpos($content, 'iso-theme.css') === false) {
        // Add ISO theme after Bootstrap CSS
        $iso_css_includes = "\n    <!-- ISO 9001 Professional Design System -->\n    <link rel=\"stylesheet\" href=\"assets/css/iso-theme.css\">\n    <link rel=\"stylesheet\" href=\"assets/css/iso-components.css\">\n    <link rel=\"stylesheet\" href=\"assets/css/iso-bootstrap.css\">";
        
        $content = preg_replace(
            '/(<link[^>]*bootstrap[^>]*>)/',
            '$1' . $iso_css_includes,
            $content
        );
    }
    
    // Update component classes to ISO
    $class_mappings = [
        'workshop-card' => 'iso-card',
        'stats-grid' => 'iso-stats-grid',
        'page-header' => 'iso-page-header',
        'btn-primary-custom' => 'iso-btn-primary',
        'btn-secondary-custom' => 'iso-btn-secondary',
        'card' => 'iso-card',
        'stats-cards' => 'iso-stats-grid'
    ];
    
    foreach ($class_mappings as $old_class => $new_class) {
        $content = preg_replace("/class=\"[^\"]*\\b{$old_class}\\b[^\"]*\"/", 'class="' . $new_class . '"', $content);
    }
    
    // Update main-content for ISO theme
    $content = preg_replace('/margin-left:\s*250px;/', 'margin-left: 260px;', $content);
    $content = preg_replace('/margin-left:\s*260px;/', 'margin-left: 260px;', $content);
    $content = preg_replace('/padding:\s*20px;/', 'padding: var(--space-8);', $content);
    $content = preg_replace('/padding:\s*2rem;/', 'padding: var(--space-8);', $content);
    
    // Clean up extra whitespace
    $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
    
    // Save the updated content
    if (file_put_contents($filepath, $content)) {
        return [
            'success' => true, 
            'message' => 'ISO theme applied successfully',
            'changes' => $content !== $original_content
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to save file'];
    }
}

// Function to delete theme file
function deleteThemeFile($filepath) {
    if (!file_exists($filepath)) {
        return ['success' => false, 'message' => 'File not found'];
    }
    
    // Create backup first
    if (!backupFile($filepath)) {
        return ['success' => false, 'message' => 'Failed to create backup'];
    }
    
    // Delete the file
    if (unlink($filepath)) {
        return ['success' => true, 'message' => 'File deleted successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to delete file'];
    }
}

// Get all PHP files
function getAllPHPFiles($dir) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
    
    return $files;
}

// Handle cleanup operations
$cleanup_results = [];
$deleted_files = [];
$applied_files = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_old_themes'])) {
        // Delete old theme files
        foreach ($theme_files_to_delete as $file => $description) {
            $filepath = __DIR__ . '/' . $file;
            $result = deleteThemeFile($filepath);
            $result['file'] = $file;
            $result['description'] = $description;
            $deleted_files[] = $result;
        }
    }
    
    if (isset($_POST['apply_iso_to_all'])) {
        // Apply ISO theme to all PHP files
        $php_files = getAllPHPFiles(__DIR__);
        
        foreach ($php_files as $filepath) {
            $result = applyISOTheme($filepath);
            $result['file'] = str_replace(__DIR__ . '/', '', $filepath);
            $applied_files[] = $result;
        }
    }
    
    if (isset($_POST['ultimate_cleanup'])) {
        // Do both operations
        $_POST['delete_old_themes'] = true;
        $_POST['apply_iso_to_all'] = true;
        
        // Delete old theme files
        foreach ($theme_files_to_delete as $file => $description) {
            $filepath = __DIR__ . '/' . $file;
            $result = deleteThemeFile($filepath);
            $result['file'] = $file;
            $result['description'] = $description;
            $deleted_files[] = $result;
        }
        
        // Apply ISO theme to all PHP files
        $php_files = getAllPHPFiles(__DIR__);
        
        foreach ($php_files as $filepath) {
            $result = applyISOTheme($filepath);
            $result['file'] = str_replace(__DIR__ . '/', '', $filepath);
            $applied_files[] = $result;
        }
    }
}

// Check current theme files status
$theme_status = [];
foreach ($iso_theme_files as $file => $description) {
    $filepath = __DIR__ . '/' . $file;
    $theme_status[$file] = [
        'exists' => file_exists($filepath),
        'description' => $description,
        'size' => file_exists($filepath) ? filesize($filepath) : 0,
        'keep' => true
    ];
}

foreach ($theme_files_to_delete as $file => $description) {
    $filepath = __DIR__ . '/' . $file;
    $theme_status[$file] = [
        'exists' => file_exists($filepath),
        'description' => $description,
        'size' => file_exists($filepath) ? filesize($filepath) : 0,
        'keep' => false
    ];
}

// Count files with old CSS references
$php_files_with_old_css = 0;
$php_files = getAllPHPFiles(__DIR__);

foreach ($php_files as $filepath) {
    $content = file_get_contents($filepath);
    foreach ($old_css_patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            $php_files_with_old_css++;
            break;
        }
    }
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
        
        .iso-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-lg);
            padding: var(--space-6);
            margin-bottom: var(--space-6);
            transition: transform 0.2s;
        }
        
        .iso-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .file-exists {
            background: var(--success);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius);
            font-size: var(--text-sm);
        }
        
        .file-missing {
            background: var(--danger);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius);
            font-size: var(--text-sm);
        }
        
        .file-keep {
            background: var(--info);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius);
            font-size: var(--text-sm);
        }
        
        .file-delete {
            background: #dc2626;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius);
            font-size: var(--text-sm);
        }
        
        .cleanup-result {
            background: var(--bg-tertiary);
            border-left: 4px solid var(--primary);
            padding: var(--space-4);
            margin-bottom: var(--space-3);
            border-radius: var(--radius);
        }
        
        .danger-box {
            background: #fee2e2;
            border: 1px solid #dc2626;
            border-radius: var(--radius);
            padding: var(--space-4);
            margin-bottom: var(--space-4);
        }
        
        .success-box {
            background: #f0fdf4;
            border: 1px solid #16a34a;
            border-radius: var(--radius);
            padding: var(--space-4);
            margin-bottom: var(--space-4);
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
                            <i class="fas fa-broom me-3"></i>
                            Ultimate Theme Cleanup - ISO Only
                        </h1>
                        <p class="text-muted mb-0">Delete all themes except ISO 9001 and apply it universally</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <button class="iso-btn-secondary" onclick="window.location.href='dashboard_iso.php'">
                                <i class="fas fa-eye me-2"></i>Preview ISO Theme
                            </button>
                            <button class="iso-btn-primary" onclick="window.location.href='dashboard_simple.php'">
                                <i class="fas fa-home me-2"></i>Dashboard
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cleanup Results -->
            <?php if (!empty($deleted_files) || !empty($applied_files)): ?>
                <div class="iso-card">
                    <h2 class="mb-4">📊 Cleanup Results</h2>
                    
                    <?php if (!empty($deleted_files)): ?>
                        <h5>Deleted Theme Files:</h5>
                        <?php foreach ($deleted_files as $result): ?>
                            <div class="cleanup-result">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($result['file']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($result['description']); ?></small>
                                    </div>
                                    <div>
                                        <?php if ($result['success']): ?>
                                            <span class="file-exists">✓ Deleted</span>
                                        <?php else: ?>
                                            <span class="file-missing">✗ Failed</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (!$result['success']): ?>
                                    <small class="text-muted"><?php echo htmlspecialchars($result['message']); ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($applied_files)): ?>
                        <h5 class="mt-4">Applied ISO Theme:</h5>
                        <?php 
                        $success_count = array_sum(array_map(function($r) { return $r['success'] ? 1 : 0; }, $applied_files)); 
                        $modified_count = array_sum(array_map(function($r) { return isset($r['changes']) && $r['changes'] ? 1 : 0; }, $applied_files));
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Theme Application Progress</span>
                                <span><?php echo $success_count; ?>/<?php echo count($applied_files); ?> files</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo ($success_count / count($applied_files)) * 100; ?>%"></div>
                            </div>
                            <small class="text-muted"><?php echo $modified_count; ?> files modified</small>
                        </div>
                        
                        <?php if (count($applied_files) <= 10): ?>
                            <?php foreach ($applied_files as $result): ?>
                                <div class="cleanup-result">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($result['file']); ?></strong>
                                            <?php if (isset($result['changes']) && $result['changes']): ?>
                                                <span class="badge bg-info ms-2">Modified</span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <?php if ($result['success']): ?>
                                                <span class="file-exists">✓ Applied</span>
                                            <?php else: ?>
                                                <span class="file-missing">✗ Failed</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <?php echo count($applied_files); ?> PHP files processed. <?php echo $modified_count; ?> files were modified to use ISO theme.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Current Theme Status -->
            <div class="iso-card">
                <h2 class="mb-4">📁 Theme Files Status</h2>
                
                <div class="row">
                    <?php foreach ($theme_status as $file => $status): ?>
                        <div class="col-md-4 mb-3">
                            <div class="iso-card">
                                <h5><?php echo htmlspecialchars($status['description']); ?></h5>
                                <p class="mb-2">
                                    <code><?php echo htmlspecialchars($file); ?></code>
                                </p>
                                <div class="d-flex gap-2">
                                    <?php if ($status['exists']): ?>
                                        <span class="file-exists">Exists</span>
                                        <span class="text-muted"><?php echo number_format($status['size'] / 1024, 2); ?> KB</span>
                                    <?php else: ?>
                                        <span class="file-missing">Not Found</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($status['keep']): ?>
                                        <span class="file-keep">KEEP</span>
                                    <?php else: ?>
                                        <span class="file-delete">DELETE</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- PHP Files Status -->
            <div class="iso-card">
                <h2 class="mb-4">📄 PHP Files Status</h2>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="iso-card">
                            <h5>Files with Old CSS References</h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h3 mb-0"><?php echo $php_files_with_old_css; ?></span>
                                <span class="file-delete">Need ISO Theme</span>
                            </div>
                            <small class="text-muted">Out of <?php echo count($php_files); ?> total PHP files</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="iso-card">
                            <h5>Files Already Using ISO Theme</h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h3 mb-0"><?php echo count($php_files) - $php_files_with_old_css; ?></span>
                                <span class="file-exists">ISO Ready</span>
                            </div>
                            <small class="text-muted">Using ISO theme or no CSS conflicts</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cleanup Actions -->
            <div class="iso-card">
                <h2 class="mb-4">🚀 Ultimate Cleanup Actions</h2>
                
                <div class="danger-box">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>ULTIMATE CLEANUP ZONE</h5>
                    <p class="mb-0">This will permanently delete ALL old themes and apply ISO 9001 theme universally. Automatic backups will be created.</p>
                </div>
                
                <div class="success-box">
                    <h5><i class="fas fa-check-circle me-2"></i>Final Result</h5>
                    <p class="mb-0">After cleanup: Only ISO 9001 theme will exist, applied to all pages with unified professional design.</p>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <h5>Step 1: Delete Old Themes</h5>
                        <p class="text-muted small">Remove style.css, simple-theme.css, admin-theme.css, bootstrap-theme.css, professional.css</p>
                        <form method="POST">
                            <button type="submit" name="delete_old_themes" class="btn btn-danger w-100"
                                    onclick="return confirm('This will delete ALL old theme files. Backup will be created. Continue?')">
                                <i class="fas fa-trash me-2"></i>Delete Old Themes
                            </button>
                        </form>
                    </div>
                    
                    <div class="col-md-4">
                        <h5>Step 2: Apply ISO Theme</h5>
                        <p class="text-muted small">Apply ISO 9001 theme to ALL PHP files universally</p>
                        <form method="POST">
                            <button type="submit" name="apply_iso_to_all" class="btn btn-warning w-100"
                                    onclick="return confirm('This will modify ALL PHP files to use ISO theme. Backup will be created. Continue?')">
                                <i class="fas fa-paint-brush me-2"></i>Apply ISO Theme
                            </button>
                        </form>
                    </div>
                    
                    <div class="col-md-4">
                        <h5>Step 3: Ultimate Cleanup</h5>
                        <p class="text-muted small">Do both operations - complete ISO theme universal application</p>
                        <form method="POST">
                            <button type="submit" name="ultimate_cleanup" class="btn btn-dark w-100"
                                    onclick="return confirm('ULTIMATE CLEANUP: This will delete ALL old themes AND apply ISO theme to ALL files. This action cannot be undone. Continue?')">
                                <i class="fas fa-skull-crossbones me-2"></i>Ultimate Cleanup
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h5>What will remain:</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>iso-theme.css</li>
                                <li><i class="fas fa-check text-success me-2"></i>iso-components.css</li>
                                <li><i class="fas fa-check text-success me-2"></i>iso-bootstrap.css</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>Universal ISO design</li>
                                <li><i class="fas fa-check text-success me-2"></i>No theme conflicts</li>
                                <li><i class="fas fa-check text-success me-2"></i>Professional appearance</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h5>What will be deleted:</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-times text-danger me-2"></i>style.css (Old Theme)</li>
                                <li><i class="fas fa-times text-danger me-2"></i>simple-theme.css (Simple Theme)</li>
                                <li><i class="fas fa-times text-danger me-2"></i>admin-theme.css (Admin Theme)</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-times text-danger me-2"></i>bootstrap-theme.css</li>
                                <li><i class="fas fa-times text-danger me-2"></i>professional.css</li>
                                <li><i class="fas fa-times text-danger me-2"></i>All old CSS references</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Verification -->
            <div class="iso-card">
                <h2 class="mb-4">✅ Final Verification</h2>
                
                <div class="success-box">
                    <h5><i class="fas fa-info-circle me-2"></i>After Ultimate Cleanup</h5>
                    <ul class="mb-0">
                        <li>Only ISO 9001 theme files will exist</li>
                        <li>All PHP files will use ISO theme exclusively</li>
                        <li>No CSS conflicts or theme overlaps</li>
                        <li>Universal professional design across all pages</li>
                        <li>Automatic backups created for all changes</li>
                    </ul>
                </div>
                
                <div class="mt-3">
                    <h5>Test Pages After Cleanup:</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <a href="dashboard_iso.php" class="btn btn-primary btn-sm d-block mb-2">
                                <i class="fas fa-eye me-2"></i>Test ISO Theme
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="login.php" class="btn btn-outline-primary btn-sm d-block mb-2">
                                <i class="fas fa-sign-in-alt me-2"></i>Test Login
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="buses.php" class="btn btn-outline-primary btn-sm d-block mb-2">
                                <i class="fas fa-bus me-2"></i>Test Management
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="achat_bc.php" class="btn btn-outline-primary btn-sm d-block mb-2">
                                <i class="fas fa-shopping-cart me-2"></i>Test Achat
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
