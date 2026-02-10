<?php
// FUTURE AUTOMOTIVE - Quick Audit Results
// نتائج التدقيق السريع

require_once 'config.php';
require_once 'includes/functions.php';

// Check authentication
require_login();

echo "<h1>🔍 نتائج التدقيق السريع</h1>";

// Security Audit
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-warning text-dark'>";
echo "<h5 class='mb-0'>🔐 الأمان والصلاحيات</h5>";
echo "</div>";
echo "<div class='card-body'>";

$security_issues = [];
$security_recommendations = [];

// Check .htaccess
if (!file_exists('.htaccess')) {
    $security_issues[] = 'ملف .htaccess غير موجود';
    $security_recommendations[] = 'إنشاء ملف .htaccess لحماية الملفات الحساسة';
}

// Check config files permissions
$config_files = ['config.php', 'config_achat_hostinger.php'];
foreach ($config_files as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        if ($perms & 0x004) {
            $security_issues[] = "ملف $file قابل للقراءة من قبل المستخدمين الآخرين";
            $security_recommendations[] = "تغيير صلاحيات $file إلى 640 أو أقل";
        }
    }
}

// Check for debug mode
if (defined('DEBUG') && DEBUG) {
    $security_issues[] = 'وضع التصحيح (DEBUG) مفعّل';
    $security_recommendations[] = 'إلغاء تفعيل وضع التصحيح في بيئة الإنتاج';
}

if (empty($security_issues)) {
    echo "<div class='alert alert-success'>";
    echo "<i class='fas fa-check-circle me-2'></i>";
    echo "لم يتم العثور على مشاكل أمنية خطيرة!";
    echo "</div>";
} else {
    echo "<div class='alert alert-warning'>";
    echo "<h6><i class='fas fa-exclamation-triangle me-2'></i>المشاكل الأمنية:</h6>";
    echo "<ul>";
    foreach ($security_issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='alert alert-info'>";
    echo "<h6><i class='fas fa-lightbulb me-2'></i>توصيات الأمان:</h6>";
    echo "<ul>";
    foreach ($security_recommendations as $rec) {
        echo "<li>$rec</li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "</div>";
echo "</div>";

// Performance Audit
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h5 class='mb-0'>⚡ الأداء والسرعة</h5>";
echo "</div>";
echo "<div class='card-body'>";

$performance_issues = [];
$performance_recommendations = [];

// Check PHP version
$php_version = phpversion();
if (version_compare($php_version, '8.0', '<')) {
    $performance_issues[] = "إصدار PHP قديم: $php_version";
    $performance_recommendations[] = 'ترقية PHP إلى إصدار 8.0 أو أحدث';
}

// Check memory limit
$memory_limit = ini_get('memory_limit');
if ($memory_limit < '256M') {
    $performance_issues[] = "حد الذاكرة منخفض: $memory_limit";
    $performance_recommendations[] = 'زيادة memory_limit إلى 256M أو أكثر';
}

// Check for caching
if (!extension_loaded('apcu') && !extension_loaded('opcache')) {
    $performance_issues[] = 'لا يوجد نظام كاش مفعّل';
    $performance_recommendations[] = 'تفعيل OPcache أو APCu لتحسين الأداء';
}

if (empty($performance_issues)) {
    echo "<div class='alert alert-success'>";
    echo "<i class='fas fa-check-circle me-2'></i>";
    echo "الأداء جيد!";
    echo "</div>";
} else {
    echo "<div class='alert alert-warning'>";
    echo "<h6><i class='fas fa-exclamation-triangle me-2'></i>مشاكل الأداء:</h6>";
    echo "<ul>";
    foreach ($performance_issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='alert alert-info'>";
    echo "<h6><i class='fas fa-lightbulb me-2'></i>توصيات الأداء:</h6>";
    echo "<ul>";
    foreach ($performance_recommendations as $rec) {
        echo "<li>$rec</li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "</div>";
echo "</div>";

// Files Audit
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-secondary text-white'>";
echo "<h5 class='mb-0'>📁 الملفات والهيكل</h5>";
echo "</div>";
echo "<div class='card-body'>";

$file_issues = [];
$file_recommendations = [];

// Check for duplicate files
$file_hashes = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__, RecursiveDirectoryIterator::SKIP_DOTS)
);

$duplicate_count = 0;
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getSize() < 10 * 1024 * 1024) {
        $hash = md5_file($file->getPathname());
        if (isset($file_hashes[$hash])) {
            $duplicate_count++;
        }
        $file_hashes[$hash] = $file->getPathname();
    }
}

if ($duplicate_count > 0) {
    $file_issues[] = "توجد $duplicate_count ملفات مكررة";
    $file_recommendations[] = 'إزالة الملفات المكررة';
}

// Check for missing essential files
$essential_files = [
    'config.php',
    'includes/functions.php',
    'includes/header_simple.php',
    'assets/css/simple-theme.css'
];

$missing_files = [];
foreach ($essential_files as $file) {
    if (!file_exists($file)) {
        $missing_files[] = $file;
    }
}

if (!empty($missing_files)) {
    $file_issues[] = 'ملفات أساسية مفقودة';
    $file_recommendations[] = 'استعادة الملفات الأساسية المفقودة';
}

if (empty($file_issues)) {
    echo "<div class='alert alert-success'>";
    echo "<i class='fas fa-check-circle me-2'></i>";
    echo "هيكل الملفات جيد!";
    echo "</div>";
} else {
    echo "<div class='alert alert-warning'>";
    echo "<h6><i class='fas fa-exclamation-triangle me-2'></i>مشاكل الملفات:</h6>";
    echo "<ul>";
    foreach ($file_issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='alert alert-info'>";
    echo "<h6><i class='fas fa-lightbulb me-2'></i>توصيات الملفات:</h6>";
    echo "<ul>";
    foreach ($file_recommendations as $rec) {
        echo "<li>$rec</li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "</div>";
echo "</div>";

// Summary
echo "<div class='card'>";
echo "<div class='card-header bg-primary text-white'>";
echo "<h5 class='mb-0'>📊 ملخص التدقيق</h5>";
echo "</div>";
echo "<div class='card-body'>";

$total_issues = count($security_issues) + count($performance_issues) + count($file_issues);
$total_recommendations = count($security_recommendations) + count($performance_recommendations) + count($file_recommendations);

echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<div class='alert alert-warning'>";
echo "<h6>إجمالي المشاكل: $total_issues</h6>";
echo "<small>الأمان: " . count($security_issues) . "</small><br>";
echo "<small>الأداء: " . count($performance_issues) . "</small><br>";
echo "<small>الملفات: " . count($file_issues) . "</small>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-6'>";
echo "<div class='alert alert-info'>";
echo "<h6>إجمالي التوصيات: $total_recommendations</h6>";
echo "<small>الأمان: " . count($security_recommendations) . "</small><br>";
echo "<small>الأداء: " . count($performance_recommendations) . "</small><br>";
echo "<small>الملفات: " . count($file_recommendations) . "</small>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='mt-3'>";
echo "<a href='site_audit.php' class='btn btn-primary'>🔍 التدقيق الشامل</a>";
echo "<a href='dashboard_simple.php' class='btn btn-secondary ms-2'>🔙 لوحة التحكم</a>";
echo "</div>";

echo "</div>";
echo "</div>";

// Quick Stats
echo "<div class='card mt-4'>";
echo "<div class='card-header bg-dark text-white'>";
echo "<h5 class='mb-0'>📈 إحصائيات سريعة</h5>";
echo "</div>";
echo "<div class='card-body'>";

$root_dir = __DIR__;
$file_count = 0;
$total_size = 0;
$php_files = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root_dir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->isFile()) {
        $file_count++;
        $total_size += $file->getSize();
        
        if ($file->getExtension() === 'php') {
            $php_files++;
        }
    }
}

echo "<div class='row text-center'>";
echo "<div class='col-md-3'>";
echo "<h4>$file_count</h4>";
echo "<p class='text-muted'>ملفات</p>";
echo "</div>";
echo "<div class='col-md-3'>";
echo "<h4>" . number_format($total_size / 1024 / 1024, 2) . " MB</h4>";
echo "<p class='text-muted'>حجم</p>";
echo "</div>";
echo "<div class='col-md-3'>";
echo "<h4>$php_files</h4>";
echo "<p class='text-muted'>PHP</p>";
echo "</div>";
echo "<div class='col-md-3'>";
echo "<h4>" . round(($file_count > 0 ? ($php_files / $file_count) * 100 : 0), 1) . "%</h4>";
echo "<p class='text-muted'>PHP</p>";
echo "</div>";
echo "</div>";

echo "</div>";
echo "</div>";
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نتائج التدقيق السريع</title>
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
                <h1><i class="fas fa-clipboard-check me-2"></i>نتائج التدقيق السريع</h1>
                <p class="text-muted">تدقيق سريع لأهم جوانب الموقع</p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
