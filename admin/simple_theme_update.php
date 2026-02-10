<?php
// FUTURE AUTOMOTIVE - Simple Theme Update Tool
// أداة تطبيق تيم dashboard_simple.php على جميع الصفحات

require_once 'config.php';
require_once 'includes/functions.php';

// Check authentication
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

$user = get_logged_in_user();
$role = $user['role'] ?? '';

// Only admin can access this tool
if ($role !== 'admin') {
    http_response_code(403);
    echo 'Accès refusé.';
    exit();
}

$page_title = 'Simple Theme Update Tool';
$database = new Database();
$pdo = $database->connect();

// Get all PHP files in admin directory
$admin_files = [];
$admin_dir = __DIR__;
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($admin_dir));

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $admin_files[] = [
            'path' => $file->getPathname(),
            'relative_path' => str_replace($admin_dir . '/', '', $file->getPathname()),
            'filename' => $file->getFilename(),
            'size' => $file->getSize(),
            'modified' => $file->getMTime()
        ];
    }
}

// Sort files by filename
usort($admin_files, function($a, $b) {
    return strcmp($a['filename'], $b['filename']);
});

// Process update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_theme'])) {
    $target_file = $_POST['target_file'] ?? '';
    
    if (empty($target_file) || !file_exists($target_file)) {
        echo json_encode(['success' => false, 'error' => 'File not found']);
        exit;
    }
    
    $result = updateFileTheme($target_file);
    echo json_encode($result);
    exit;
}

// Get file details for preview
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['preview'])) {
    $target_file = $_GET['preview'] ?? '';
    
    if (empty($target_file) || !file_exists($target_file)) {
        echo json_encode(['success' => false, 'error' => 'File not found']);
        exit;
    }
    
    $result = getFilePreview($target_file);
    echo json_encode($result);
    exit;
}

function updateFileTheme($file_path) {
    try {
        $content = file_get_contents($file_path);
        $original_content = $content;
        
        // Check if file already has simple theme
        if (strpos($content, 'simple-theme.css') !== false) {
            return ['success' => false, 'error' => 'File already has simple theme'];
        }
        
        // Apply theme updates
        $content = applyThemeUpdates($content);
        
        // Write updated content
        if (file_put_contents($file_path, $content)) {
            return [
                'success' => true, 
                'message' => 'Theme applied successfully',
                'changes' => countChanges($original_content, $content)
            ];
        } else {
            return ['success' => false, 'error' => 'Failed to write file'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function getFilePreview($file_path) {
    try {
        $content = file_get_contents($content = file_get_contents($file_path);
        $lines = explode("\n", $content);
        
        return [
            'success' => true,
            'preview' => array_slice($lines, 0, 50), // First 50 lines
            'total_lines' => count($lines),
            'has_theme' => strpos($content, 'simple-theme.css') !== false
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function applyThemeUpdates($content) {
    // 1. Replace CSS includes
    $content = preg_replace(
        '/<link href="[^"]*style\.css"[^>]*>/',
        '<link rel="stylesheet" href="../assets/css/simple-theme.css">',
        $content
    );
    
    // 2. Replace header include
    $content = preg_replace(
        '/<\?php include.*header\.php.*\?>/',
        '<?php include "../includes/header_simple.php"; ?>',
        $content
    );
    
    // 3. Add navigation buttons if not present
    if (strpos($content, 'btn-outline-primary') === false && strpos($content, 'btn-outline-success') === false) {
        // Find main content div and add navigation
        $pattern = '/<div class="main-content">(.*?)<\/div>/s';
        if (preg_match($pattern, $content, $matches)) {
            $main_content = $matches[1];
            
            // Add navigation after page header
            $nav_buttons = '
            <div class="d-flex gap-3">
                <button class="btn btn-outline-primary" onclick="window.location.href=\'../quick_audit.php\'">
                    <i class="fas fa-clipboard-check me-2"></i>Audit
                </button>
                <button class="btn btn-outline-success" onclick="window.location.href=\'../remove_unnecessary_files.php\'">
                    <i class="fas fa-trash-alt me-2"></i>Nettoyer
                </button>
                <button class="btn btn-primary" onclick="window.location.href=\'../dashboard_simple.php\'">
                    <i class="fas fa-home me-2"></i>Dashboard
                </button>
            </div>';
            
            $content = preg_replace(
                $pattern,
                '<div class="main-content">' . $nav_buttons . $main_content . '</div>',
                $content
            );
        }
    }
    
    // 4. Replace card with workshop-card
    $content = preg_replace(
        '/<div class="card([^>]*)>/',
        '<div class="workshop-card$1">',
        $content
    );
    
    // 5. Replace card-header with simple h6
    $content = preg_replace(
        '/<div class="card-header([^>]*)>(.*?)<\/div>/',
        '<h6 class="mb-3"$2>$3</h6>',
        $content
    );
    
    // 6. Replace card-body with simple content
    $content = preg_replace(
        '/<div class="card-body([^>]*)>(.*?)<\/div>/',
        '$2',
        $content
    );
    
    // 7. Replace table-sm with table-hover
    $content = preg_replace(
        '/<table class="table-sm"/',
        '<table class="table-hover"',
        $content
    );
    
    // 8. Replace stat-card styling if present
    $content = preg_replace(
        '/class="stat-card"/',
        'class="stat-card"',
        $content
    );
    
    // 9. Replace form styling
    $content = preg_replace(
        '/style="[^"]*border-radius:[^"]*;[^"]*"/',
        'style="border-radius: var(--radius);"',
        $content
    );
    
    return $content;
}

function countChanges($original, $updated) {
    $original_lines = explode("\n", $original);
    $updated_lines = explode("\n", $updated);
    
    $changes = 0;
    $min_lines = min(count($original_lines), count($updated_lines));
    
    for ($i = 0; $i < $min_lines; $i++) {
        if ($original_lines[$i] !== $updated_lines[$i]) {
            $changes++;
        }
    }
    
    return $changes;
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
    <link rel="stylesheet" href="../assets/css/simple-theme.css">
    
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
        
        .file-item {
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: var(--space-4);
            margin-bottom: var(--space-4);
            transition: all 0.2s ease;
            background-color: var(--bg-white);
        }
        
        .file-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .file-item.has-theme {
            border-left: 4px solid var(--success);
        }
        
        .file-item.needs-theme {
            border-left: 4px solid var(--warning);
        }
        
        .btn-update {
            background-color: var(--primary);
            border: none;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius);
            font-size: 0.875rem;
        }
        
        .btn-update:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-preview {
            background-color: var(--info);
            border: none;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius);
            font-size: 0.875rem;
        }
        
        .btn-preview:hover {
            background-color: var(--info-dark);
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
    <?php include '../includes/header_simple.php'; ?>
    
    <!-- Include sidebar -->
    <?php include '../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-6">
                <div>
                    <h1 class="mb-2">
                        <i class="fas fa-paint-brush me-3"></i>
                        Simple Theme Update Tool
                    </h1>
                    <p class="text-muted mb-0">Bienvenue, <?php echo htmlspecialchars($user['full_name']); ?></p>
                </div>
                <div class="d-flex gap-3">
                    <button class="btn btn-outline-primary" onclick="window.location.href='../quick_audit.php'">
                        <i class="fas fa-clipboard-check me-2"></i>Audit
                    </button>
                    <button class="btn btn-outline-success" onclick="window.location.href='../remove_unnecessary_files.php'">
                        <i class="fas fa-trash-alt me-2"></i>Nettoyer
                    </button>
                    <button class="btn btn-primary" onclick="window.location.href='../dashboard_simple.php'">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </button>
                </div>
            </div>

            <div class="workshop-card">
                <h2 class="mb-4">📋 قائمة ملفات Admin</h2>
                <p class="text-muted mb-4">اختر الملفات التي تريد تطبيق التيم عليها:</p>
                
                <div class="mb-4">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleAll()">
                        <label class="form-check-label" for="selectAll">
                            تحديد الكل
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="selectNeedsTheme" onchange="toggleNeedsTheme()">
                        <label class="form-check-label" for="selectNeedsTheme">
                            تحديد الملفات التي تحتاج للتحديث فقط
                        </label>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th><input type="checkbox" id="selectAllHeader" onchange="toggleAll()"></th>
                                <th>الملف</th>
                                <th>الحجم</th>
                                <th>آخر تعديل</th>
                                <th>الحالة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admin_files as $file): ?>
                                <tr class="file-item <?php echo hasSimpleTheme($file['path']) ? 'has-theme' : 'needs-theme'; ?>">
                                    <td>
                                        <input type="checkbox" class="form-check-input file-checkbox" 
                                               value="<?php echo htmlspecialchars($file['relative_path']); ?>"
                                               data-filename="<?php echo htmlspecialchars($file['filename']); ?>"
                                               <?php echo hasSimpleTheme($file['path']) ? 'disabled' : ''; ?>>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($file['relative_path']); ?></code>
                                    </td>
                                    <td><?php echo formatBytes($file['size']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', $file['modified']); ?></td>
                                    <td>
                                        <?php if (hasSimpleTheme($file['path'])): ?>
                                            <span class="badge bg-success">✅ تم التطبيق</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">⚠️ يحتاج تحديث</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-preview btn-sm me-1" 
                                                onclick="previewFile('<?php echo htmlspecialchars($file['path']); ?>')"
                                                <?php echo hasSimpleTheme($file['path']) ? 'disabled' : ''; ?>>
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-update btn-sm" 
                                                onclick="updateFile('<?php echo htmlspecialchars($file['path'); ?>')"
                                                <?php echo hasSimpleTheme($file['path']) ? 'disabled' : ''; ?>>
                                            <i class="fas fa-sync"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4">
                    <button class="btn btn-primary btn-lg" onclick="updateSelected()">
                        <i class="fas fa-paint-brush me-2"></i>
                        تطبيق التيم على الملفات المحددة
                    </button>
                    <button class="btn btn-secondary btn-lg ms-2" onclick="window.location.reload()">
                        <i class="fas fa-redo me-2"></i>
                        تحديث القائمة
                    </button>
                </div>
            </div>
            
            <!-- Progress Modal -->
            <div class="modal fade" id="progressModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">تقدم التحديث</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="progress mb-3">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" 
                                     style="width: 0%" 
                                     id="progressBar">
                                </div>
                            </div>
                            <div id="progressText">جاري التحديث...</div>
                            <div id="progressDetails"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedFiles = [];
        let totalFiles = 0;
        let processedFiles = 0;
        
        function toggleAll() {
            const checkboxes = document.querySelectorAll('.file-checkbox');
            const selectAll = document.getElementById('selectAll').checked;
            const selectAllHeader = document.getElementById('selectAllHeader').checked;
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll || selectAllHeader;
                checkbox.disabled = selectAll && hasSimpleTheme(checkbox.value);
            });
            
            updateSelectedCount();
        }
        
        function toggleNeedsTheme() {
            const checkboxes = document.querySelectorAll('.file-checkbox');
            const selectNeedsTheme = document.getElementById('selectNeedsTheme').checked;
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectNeedsTheme && !hasSimpleTheme(checkbox.value);
                checkbox.disabled = !selectNeedsTheme || hasSimpleTheme(checkbox.value);
            });
            
            updateSelectedCount();
        }
        
        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.file-checkbox:checked:not(:disabled)');
            selectedFiles = Array.from(checkboxes).map(cb => cb.value);
            document.getElementById('selectAll').checked = checkboxes.length > 0 && checkboxes.length === document.querySelectorAll('.file-checkbox:not(:disabled)').length;
            document.getElementById('selectAllHeader').checked = document.getElementById('selectAll').checked;
        }
        
        function hasSimpleTheme(file_path) {
            // Simple check - in real implementation, this would be more thorough
            return file_exists($file_path) && strpos(file_get_contents($file_path), 'simple-theme.css') !== false;
        }
        
        function previewFile(file_path) {
            fetch('?preview=' + encodeURIComponent(file_path))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Preview:\n\n' + data.preview.slice(0, 20).join('\n') + '\n\nTotal lines: ' + data.total_lines);
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Error: ' + error);
                });
        }
        
        function updateFile(file_path) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'target_file=' + encodeURIComponent(file_path) + '&update_theme=1'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI
                        const checkbox = document.querySelector(`input[value="${file_path}"]`);
                        if (checkbox) {
                            checkbox.disabled = true;
                            checkbox.closest('tr').classList.add('has-theme');
                            checkbox.closest('td').querySelector('.badge').className = 'badge bg-success';
                            checkbox.closest('td').querySelector('.badge').textContent = '✅ تم التطبيق';
                        }
                        
                        // Update progress
                        processedFiles++;
                        updateProgress();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Error: ' + error);
                });
        }
        
        function updateSelected() {
            if (selectedFiles.length === 0) {
                alert('الرجاء اختيار ملف واحد على الأقل');
                return;
            }
            
            // Show progress modal
            const modal = new bootstrap.Modal(document.getElementById('progressModal'));
            modal.show();
            
            // Reset progress
            totalFiles = selectedFiles.length;
            processedFiles = 0;
            updateProgress();
            
            // Process files one by one
            processFiles();
        }
        
        function processFiles() {
            if (processedFiles < totalFiles) {
                const file_path = selectedFiles[processedFiles];
                
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'target_file=' + encodeURIComponent(file_path) + '&update_theme=1'
                })
                .then(response => response.json())
                .then(data => {
                    processedFiles++;
                    updateProgress();
                    
                    if (processedFiles < totalFiles) {
                        setTimeout(processFiles, 500); // Small delay between requests
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    processedFiles++;
                    updateProgress();
                });
            } else {
                // All files processed
                const modal = bootstrap.Modal.getInstance(document.getElementById('progressModal'));
                modal.hide();
                
                alert('تم تحديث ' + processedFiles + ' ملف بنجاح!');
                
                // Reload page after 2 seconds
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        }
        
        function updateProgress() {
            const percentage = totalFiles > 0 ? (processedFiles / totalFiles) * 100 : 0;
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            const progressDetails = document.getElementById('progressDetails');
            
            progressBar.style.width = percentage + '%';
            progressText.textContent = `جاري التحديث... (${processedFiles}/${totalFiles})`;
            progressDetails.textContent = `تم تحديث ${processedFiles} من ${totalFiles} ملف`;
        }
        
        function formatBytes($bytes) {
            $units = ['B', 'KB', 'MB', 'GB'];
            $bytes = max($bytes, 0);
            $factor = floor((count($bytes) / 1024));
            
            for ($i = 0; $i < count($units); $i++) {
                if ($bytes < 1024) {
                    break;
                }
                $bytes /= 1024;
            }
            
            return round($bytes, 2) . ' ' . $units[$factor];
        }
    </script>
</body>
</html>
