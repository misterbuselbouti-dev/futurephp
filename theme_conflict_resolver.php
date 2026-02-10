<?php
// FUTURE AUTOMOTIVE - Theme Conflict Resolver
// حل تعارضات الثيمات

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

$page_title = 'Theme Conflict Resolver';

// Define theme files
$theme_files = [
    'assets/css/style.css' => 'Old Theme',
    'assets/css/simple-theme.css' => 'Simple Theme',
    'assets/css/admin-theme.css' => 'Admin Theme',
    'assets/css/bootstrap-theme.css' => 'Bootstrap Theme'
];

// Scan for theme conflicts
function scanThemeConflicts() {
    $conflicts = [];
    $files = [];
    
    // Scan all PHP files for CSS includes
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(__DIR__, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            $relativePath = str_replace(__DIR__ . '/', '', $file->getPathname());
            
            // Look for CSS includes
            preg_match_all('/<link[^>]*href=["\']([^"\']*\.css)["\'][^>]*>/i', $content, $matches);
            
            if (!empty($matches[1])) {
                $files[$relativePath] = [
                    'path' => $file->getPathname(),
                    'css_files' => $matches[1],
                    'content' => $content
                ];
                
                // Check for multiple CSS files
                if (count($matches[1]) > 2) {
                    $conflicts[] = [
                        'file' => $relativePath,
                        'issue' => 'Multiple CSS files included',
                        'css_files' => $matches[1]
                    ];
                }
                
                // Check for old theme
                if (in_array('assets/css/style.css', $matches[1]) && in_array('assets/css/simple-theme.css', $matches[1])) {
                    $conflicts[] = [
                        'file' => $relativePath,
                        'issue' => 'Both old and simple theme included',
                        'css_files' => $matches[1]
                    ];
                }
            }
        }
    }
    
    return ['files' => $files, 'conflicts' => $conflicts];
}

// Fix theme conflicts
function fixThemeConflicts($files, $keepSimple = true) {
    $fixed_files = [];
    
    foreach ($files as $relativePath => $fileInfo) {
        $content = $fileInfo['content'];
        $originalContent = $content;
        
        // Remove old theme CSS includes
        if ($keepSimple) {
            // Remove old theme references
            $content = preg_replace('/<link[^>]*href=["\'][^"\']*style\.css["\'][^>]*>/i', '', $content);
            $content = preg_replace('/<link[^>]*href=["\'][^"\']*admin-theme\.css["\'][^>]*>/i', '', $content);
            $content = preg_replace('/<link[^>]*href=["\'][^"\']*bootstrap-theme\.css["\'][^>]*>/i', '', $content);
            
            // Ensure simple theme is included
            if (strpos($content, 'simple-theme.css') === false) {
                // Add simple theme after bootstrap
                $content = preg_replace(
                    '/(<link[^>]*bootstrap[^>]*>)/',
                    '$1' . "\n    <link rel=\"stylesheet\" href=\"assets/css/simple-theme.css\">",
                    $content
                );
            }
        } else {
            // Remove simple theme, keep old theme
            $content = preg_replace('/<link[^>]*href=["\'][^"\']*simple-theme\.css["\'][^>]*>/i', '', $content);
            
            // Ensure old theme is included
            if (strpos($content, 'style.css') === false) {
                $content = preg_replace(
                    '/(<link[^>]*bootstrap[^>]*>)/',
                    '$1' . "\n    <link rel=\"stylesheet\" href=\"assets/css/style.css\">",
                    $content
                );
            }
        }
        
        // Clean up extra whitespace
        $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
        
        if ($content !== $originalContent) {
            // Save the fixed content
            if (file_put_contents($fileInfo['path'], $content)) {
                $fixed_files[] = $relativePath;
            }
        }
    }
    
    return $fixed_files;
}

// Handle form submission
$scan_results = scanThemeConflicts();
$fixed_files = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['fix_conflicts'])) {
        $keepSimple = $_POST['theme_choice'] === 'simple';
        $fixed_files = fixThemeConflicts($scan_results['files'], $keepSimple);
        
        // Re-scan after fixing
        $scan_results = scanThemeConflicts();
    }
    
    if (isset($_POST['delete_old_theme'])) {
        $old_theme_path = __DIR__ . '/assets/css/style.css';
        if (file_exists($old_theme_path)) {
            // Backup first
            $backup_path = __DIR__ . '/assets/css/style.css.backup.' . date('Y-m-d-H-i-s');
            copy($old_theme_path, $backup_path);
            
            // Delete old theme
            if (unlink($old_theme_path)) {
                $success_message = "Old theme file deleted and backed up as: " . basename($backup_path);
            } else {
                $error_message = "Failed to delete old theme file";
            }
        } else {
            $error_message = "Old theme file not found";
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
    
    <!-- Simple Clean Theme -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/simple-theme.css">
    
    <style>
        .main-content {
            margin-left: 260px;
            padding: 2rem;
        }
        
        .workshop-card {
            background-color: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: var(--space-6);
            margin-bottom: var(--space-6);
            transition: transform 0.2s;
        }
        
        .workshop-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .conflict-item {
            background: #fef3c7;
            border-left: 4px solid #d97706;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
        }
        
        .fixed-item {
            background: #f0fdf4;
            border-left: 4px solid #16a34a;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
        }
        
        .css-file {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 0.5rem;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.875rem;
        }
        
        .theme-preview {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
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
            <div class="d-flex justify-content-between align-items-center mb-6">
                <div>
                    <h1 class="mb-2">
                        <i class="fas fa-palette me-3"></i>
                        Theme Conflict Resolver
                    </h1>
                    <p class="text-muted mb-0">Resolve CSS theme conflicts and clean up old themes</p>
                </div>
                <div class="d-flex gap-3">
                    <button class="btn btn-outline-primary" onclick="window.location.href='dashboard_simple.php'">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </button>
                </div>
            </div>

            <!-- Success Messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Error Messages -->
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Fixed Files -->
            <?php if (!empty($fixed_files)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    Successfully fixed <?php echo count($fixed_files); ?> files:
                    <ul class="mb-0 mt-2">
                        <?php foreach ($fixed_files as $file): ?>
                            <li><?php echo htmlspecialchars($file); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Theme Files Status -->
            <div class="workshop-card">
                <h2 class="mb-4">📁 Theme Files Status</h2>
                
                <div class="row">
                    <?php foreach ($theme_files as $filePath => $description): ?>
                        <div class="col-md-6 mb-3">
                            <div class="theme-preview">
                                <h5><?php echo htmlspecialchars($description); ?></h5>
                                <p class="mb-2">
                                    <code><?php echo htmlspecialchars($filePath); ?></code>
                                </p>
                                <div>
                                    <?php if (file_exists(__DIR__ . '/' . $filePath)): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Exists
                                        </span>
                                        <span class="text-muted ms-2">
                                            <?php echo number_format(filesize(__DIR__ . '/' . $filePath) / 1024, 2); ?> KB
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-times me-1"></i>Not Found
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Conflicts -->
            <div class="workshop-card">
                <h2 class="mb-4">⚠️ Theme Conflicts Found</h2>
                
                <?php if (empty($scan_results['conflicts'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        No theme conflicts found! All files are using consistent themes.
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-3">Found <?php echo count($scan_results['conflicts']); ?> conflicts that need to be resolved:</p>
                    
                    <?php foreach ($scan_results['conflicts'] as $conflict): ?>
                        <div class="conflict-item">
                            <h6>
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($conflict['file']); ?>
                            </h6>
                            <p class="mb-2">
                                <strong>Issue:</strong> <?php echo htmlspecialchars($conflict['issue']); ?>
                            </p>
                            <div class="mb-2">
                                <strong>CSS Files:</strong>
                                <?php foreach ($conflict['css_files'] as $css): ?>
                                    <span class="css-file d-inline-block me-2 mb-1">
                                        <?php echo htmlspecialchars($css); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="mt-4">
                        <h5>Choose which theme to keep:</h5>
                        <form method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="theme_choice" id="theme_simple" value="simple" checked>
                                        <label class="form-check-label" for="theme_simple">
                                            <strong>Keep Simple Theme</strong>
                                            <br>
                                            <small class="text-muted">Modern, clean, consistent design</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="theme_choice" id="theme_old" value="old">
                                        <label class="form-check-label" for="theme_old">
                                            <strong>Keep Old Theme</strong>
                                            <br>
                                            <small class="text-muted">Legacy design system</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" name="fix_conflicts" class="btn btn-primary">
                                <i class="fas fa-wrench me-2"></i>
                                Fix All Conflicts
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Files Using CSS -->
            <div class="workshop-card">
                <h2 class="mb-4">📄 Files Using CSS Themes</h2>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>CSS Files Included</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scan_results['files'] as $relativePath => $fileInfo): ?>
                                <tr>
                                    <td>
                                        <code><?php echo htmlspecialchars($relativePath); ?></code>
                                    </td>
                                    <td>
                                        <?php foreach ($fileInfo['css_files'] as $css): ?>
                                            <span class="css-file d-inline-block me-1 mb-1">
                                                <?php echo htmlspecialchars($css); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $hasConflict = false;
                                        foreach ($scan_results['conflicts'] as $conflict) {
                                            if ($conflict['file'] === $relativePath) {
                                                $hasConflict = true;
                                                break;
                                            }
                                        }
                                        ?>
                                        <?php if ($hasConflict): ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Conflict
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>OK
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Delete Old Theme -->
            <div class="workshop-card">
                <h2 class="mb-4">🗑️ Clean Up Old Theme</h2>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This will permanently delete the old theme file. A backup will be created automatically.
                </div>
                
                <p class="text-muted">If you're sure you want to remove the old theme file to clean up your project:</p>
                
                <form method="POST">
                    <button type="submit" name="delete_old_theme" class="btn btn-danger" 
                            onclick="return confirm('Are you sure you want to delete the old theme file? A backup will be created first.')">
                        <i class="fas fa-trash me-2"></i>
                        Delete Old Theme File (style.css)
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
