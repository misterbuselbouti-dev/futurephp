<?php
// FUTURE AUTOMOTIVE - Site Cleanup Tool with Protection
// أداة تنظيف شاملة للموقع مع حماية الملفات المهمة

require_once 'config.php';
require_once 'includes/functions.php';

// Check authentication
require_login();

echo "<h2>🧹 أداة تنظيف شاملة للموقع</h2>";

// Define protected files and directories
$protected_files = [
    'config.php',
    'config_achat_hostinger.php',
    'includes/',
    'assets/',
    'admin/',
    'sql/',
    'pdf/',
    'dashboard_simple.php',
    'buses_complete.php',
    'fournisseurs.php',
    'achat_da.php',
    'achat_be.php',
    'purchase_performance.php',
    'login.php',
    'logout.php',
    'index.php',
    '.htaccess',
    'composer.json',
    'README.md',
    'LICENSE'
];

$protected_patterns = [
    '*.php',
    '*.html',
    '*.css',
    '*.js',
    '*.json',
    '.env*',
    '*.sql',
    '*.md',
    '*.txt'
];

// Cleanup actions
$cleanup_actions = [
    'temp_files' => 'ملفات مؤقتة',
    'cache_files' => 'ملفات الكاش',
    'log_files' => 'ملفات السجلات القديمة',
    'backup_files' => 'ملفات النسخ الاحتياطي القديمة',
    'session_files' => 'ملفات الجلسات المنتهية',
    'error_logs' => 'سجلات الأخطاء',
    'temp_uploads' => 'الملفات المرفوعة المؤقتة'
];

// Get cleanup action from request
$action = sanitize_input($_GET['action'] ?? '');

if ($action && isset($cleanup_actions[$action])) {
    perform_cleanup($action);
} else {
    show_cleanup_options();
}

function show_cleanup_options() {
    global $cleanup_actions;
    
    echo "<h3>📋 خيارات التنظيف:</h3>";
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
    echo "<p><strong>⚠️ تحذير:</strong> هذه الأداة ستحذف الملفات المحددة فقط. جميع الملفات المهمة محمية ولن يتم حذفها.</p>";
    echo "</div>";
    
    echo "<div class='row'>";
    foreach ($cleanup_actions as $key => $label) {
        echo "<div class='col-md-6 mb-3'>";
        echo "<div class='card'>";
        echo "<div class='card-body'>";
        echo "<h5 class='card-title'>$label</h5>";
        echo "<p class='card-text'>تنظيف $key</p>";
        echo "<a href='?action=$key' class='btn btn-warning' onclick='return confirm(\"هل أنت متأكد من تنظيف $label؟\")'>";
        echo "<i class='fas fa-broom me-2'></i>تنظيف</a>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }
    echo "</div>";
    
    echo "<div class='mt-4'>";
    echo "<a href='?action=all' class='btn btn-danger' onclick='return confirm(\"هل أنت متأكد من تنظيف كل الملفات غير المهمة؟\")'>";
    echo "<i class='fas fa-trash-alt me-2'></i>تنظيف شامل</a>";
    echo "<a href='?action=dry_run' class='btn btn-info ms-2'>";
    echo "<i class='fas fa-eye me-2'></i>فحص فقط (بدون حذف)</a>";
    echo "</div>";
    
    show_site_info();
}

function perform_cleanup($action) {
    global $protected_files, $protected_patterns;
    
    echo "<h3>🧹 جاري التنظيف: " . get_action_label($action) . "</h3>";
    
    $deleted_count = 0;
    $total_size = 0;
    
    try {
        $root_dir = __DIR__;
        
        if ($action === 'all') {
            // Clean all types
            foreach (array_keys($GLOBALS['cleanup_actions']) as $clean_action) {
                $result = cleanup_type($clean_action, $root_dir, false);
                $deleted_count += $result['count'];
                $total_size += $result['size'];
            }
        } elseif ($action === 'dry_run') {
            // Dry run - show what would be deleted
            echo "<h4>🔍 فحص الملفات التي سيتم حذفها:</h4>";
            foreach (array_keys($GLOBALS['cleanup_actions']) as $clean_action) {
                $result = cleanup_type($clean_action, $root_dir, true);
                echo "<p><strong>" . get_action_label($clean_action) . ":</strong> " . $result['count'] . " ملف (" . format_bytes($result['size']) . ")</p>";
            }
            echo "<div class='alert alert-info mt-3'>";
            echo "<i class='fas fa-info-circle me-2'></i>";
            echo "هذا كان فحصاً فقط. لم يتم حذف أي ملفات.";
            echo "</div>";
        } else {
            // Clean specific type
            $result = cleanup_type($action, $root_dir, false);
            $deleted_count = $result['count'];
            $total_size = $result['size'];
        }
        
        if ($action !== 'dry_run') {
            echo "<div class='alert alert-success'>";
            echo "<i class='fas fa-check-circle me-2'></i>";
            echo "تم حذف $deleted_count ملف (حجم إجمالي: " . format_bytes($total_size) . ")";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>";
        echo "<i class='fas fa-exclamation-triangle me-2'></i>";
        echo "خطأ: " . $e->getMessage();
        echo "</div>";
    }
    
    echo "<div class='mt-3'>";
    echo "<a href='?' class='btn btn-primary'>🔙 رجوع</a>";
    echo "</div>";
}

function cleanup_type($type, $root_dir, $dry_run = false) {
    $deleted_count = 0;
    $total_size = 0;
    
    switch ($type) {
        case 'temp_files':
            $result = clean_directory($root_dir, '/^\.|~$|\.tmp$|\.temp$/', $dry_run);
            break;
        case 'cache_files':
            $result = clean_cache_files($root_dir, $dry_run);
            break;
        case 'log_files':
            $result = clean_old_files($root_dir, '/\.log$/', 7, $dry_run); // 7 days old
            break;
        case 'backup_files':
            $result = clean_old_files($root_dir, '/\.bak$|\.backup$|\.old$/', 30, $dry_run); // 30 days old
            break;
        case 'session_files':
            $result = clean_session_files($root_dir, $dry_run);
            break;
        case 'error_logs':
            $result = clean_error_logs($root_dir, $dry_run);
            break;
        case 'temp_uploads':
            $result = clean_temp_uploads($root_dir, $dry_run);
            break;
        default:
            $result = ['count' => 0, 'size' => 0];
    }
    
    return $result;
}

function clean_directory($dir, $pattern, $dry_run = false) {
    $deleted_count = 0;
    $total_size = 0;
    
    if (!is_dir($dir)) {
        return ['count' => 0, 'size' => 0];
    }
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $filepath = $dir . '/' . $file;
        
        // Skip protected files and directories
        if (is_protected($filepath)) {
            continue;
        }
        
        if (is_file($filepath) && preg_match($pattern, $file)) {
            $size = filesize($filepath);
            $total_size += $size;
            
            if (!$dry_run) {
                unlink($filepath);
                echo "<p style='color: green; font-size: 12px;'>✅ تم حذف: $file (" . format_bytes($size) . ")</p>";
            } else {
                echo "<p style='color: blue; font-size: 12px;'>🔍 سيتم حذف: $file (" . format_bytes($size) . ")</p>";
            }
            $deleted_count++;
        } elseif (is_dir($filepath) && preg_match($pattern, $file)) {
            // Recursively clean subdirectories
            $result = clean_directory($filepath, $pattern, $dry_run);
            $deleted_count += $result['count'];
            $total_size += $result['size'];
            
            // Remove empty directory
            if (!$dry_run && count(scandir($filepath)) <= 2) {
                rmdir($filepath);
                echo "<p style='color: orange; font-size: 12px;'>📁 تم حذف مجلد فارغ: $file</p>";
            }
        }
    }
    
    return ['count' => $deleted_count, 'size' => $total_size];
}

function clean_cache_files($root_dir, $dry_run = false) {
    $deleted_count = 0;
    $total_size = 0;
    
    $cache_dirs = [
        $root_dir . '/cache',
        $root_dir . '/tmp',
        $root_dir . '/temp',
        $root_dir . '/storage/cache',
        $root_dir . '/vendor/composer/cache'
    ];
    
    foreach ($cache_dirs as $cache_dir) {
        if (is_dir($cache_dir) && !is_protected($cache_dir)) {
            $result = clean_directory($cache_dir, '/.*/', $dry_run);
            $deleted_count += $result['count'];
            $total_size += $result['size'];
        }
    }
    
    return ['count' => $deleted_count, 'size' => $total_size];
}

function clean_old_files($root_dir, $pattern, $days_old, $dry_run = false) {
    $deleted_count = 0;
    $total_size = 0;
    $cutoff_time = time() - ($days_old * 24 * 60 * 60);
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root_dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && !is_protected($file->getPathname()) && preg_match($pattern, $file->getFilename())) {
            if ($file->getMTime() < $cutoff_time) {
                $size = $file->getSize();
                $total_size += $size;
                
                if (!$dry_run) {
                    unlink($file->getPathname());
                    echo "<p style='color: green; font-size: 12px;'>✅ تم حذف ملف قديم: " . $file->getFilename() . " (" . format_bytes($size) . ")</p>";
                } else {
                    echo "<p style='color: blue; font-size: 12px;'>🔍 سيتم حذف ملف قديم: " . $file->getFilename() . " (" . format_bytes($size) . ")</p>";
                }
                $deleted_count++;
            }
        }
    }
    
    return ['count' => $deleted_count, 'size' => $total_size];
}

function clean_session_files($root_dir, $dry_run = false) {
    $deleted_count = 0;
    $total_size = 0;
    
    $session_dir = session_save_path();
    if (!$session_dir) {
        $session_dir = $root_dir . '/sessions';
    }
    
    if (is_dir($session_dir) && !is_protected($session_dir)) {
        $files = scandir($session_dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $filepath = $session_dir . '/' . $file;
            if (is_file($filepath) && !is_protected($filepath)) {
                // Check if session is expired (older than 24 hours)
                if (filemtime($filepath) < time() - 86400) {
                    $size = filesize($filepath);
                    $total_size += $size;
                    
                    if (!$dry_run) {
                        unlink($filepath);
                        echo "<p style='color: green; font-size: 12px;'>✅ تم حذف جلسة منتهية: $file (" . format_bytes($size) . ")</p>";
                    } else {
                        echo "<p style='color: blue; font-size: 12px;'>🔍 سيتم حذف جلسة منتهية: $file (" . format_bytes($size) . ")</p>";
                    }
                    $deleted_count++;
                }
            }
        }
    }
    
    return ['count' => $deleted_count, 'size' => $total_size];
}

function clean_error_logs($root_dir, $dry_run = false) {
    $deleted_count = 0;
    $total_size = 0;
    
    $log_files = [
        $root_dir . '/error.log',
        $root_dir . '/debug.log',
        $root_dir . '/php_errors.log',
        $root_dir . '/access.log'
    ];
    
    foreach ($log_files as $log_file) {
        if (file_exists($log_file) && !is_protected($log_file)) {
            $size = filesize($log_file);
            $total_size += $size;
            
            if (!$dry_run) {
                // Truncate instead of delete to maintain file structure
                file_put_contents($log_file, '');
                echo "<p style='color: orange; font-size: 12px;'>📝 تم تفريغ سجل الأخطاء: " . basename($log_file) . " (" . format_bytes($size) . ")</p>";
            } else {
                echo "<p style='color: blue; font-size: 12px;'>🔍 سيتم تفريغ سجل الأخطاء: " . basename($log_file) . " (" . format_bytes($size) . ")</p>";
            }
            $deleted_count++;
        }
    }
    
    return ['count' => $deleted_count, 'size' => $total_size];
}

function clean_temp_uploads($root_dir, $dry_run = false) {
    $deleted_count = 0;
    $total_size = 0;
    
    $upload_dirs = [
        $root_dir . '/uploads/temp',
        $root_dir . '/temp/uploads',
        $root_dir . '/tmp/uploads'
    ];
    
    foreach ($upload_dirs as $upload_dir) {
        if (is_dir($upload_dir) && !is_protected($upload_dir)) {
            $result = clean_old_files($upload_dir, '/.*/', 1, $dry_run); // 1 day old
            $deleted_count += $result['count'];
            $total_size += $result['size'];
        }
    }
    
    return ['count' => $deleted_count, 'size' => $total_size];
}

function is_protected($filepath) {
    global $protected_files, $protected_patterns;
    
    $relative_path = str_replace(__DIR__ . '/', '', $filepath);
    
    // Check if file/directory is in protected list
    foreach ($protected_files as $protected) {
        if (strpos($relative_path, $protected) === 0 || $relative_path === $protected) {
            return true;
        }
    }
    
    // Check if file matches protected patterns
    foreach ($protected_patterns as $pattern) {
        if (fnmatch($pattern, basename($filepath))) {
            return true;
        }
    }
    
    return false;
}

function get_action_label($action) {
    global $cleanup_actions;
    return $cleanup_actions[$action] ?? $action;
}

function format_bytes($bytes) {
    if ($bytes === 0) return '0 B';
    
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

function show_site_info() {
    echo "<h3>📊 معلومات الموقع:</h3>";
    
    $root_dir = __DIR__;
    $total_size = 0;
    $file_count = 0;
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root_dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $total_size += $file->getSize();
            $file_count++;
        }
    }
    
    echo "<div class='row'>";
    echo "<div class='col-md-4'>";
    echo "<div class='card'>";
    echo "<div class='card-body text-center'>";
    echo "<h4>" . number_format($file_count) . "</h4>";
    echo "<p class='text-muted'>إجمالي الملفات</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "<div class='col-md-4'>";
    echo "<div class='card'>";
    echo "<div class='card-body text-center'>";
    echo "<h4>" . format_bytes($total_size) . "</h4>";
    echo "<p class='text-muted'>حجم الموقع</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "<div class='col-md-4'>";
    echo "<div class='card'>";
    echo "<div class='card-body text-center'>";
    echo "<h4>" . count($GLOBALS['protected_files']) . "</h4>";
    echo "<p class='text-muted'>ملفات محمية</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='alert alert-info mt-3'>";
    echo "<h5>📋 الملفات المحمية:</h5>";
    echo "<ul>";
    foreach ($GLOBALS['protected_files'] as $protected) {
        echo "<li><code>$protected</code></li>";
    }
    echo "</ul>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أداة تنظيف الموقع</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/simple-theme.css">
</head>
<body>
    <?php include 'includes/header_simple.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="mb-4">
                <h1><i class="fas fa-broom me-2"></i>أداة تنظيف الموقع</h1>
                <p class="text-muted">تنظيف الملفات غير المهمة مع حماية الملفات الهامة</p>
            </div>
            
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>تنبيه هام:</h5>
                <ul>
                    <li>جميع الملفات المهمة محمية ولن يتم حذفها</li>
                    <li>يمكنك استخدام "فحص فقط" لمعرفة ما سيتم حذفه</li>
                    <li>يفضل أخذ نسخة احتياطية قبل التنظيف الشامل</li>
                    <li>الملفات المحمية تشمل: ملفات PHP, CSS, JS, الإعدادات, قاعدة البيانات</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
