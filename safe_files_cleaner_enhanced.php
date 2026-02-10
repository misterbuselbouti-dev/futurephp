<?php
// FUTURE AUTOMOTIVE - Enhanced Safe Files Cleaner with Recovery
// منظف آمن للملفات الزائدة مع نظام استعادة

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

$page_title = 'Enhanced Safe Files Cleaner';

// Define safe files that should NEVER be deleted
$protected_files = [
    // Core system files
    'config.php',
    'config_achat_hostinger.php',
    'database.php',
    'index.php',
    'login.php',
    'logout.php',
    'dashboard.php',
    'dashboard_simple.php',
    'dashboard_iso.php',
    
    // Essential includes
    'includes/header.php',
    'includes/header_simple.php',
    'includes/sidebar.php',
    'includes/footer.php',
    'includes/achat_tabs.php',
    
    // Core achat files
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
    'achat_be_view.php',
    
    // Essential achat helpers
    'achat_da_delete.php',
    'achat_da_validate.php',
    'achat_dp_response.php',
    'achat_bc_pdf.php',
    'achat_be_pdf.php',
    'achat_bc_get_dp_items.php',
    'achat_be_get_bc_items.php',
    'achat_be_auto_validate.php',
    
    // Database and setup files
    'create_fournisseurs_table.php',
    'check_database_data.php',
    'database_setup.php',
    'simple_theme_update.php',
    'update_achat_theme.php',
    'test_bc_search.php',
    'iso_theme_migrator.php',
    'theme_conflict_resolver.php',
    'ultimate_theme_cleanup.php',
    'safe_files_cleaner.php',
    
    // Essential directories and their contents
    'assets/',
    'includes/',
    'admin/',
    'pdfs/',
    'uploads/',
    'sql/',
    'purchase/',
    'api/',
    'driver/',
    'management/',
    'reports/',
    'pdf/',
    'config/',
    'logs/',
    'technician/',
    '.windsurf/'
];

// Define file patterns that are safe to delete
$safe_delete_patterns = [
    // Backup files
    '*.bak',
    '*.backup',
    '*.old',
    '*.orig',
    
    // Temporary files
    '*.tmp',
    '*.temp',
    '*~',
    
    // Log files
    '*.log',
    
    // Cache files
    '*.cache',
    
    // Duplicate files (numbered copies)
    '* (1).php',
    '* (2).php',
    '* (3).php',
    '* - Copy.php',
    '* - Copie.php',
    
    // Test files
    'test_*.php',
    'demo_*.php',
    'sample_*.php',
    'debug_*.php',
    
    // Editor files
    '.DS_Store',
    'Thumbs.db',
    '*.swp',
    '*.swo'
];

// Function to check if file is protected
function isProtectedFile($filepath, $protected_files) {
    $filename = basename($filepath);
    $relativePath = str_replace(__DIR__ . '/', '', $filepath);
    
    foreach ($protected_files as $protected) {
        // Check exact match
        if ($filename === $protected || $relativePath === $protected) {
            return true;
        }
        
        // Check directory protection
        if (str_ends_with($protected, '/') && str_starts_with($relativePath, $protected)) {
            return true;
        }
    }
    
    return false;
}

// Function to check if file matches safe delete pattern
function matchesSafePattern($filename, $patterns) {
    foreach ($patterns as $pattern) {
        // Convert glob pattern to regex
        $regex = '/^' . str_replace(['*', '?'], ['.*', '.'], preg_quote($pattern, '/')) . '$/';
        if (preg_match($regex, $filename)) {
            return true;
        }
    }
    return false;
}

// Function to find backup files
function findBackupFiles() {
    $backup_files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(__DIR__, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $filename = $file->getFilename();
            $filepath = $file->getPathname();
            $relativePath = str_replace(__DIR__ . '/', '', $filepath);
            
            // Check if it's a backup file
            if (preg_match('/\.(backup|bak|old)\./', $filename) || 
                preg_match('/\.\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}$/', $filename)) {
                $backup_files[] = [
                    'path' => $filepath,
                    'relative_path' => $relativePath,
                    'filename' => $filename,
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime(),
                    'original_file' => preg_replace('/\.(backup|bak|old)\..*$/', '', $filename)
                ];
            }
        }
    }
    
    // Sort by modification time (newest first)
    usort($backup_files, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
    
    return $backup_files;
}

// Function to restore from backup
function restoreFromBackup($backup_path) {
    if (!file_exists($backup_path)) {
        return ['success' => false, 'message' => 'Backup file not found'];
    }
    
    // Extract original file path
    $original_path = preg_replace('/\.(backup|bak|old)\..*$/', '', $backup_path);
    $original_path = preg_replace('/\.\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}$/', '', $original_path);
    
    // Check if original file exists
    if (file_exists($original_path)) {
        // Create backup of current file before restoring
        $current_backup = $original_path . '.before_restore.' . date('Y-m-d-H-i-s');
        copy($original_path, $current_backup);
    }
    
    // Restore from backup
    if (copy($backup_path, $original_path)) {
        return [
            'success' => true, 
            'message' => 'File restored successfully',
            'original_path' => $original_path,
            'backup_created' => isset($current_backup) ? $current_backup : null
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to restore file'];
    }
}

// Scan directory for files
function scanDirectory($dir, $protected_files, $safe_patterns) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $filepath = $file->getPathname();
            $filename = $file->getFilename();
            $relativePath = str_replace(__DIR__ . '/', '', $filepath);
            
            $fileInfo = [
                'path' => $filepath,
                'relative_path' => $relativePath,
                'filename' => $filename,
                'size' => $file->getSize(),
                'modified' => $file->getMTime(),
                'protected' => isProtectedFile($filepath, $protected_files),
                'safe_to_delete' => matchesSafePattern($filename, $safe_patterns) && !isProtectedFile($filepath, $protected_files),
                'reason' => ''
            ];
            
            // Add reason for protection or safety
            if ($fileInfo['protected']) {
                $fileInfo['reason'] = 'Protected system file';
            } elseif ($fileInfo['safe_to_delete']) {
                $fileInfo['reason'] = 'Safe to delete (matches pattern)';
            } else {
                $fileInfo['reason'] = 'Unknown - manual review needed';
            }
            
            $files[] = $fileInfo;
        }
    }
    
    return $files;
}

// Get all files
$all_files = [];
$deletable_files = [];
$protected_files_count = 0;
$unknown_files_count = 0;
$backup_files = [];
$restore_results = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $all_files = scanDirectory(__DIR__, $protected_files, $safe_delete_patterns);
    $backup_files = findBackupFiles();
    
    foreach ($all_files as $file) {
        if ($file['protected']) {
            $protected_files_count++;
        } elseif ($file['safe_to_delete']) {
            $deletable_files[] = $file;
        } else {
            $unknown_files_count++;
        }
    }
}

// Handle deletion
$deleted_files = [];
$delete_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_files'])) {
        $files_to_delete = $_POST['files_to_delete'] ?? [];
        
        foreach ($files_to_delete as $filepath) {
            // Double check protection before deletion
            if (!isProtectedFile($filepath, $protected_files)) {
                try {
                    if (unlink($filepath)) {
                        $deleted_files[] = basename($filepath);
                    } else {
                        $delete_errors[] = "Failed to delete: " . basename($filepath);
                    }
                } catch (Exception $e) {
                    $delete_errors[] = "Error deleting " . basename($filepath) . ": " . $e->getMessage();
                }
            } else {
                $delete_errors[] = "Refused to delete protected file: " . basename($filepath);
            }
        }
        
        // Refresh file list after deletion
        $all_files = scanDirectory(__DIR__, $protected_files, $safe_delete_patterns);
        $deletable_files = [];
        $protected_files_count = 0;
        $unknown_files_count = 0;
        $backup_files = findBackupFiles();
        
        foreach ($all_files as $file) {
            if ($file['protected']) {
                $protected_files_count++;
            } elseif ($file['safe_to_delete']) {
                $deletable_files[] = $file;
            } else {
                $unknown_files_count++;
            }
        }
    }
    
    if (isset($_POST['restore_backup'])) {
        $backup_path = $_POST['backup_path'] ?? '';
        if (!empty($backup_path)) {
            $result = restoreFromBackup($backup_path);
            $result['backup_file'] = basename($backup_path);
            $restore_results[] = $result;
            
            // Refresh lists after restore
            $all_files = scanDirectory(__DIR__, $protected_files, $safe_delete_patterns);
            $backup_files = findBackupFiles();
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
        
        .file-protected {
            background: #fee2e2;
            border-left: 4px solid #dc2626;
        }
        
        .file-safe {
            background: #f0fdf4;
            border-left: 4px solid #16a34a;
        }
        
        .file-unknown {
            background: #fef3c7;
            border-left: 4px solid #d97706;
        }
        
        .file-backup {
            background: #eff6ff;
            border-left: 4px solid #2563eb;
        }
        
        .file-size {
            font-size: 0.85rem;
            color: #6b7280;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .restore-success {
            background: #f0fdf4;
            border: 1px solid #16a34a;
            border-radius: var(--radius);
            padding: var(--space-4);
            margin-bottom: var(--space-3);
        }
        
        .restore-error {
            background: #fee2e2;
            border: 1px solid #dc2626;
            border-radius: var(--radius);
            padding: var(--space-4);
            margin-bottom: var(--space-3);
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
                <div class="d-flex justify-content-between align-items-center mb-6">
                    <div>
                        <h1 class="mb-2">
                            <i class="fas fa-broom me-3"></i>
                            Enhanced Safe Files Cleaner
                        </h1>
                        <p class="text-muted mb-0">Safely remove unnecessary files with recovery system</p>
                    </div>
                    <div class="d-flex gap-3">
                        <button class="iso-btn-secondary" onclick="window.location.href='dashboard_iso.php'">
                            <i class="fas fa-home me-2"></i>Dashboard
                        </button>
                    </div>
                </div>
            </div>

            <!-- Restore Results -->
            <?php if (!empty($restore_results)): ?>
                <div class="iso-card">
                    <h2 class="mb-4">🔄 Restore Results</h2>
                    
                    <?php foreach ($restore_results as $result): ?>
                        <div class="<?php echo $result['success'] ? 'restore-success' : 'restore-error'; ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($result['backup_file']); ?></strong>
                                    <?php if ($result['success']): ?>
                                        <br>
                                        <small class="text-muted">
                                            Restored to: <?php echo htmlspecialchars($result['original_path']); ?>
                                            <?php if (isset($result['backup_created'])): ?>
                                                | Current file backed up to: <?php echo htmlspecialchars(basename($result['backup_created'])); ?>
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php if ($result['success']): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Restored
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times me-1"></i>Failed
                                        </span>
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

            <!-- Success Messages -->
            <?php if (!empty($deleted_files)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    Successfully deleted <?php echo count($deleted_files); ?> files:
                    <ul class="mb-0 mt-2">
                        <?php foreach ($deleted_files as $file): ?>
                            <li><?php echo htmlspecialchars($file); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Error Messages -->
            <?php if (!empty($delete_errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo count($delete_errors); ?> errors occurred:
                    <ul class="mb-0 mt-2">
                        <?php foreach ($delete_errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <h3><i class="fas fa-shield-alt me-2"></i><?php echo count($all_files); ?></h3>
                        <p class="mb-0">Total Files</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card" style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);">
                        <h3><i class="fas fa-lock me-2"></i><?php echo $protected_files_count; ?></h3>
                        <p class="mb-0">Protected Files</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card" style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);">
                        <h3><i class="fas fa-trash me-2"></i><?php echo count($deletable_files); ?></h3>
                        <p class="mb-0">Safe to Delete</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                        <h3><i class="fas fa-history me-2"></i><?php echo count($backup_files); ?></h3>
                        <p class="mb-0">Backup Files</p>
                    </div>
                </div>
            </div>

            <!-- Backup Files Recovery -->
            <?php if (!empty($backup_files)): ?>
                <div class="iso-card">
                    <h2 class="mb-4">💾 Backup Files Recovery</h2>
                    <p class="text-muted mb-3">Found <?php echo count($backup_files); ?> backup files that can be restored:</p>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Backup File</th>
                                    <th>Original File</th>
                                    <th>Size</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backup_files as $backup): ?>
                                    <tr>
                                        <td>
                                            <code><?php echo htmlspecialchars($backup['filename']); ?></code>
                                        </td>
                                        <td>
                                            <code><?php echo htmlspecialchars($backup['original_file']); ?></code>
                                            <?php if (!file_exists(__DIR__ . '/' . dirname($backup['relative_path']) . '/' . $backup['original_file'])): ?>
                                                <span class="badge bg-warning ms-2">Missing</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="file-size"><?php echo number_format($backup['size'] / 1024, 2); ?> KB</span>
                                        </td>
                                        <td>
                                            <small><?php echo date('Y-m-d H:i:s', $backup['modified']); ?></small>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="backup_path" value="<?php echo htmlspecialchars($backup['path']); ?>">
                                                <button type="submit" name="restore_backup" class="btn btn-sm btn-primary"
                                                        onclick="return confirm('Restore <?php echo htmlspecialchars($backup['original_file']); ?> from this backup?')">
                                                    <i class="fas fa-undo me-1"></i>Restore
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Safe to Delete Files -->
            <?php if (!empty($deletable_files)): ?>
                <div class="iso-card">
                    <h2 class="mb-4">🗑️ Safe to Delete Files</h2>
                    <p class="text-muted mb-3">These files match safe deletion patterns and can be safely removed:</p>
                    
                    <form method="POST">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes(this.checked)">
                                        </th>
                                        <th>File</th>
                                        <th>Size</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deletable_files as $file): ?>
                                        <tr class="file-safe">
                                            <td>
                                                <input class="form-check-input" type="checkbox" name="files_to_delete[]" value="<?php echo htmlspecialchars($file['path']); ?>" id="file_<?php echo md5($file['path']); ?>" checked>
                                            </td>
                                            <td>
                                                <label for="file_<?php echo md5($file['path']); ?>" class="d-block">
                                                    <code><?php echo htmlspecialchars($file['relative_path']); ?></code>
                                                </label>
                                            </td>
                                            <td>
                                                <span class="file-size"><?php echo number_format($file['size'] / 1024, 2); ?> KB</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success"><?php echo htmlspecialchars($file['reason']); ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" name="delete_files" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete the selected files? This action cannot be undone.')">
                                <i class="fas fa-trash me-2"></i>Delete Selected Files (<?php echo count($deletable_files); ?>)
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleAllCheckboxes(true)">Select All</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleAllCheckboxes(false)">Deselect All</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Protected Files Info -->
            <div class="iso-card">
                <h2 class="mb-4">🛡️ Protection Information</h2>
                <p class="text-muted">The following files and directories are <strong>NEVER</strong> deleted for safety:</p>
                
                <div class="row">
                    <div class="col-md-6">
                        <h5>Core System Files:</h5>
                        <ul class="small">
                            <li>config.php, database.php</li>
                            <li>index.php, login.php, dashboard.php</li>
                            <li>All achat_*.php files</li>
                            <li>Database setup files</li>
                            <li>Theme management files</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5>Protected Directories:</h5>
                        <ul class="small">
                            <li>assets/ - CSS, JS, images</li>
                            <li>includes/ - Header, sidebar, footer</li>
                            <li>admin/ - Admin panel files</li>
                            <li>pdfs/, uploads/, sql/ - Data files</li>
                            <li>api/, driver/, management/ - Functional areas</li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-3">
                    <h5>Safe Deletion Patterns:</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="small">
                                <li>*.bak, *.backup, *.old</li>
                                <li>*.tmp, *.temp, *~</li>
                                <li>*.log, *.cache</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="small">
                                <li>* (1).php, * - Copy.php</li>
                                <li>test_*.php, demo_*.php</li>
                                <li>.DS_Store, Thumbs.db</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function toggleAllCheckboxes(checked) {
            const checkboxes = document.querySelectorAll('input[name="files_to_delete[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = checked;
            });
        }
        
        // Update count when checkboxes change
        document.addEventListener('change', function(e) {
            if (e.target.name === 'files_to_delete[]') {
                const checkedCount = document.querySelectorAll('input[name="files_to_delete[]"]:checked').length;
                const deleteButton = document.querySelector('button[name="delete_files"]');
                if (deleteButton) {
                    deleteButton.innerHTML = `<i class="fas fa-trash me-2"></i>Delete Selected Files (${checkedCount})`;
                }
            }
        });
    </script>
</body>
</html>
