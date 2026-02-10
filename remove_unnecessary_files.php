<?php
// FUTURE AUTOMOTIVE - Remove Unnecessary Files
// حذف الملفات الزائدة غير الضرورية

require_once 'config.php';
require_once 'includes/functions.php';

// Check authentication
require_login();

echo "<h2>🗑️ حذف الملفات الزائدة غير الضرورية</h2>";

// Define essential files that should NEVER be deleted
$essential_files = [
    // Core configuration
    'config.php',
    'config_achat_hostinger.php',
    
    // Essential directories
    'includes/',
    'assets/',
    'admin/',
    'sql/',
    'pdf/',
    
    // Core application files
    'index.php',
    'login.php',
    'logout.php',
    'dashboard_simple.php',
    'buses_complete.php',
    'fournisseurs.php',
    'achat_da.php',
    'achat_be.php',
    'purchase_performance.php',
    'site_cleanup.php',
    'fix_ref_ot.php',
    'setup_supplier_tables.php',
    
    // Essential assets
    '.htaccess',
    'composer.json',
    'README.md',
    'LICENSE'
];

// Define file patterns to consider for deletion
$unnecessary_patterns = [
    '*.tmp',
    '*.temp',
    '*~',
    '*.bak',
    '*.backup',
    '*.old',
    '*.log',
    '*.swp',
    '*.swo',
    '.DS_Store',
    'Thumbs.db',
    '*.orig',
    '*.rej',
    '.#*',
    '#*#',
    '*~.orig',
    '*.merge_file_*'
];

// Define unnecessary directories
$unnecessary_dirs = [
    'node_modules/',
    'vendor/bin/',
    '.git/',
    '.svn/',
    '.hg/',
    '__MACOSX/',
    '.vscode/',
    '.idea/',
    '*.tmp/',
    'temp/',
    'tmp/',
    'cache/',
    'logs/',
    'backup/',
    'uploads/temp/',
    'uploads/backup/'
];

// Get action from request
$action = sanitize_input($_GET['action'] ?? '');

if ($action === 'scan') {
    scan_unnecessary_files();
} elseif ($action === 'delete') {
    delete_unnecessary_files();
} else {
    show_main_options();
}

function show_main_options() {
    echo "<div class='alert alert-warning'>";
    echo "<h5><i class='fas fa-exclamation-triangle me-2'></i>تحذير هام:</h5>";
    echo "<p>هذه الأداة ستحذف الملفات الزائدة和非ضرورية فقط. جميع الملفات الأساسية محمية.</p>";
    echo "</div>";
    
    echo "<div class='row'>";
    echo "<div class='col-md-6'>";
    echo "<div class='card'>";
    echo "<div class='card-body text-center'>";
    echo "<i class='fas fa-search fa-3x text-primary mb-3'></i>";
    echo "<h5>فحص الملفات الزائدة</h5>";
    echo "<p class='text-muted'>البحث عن ملفات غير ضرورية</p>";
    echo "<a href='?action=scan' class='btn btn-primary'>";
    echo "<i class='fas fa-search me-2'></i>فحص</a>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='col-md-6'>";
    echo "<div class='card'>";
    echo "<div class='card-body text-center'>";
    echo "<i class='fas fa-trash-alt fa-3x text-danger mb-3'></i>";
    echo "<h5>حذف الملفات الزائدة</h5>";
    echo "<p class='text-muted'>حذف الملفات غير الضرورية</p>";
    echo "<a href='?action=delete' class='btn btn-danger' onclick='return confirm(\"هل أنت متأكد من حذف جميع الملفات الزائدة؟\")'>";
    echo "<i class='fas fa-trash me-2'></i>حذف</a>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    show_file_categories();
}

function scan_unnecessary_files() {
    echo "<h3>🔍 فحص الملفات الزائدة</h3>";
    
    $unnecessary_files = find_unnecessary_files();
    
    if (empty($unnecessary_files)) {
        echo "<div class='alert alert-success'>";
        echo "<i class='fas fa-check-circle me-2'></i>";
        echo "لا توجد ملفات زائدة في الموقع!";
        echo "</div>";
    } else {
        echo "<div class='alert alert-info'>";
        echo "<i class='fas fa-info-circle me-2'></i>";
        echo "تم العثور على " . count($unnecessary_files) . " ملف زائد";
        echo "</div>";
        
        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped'>";
        echo "<thead>";
        echo "<tr><th>الملف</th><th>الحجم</th><th>النوع</th><th>الإجراء</th></tr>";
        echo "</thead>";
        echo "<tbody>";
        
        foreach ($unnecessary_files as $file) {
            $size = format_bytes(filesize($file));
            $type = get_file_type($file);
            $relative_path = str_replace(__DIR__ . '/', '', $file);
            
            echo "<tr>";
            echo "<td><code>$relative_path</code></td>";
            echo "<td>$size</td>";
            echo "<td><span class='badge bg-secondary'>$type</span></td>";
            echo "<td>";
            echo "<button class='btn btn-sm btn-outline-danger' onclick='deleteFile(\"$relative_path\")'>";
            echo "<i class='fas fa-trash'></i>";
            echo "</button>";
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
    }
    
    echo "<div class='mt-3'>";
    echo "<a href='?' class='btn btn-primary'>🔙 رجوع</a>";
    echo "</div>";
}

function delete_unnecessary_files() {
    echo "<h3>🗑️ حذف الملفات الزائدة</h3>";
    
    $unnecessary_files = find_unnecessary_files();
    $deleted_count = 0;
    $total_size = 0;
    $errors = [];
    
    if (empty($unnecessary_files)) {
        echo "<div class='alert alert-success'>";
        echo "<i class='fas fa-check-circle me-2'></i>";
        echo "لا توجد ملفات زائدة لحذفها!";
        echo "</div>";
    } else {
        foreach ($unnecessary_files as $file) {
            try {
                $size = filesize($file);
                $relative_path = str_replace(__DIR__ . '/', '', $file);
                
                if (unlink($file)) {
                    $deleted_count++;
                    $total_size += $size;
                    echo "<p style='color: green; font-size: 12px;'>✅ تم حذف: $relative_path (" . format_bytes($size) . ")</p>";
                } else {
                    $errors[] = $relative_path;
                    echo "<p style='color: red; font-size: 12px;'>❌ فشل حذف: $relative_path</p>";
                }
            } catch (Exception $e) {
                $errors[] = $file;
                echo "<p style='color: red; font-size: 12px;'>❌ خطأ في حذف: $file - " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<div class='alert alert-success'>";
        echo "<i class='fas fa-check-circle me-2'></i>";
        echo "تم حذف $deleted_count ملف (حجم إجمالي: " . format_bytes($total_size) . ")";
        
        if (!empty($errors)) {
            echo "<br><strong>ملفات لم يتم حذفها:</strong><br>";
            foreach ($errors as $error) {
                echo "<code>$error</code><br>";
            }
        }
        echo "</div>";
    }
    
    echo "<div class='mt-3'>";
    echo "<a href='?' class='btn btn-primary'>🔙 رجوع</a>";
    echo "</div>";
}

function find_unnecessary_files() {
    $unnecessary_files = [];
    $root_dir = __DIR__;
    
    // Scan for files matching unnecessary patterns
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root_dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $filepath = $file->getPathname();
            $filename = $file->getFilename();
            $relative_path = str_replace($root_dir . '/', '', $filepath);
            
            // Skip essential files
            if (is_essential_file($relative_path)) {
                continue;
            }
            
            // Check if file matches unnecessary patterns
            foreach ($GLOBALS['unnecessary_patterns'] as $pattern) {
                if (fnmatch($pattern, $filename)) {
                    $unnecessary_files[] = $filepath;
                    break;
                }
            }
        }
    }
    
    // Check for unnecessary directories
    foreach ($GLOBALS['unnecessary_dirs'] as $dir_pattern) {
        $dir_path = $root_dir . '/' . $dir_pattern;
        if (is_dir($dir_path)) {
            $dir_iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir_path, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($dir_iterator as $file) {
                if ($file->isFile()) {
                    $filepath = $file->getPathname();
                    $relative_path = str_replace($root_dir . '/', '', $filepath);
                    
                    if (!is_essential_file($relative_path)) {
                        $unnecessary_files[] = $filepath;
                    }
                }
            }
        }
    }
    
    return array_unique($unnecessary_files);
}

function is_essential_file($filepath) {
    global $essential_files;
    
    foreach ($essential_files as $essential) {
        if (strpos($filepath, $essential) === 0 || $filepath === $essential) {
            return true;
        }
    }
    
    return false;
}

function get_file_type($filepath) {
    $filename = basename($filepath);
    
    if (fnmatch('*.tmp', $filename) || fnmatch('*.temp', $filename)) {
        return 'ملف مؤقت';
    } elseif (fnmatch('*.bak', $filename) || fnmatch('*.backup', $filename) || fnmatch('*.old', $filename)) {
        return 'نسخة احتياطية';
    } elseif (fnmatch('*.log', $filename)) {
        return 'سجل';
    } elseif (fnmatch('*~', $filename) || fnmatch('*.swp', $filename) || fnmatch('*.swo', $filename)) {
        return 'محرر نصوص';
    } elseif (fnmatch('.DS_Store', $filename) || fnmatch('Thumbs.db', $filename)) {
        return 'نظام تشغيل';
    } elseif (fnmatch('*.orig', $filename) || fnmatch('*.rej', $filename)) {
        return 'Git/SVN';
    } else {
        return 'غير معروف';
    }
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

function show_file_categories() {
    echo "<h3>📋 أنواع الملفات التي سيتم حذفها:</h3>";
    
    $categories = [
        'ملفات مؤقتة' => ['*.tmp', '*.temp'],
        'نسخ احتياطية قديمة' => ['*.bak', '*.backup', '*.old'],
        'ملفات السجلات' => ['*.log'],
        'ملفات المحررين' => ['*~', '*.swp', '*.swo'],
        'ملفات النظام' => ['.DS_Store', 'Thumbs.db'],
        'ملفات Git/SVN' => ['*.orig', '*.rej', '.#*', '#*#'],
        'مجلدات غير ضرورية' => ['node_modules/', 'cache/', 'temp/', 'logs/']
    ];
    
    echo "<div class='row'>";
    foreach ($categories as $category => $patterns) {
        echo "<div class='col-md-6 mb-3'>";
        echo "<div class='card'>";
        echo "<div class='card-body'>";
        echo "<h6 class='card-title'>$category</h6>";
        echo "<div class='small text-muted'>";
        foreach ($patterns as $pattern) {
            echo "<code>$pattern</code> ";
        }
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }
    echo "</div>";
    
    echo "<div class='alert alert-info mt-3'>";
    echo "<h5>📁 المجلدات المحمية (لن يتم حذفها):</h5>";
    echo "<div class='row'>";
    $protected_dirs = ['includes/', 'assets/', 'admin/', 'sql/', 'pdf/'];
    foreach ($protected_dirs as $dir) {
        echo "<div class='col-md-3'><code>$dir</code></div>";
    }
    echo "</div>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حذف الملفات الزائدة</title>
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
                <h1><i class="fas fa-trash-alt me-2"></i>حذف الملفات الزائدة</h1>
                <p class="text-muted">إزالة الملفات غير الضرورية مع حماية الملفات الأساسية</p>
            </div>
            
            <div class="alert alert-danger">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>تنبيه هام جداً:</h5>
                <ul>
                    <li>هذه الأداة ستحذف الملفات الزائدة فقط</li>
                    <li>جميع الملفات الأساسية محمية ولن يتم حذفها</li>
                    <li>يفضل استخدام "فحص" أولاً لمعرفة ما سيتم حذفه</li>
                    <li>تأكد من أخذ نسخة احتياطية قبل الحذف</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
        function deleteFile(filename) {
            if (confirm('هل أنت متأكد من حذف هذا الملف: ' + filename + '؟')) {
                fetch('?action=delete_single&file=' + encodeURIComponent(filename))
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('فشل حذف الملف: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('حدث خطأ أثناء حذف الملف');
                    });
            }
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
