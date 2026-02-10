<?php
// FUTURE AUTOMOTIVE - Complete Site Audit
// تدقيق شامل للموقع

require_once 'config.php';
require_once 'includes/functions.php';

// Check authentication
require_login();

echo "<h1>🔍 تدقيق شامل للموقع - Complete Site Audit</h1>";

// Audit categories
$audit_categories = [
    'security' => 'الأمان والصلاحيات',
    'performance' => 'الأداء والسرعة',
    'database' => 'قاعدة البيانات',
    'files' => 'الملفات والهيكل',
    'code_quality' => 'جودة الكود',
    'user_experience' => 'تجربة المستخدم',
    'backup' => 'النسخ الاحتياطي',
    'documentation' => 'التوثيق'
];

// Get audit section from request
$section = sanitize_input($_GET['section'] ?? '');

if ($section && isset($audit_categories[$section])) {
    run_audit_section($section);
} else {
    show_audit_overview();
}

function show_audit_overview() {
    global $audit_categories;
    
    echo "<div class='alert alert-info'>";
    echo "<h5><i class='fas fa-info-circle me-2'></i>نظرة عامة على التدقيق</h5>";
    echo "<p>هذا التدقيق سيقوم بفحص جميع جوانب الموقع وتحديد النقاط الضعف والنواقص.</p>";
    echo "</div>";
    
    echo "<div class='row'>";
    foreach ($audit_categories as $key => $label) {
        echo "<div class='col-md-6 mb-3'>";
        echo "<div class='card'>";
        echo "<div class='card-body'>";
        echo "<h5 class='card-title'>$label</h5>";
        echo "<p class='text-muted'>فحص $key</p>";
        echo "<a href='?section=$key' class='btn btn-primary'>";
        echo "<i class='fas fa-search me-2'></i>فحص</a>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }
    echo "</div>";
    
    echo "<div class='mt-4'>";
    echo "<a href='?section=all' class='btn btn-success' onclick='return confirm(\"هل تريد تشغيل التدقيق الشامل؟ قد يستغرق بعض الوقت.\")'>";
    echo "<i class='fas fa-play me-2'></i>تشغيل التدقيق الشامل</a>";
    echo "</div>";
    
    show_quick_stats();
}

function run_audit_section($section) {
    echo "<h3>🔍 فحص: " . $GLOBALS['audit_categories'][$section] . "</h3>";
    
    switch ($section) {
        case 'security':
            audit_security();
            break;
        case 'performance':
            audit_performance();
            break;
        case 'database':
            audit_database();
            break;
        case 'files':
            audit_files();
            break;
        case 'code_quality':
            audit_code_quality();
            break;
        case 'user_experience':
            audit_user_experience();
            break;
        case 'backup':
            audit_backup();
            break;
        case 'documentation':
            audit_documentation();
            break;
        case 'all':
            run_complete_audit();
            break;
    }
    
    echo "<div class='mt-3'>";
    echo "<a href='?' class='btn btn-primary'>🔙 رجوع</a>";
    echo "</div>";
}

function audit_security() {
    $issues = [];
    $recommendations = [];
    
    echo "<h4>🔐 تدقيق الأمان والصلاحيات</h4>";
    
    // Check .htaccess
    if (!file_exists('.htaccess')) {
        $issues[] = 'ملف .htaccess غير موجود';
        $recommendations[] = 'إنشاء ملف .htaccess لحماية الملفات الحساسة';
    }
    
    // Check config files permissions
    $config_files = ['config.php', 'config_achat_hostinger.php'];
    foreach ($config_files as $file) {
        if (file_exists($file)) {
            $perms = fileperms($file);
            if ($perms & 0x004) { // Readable by others
                $issues[] = "ملف $file قابل للقراءة من قبل المستخدمين الآخرين";
                $recommendations[] = "تغيير صلاحيات $file إلى 640 أو أقل";
            }
        }
    }
    
    // Check for debug mode
    if (defined('DEBUG') && DEBUG) {
        $issues[] = 'وضع التصحيح (DEBUG) مفعّل';
        $recommendations[] = 'إلغاء تفعيل وضع التصحيح في بيئة الإنتاج';
    }
    
    // Check session security
    if (ini_get('session.cookie_httponly') !== '1') {
        $issues[] = 'ملفات تعريف الارتباط غير محمية بـ HttpOnly';
        $recommendations[] = 'تفعيل session.cookie_httponly';
    }
    
    // Check for exposed sensitive files
    $sensitive_patterns = ['*.sql', '*.env*', '*.key', '*.pem'];
    foreach ($sensitive_patterns as $pattern) {
        $files = glob($pattern);
        if (!empty($files)) {
            $issues[] = "ملفات حساسة معرضة: $pattern";
            $recommendations[] = 'نقل الملفات الحساسة خارج المجلد العام';
        }
    }
    
    display_audit_results($issues, $recommendations);
}

function audit_performance() {
    $issues = [];
    $recommendations = [];
    
    echo "<h4>⚡ تدقيق الأداء والسرعة</h4>";
    
    // Check PHP version
    $php_version = phpversion();
    if (version_compare($php_version, '8.0', '<')) {
        $issues[] = "إصدار PHP قديم: $php_version";
        $recommendations[] = 'ترقية PHP إلى إصدار 8.0 أو أحدث';
    }
    
    // Check memory limit
    $memory_limit = ini_get('memory_limit');
    if ($memory_limit < '256M') {
        $issues[] = "حد الذاكرة منخفض: $memory_limit";
        $recommendations[] = 'زيادة memory_limit إلى 256M أو أكثر';
    }
    
    // Check for caching
    if (!extension_loaded('apcu') && !extension_loaded('opcache')) {
        $issues[] = 'لا يوجد نظام كاش مفعّل';
        $recommendations[] = 'تفعيل OPcache أو APCu لتحسين الأداء';
    }
    
    // Check file sizes
    $large_files = find_large_files(__DIR__, 5 * 1024 * 1024); // 5MB
    if (!empty($large_files)) {
        $issues[] = 'توجد ملفات كبيرة جداً';
        $recommendations[] = 'ضغط الملفات الكبيرة أو نقلها خارج الموقع';
    }
    
    // Check database queries (basic check)
    try {
        $database = new Database();
        $pdo = $database->connect();
        
        $stmt = $pdo->query("SHOW TABLE STATUS");
        $tables = $stmt->fetchAll();
        
        foreach ($tables as $table) {
            if ($table['Rows'] > 10000) {
                $issues[] = "جدول {$table['Name']} يحتوي على عدد كبير من السجلات: {$table['Rows']}";
                $recommendations[] = "فهرسة الجداول الكبيرة أو تقسيمها";
            }
        }
    } catch (Exception $e) {
        $issues[] = 'لا يمكن التحقق من قاعدة البيانات';
    }
    
    display_audit_results($issues, $recommendations);
}

function audit_database() {
    $issues = [];
    $recommendations = [];
    
    echo "<h4>🗄️ تدقيق قاعدة البيانات</h4>";
    
    try {
        $database = new Database();
        $pdo = $database->connect();
        
        // Check for missing indexes
        $tables = ['buses', 'clients', 'suppliers', 'bons_commande', 'bons_entree'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW INDEX FROM $table");
            $indexes = $stmt->fetchAll();
            
            if (count($indexes) <= 1) { // Only primary index
                $issues[] = "جدول $table يفتقر إلى فهارس ثانوية";
                $recommendations[] = "إضافة فهارس للحقول المستخدمة في البحث";
            }
        }
        
        // Check for foreign key constraints
        $stmt = $pdo->query("
            SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $foreign_keys = $stmt->fetchAll();
        
        if (empty($foreign_keys)) {
            $issues[] = 'لا توجد قيود المفتاح الأجنبي';
            $recommendations[] = 'إضافة قيود المفتاح الأجنبي للحفاظ على تكامل البيانات';
        }
        
        // Check for orphaned records
        $stmt = $pdo->query("
            SELECT COUNT(*) as count FROM bons_commande bc
            LEFT JOIN clients c ON bc.client_id = c.id
            WHERE c.id IS NULL
        ");
        $orphaned = $stmt->fetch();
        
        if ($orphaned['count'] > 0) {
            $issues[] = "توجد {$orphaned['count']} سجلات يتيمة في bons_commande";
            $recommendations[] = 'تنظيف السجلات اليتيمة أو إضافة قيود المفتاح الأجنبي';
        }
        
        // Check database size
        $stmt = $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size FROM information_schema.tables WHERE table_schema = DATABASE()");
        $db_size = $stmt->fetch();
        
        if ($db_size['size'] > 100) {
            $issues[] = "حجم قاعدة البيانات كبير: {$db_size['size']} MB";
            $recommendations[] = 'أرشفة البيانات القديمة أو تحسين الهيكل';
        }
        
    } catch (Exception $e) {
        $issues[] = 'لا يمكن الاتصال بقاعدة البيانات: ' . $e->getMessage();
        $recommendations[] = 'فحص إعدادات قاعدة البيانات';
    }
    
    display_audit_results($issues, $recommendations);
}

function audit_files() {
    $issues = [];
    $recommendations = [];
    
    echo "<h4>📁 تدقيق الملفات والهيكل</h4>";
    
    // Check for duplicate files
    $file_hashes = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(__DIR__, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getSize() < 10 * 1024 * 1024) { // Less than 10MB
            $hash = md5_file($file->getPathname());
            if (isset($file_hashes[$hash])) {
                $issues[] = "ملف مكرر: {$file->getFilename()}";
                $recommendations[] = 'إزالة الملفات المكررة';
            }
            $file_hashes[$hash] = $file->getPathname();
        }
    }
    
    // Check for empty directories
    $empty_dirs = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(__DIR__, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $dir) {
        if ($dir->isDir() && count(scandir($dir->getPathname())) <= 2) {
            $empty_dirs[] = $dir->getPathname();
        }
    }
    
    if (count($empty_dirs) > 5) {
        $issues[] = 'توجد مجلدات فارغة كثيرة';
        $recommendations[] = 'إزالة المجلدات الفارغة غير الضرورية';
    }
    
    // Check file permissions
    $problematic_files = [];
    foreach (['config.php', 'includes/', 'assets/'] as $item) {
        $path = __DIR__ . '/' . $item;
        if (file_exists($path)) {
            if (is_dir($path)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
                );
                foreach ($iterator as $file) {
                    if ($file->isFile() && !is_readable($file->getPathname())) {
                        $problematic_files[] = $file->getPathname();
                    }
                }
            } elseif (!is_readable($path)) {
                $problematic_files[] = $path;
            }
        }
    }
    
    if (!empty($problematic_files)) {
        $issues[] = 'ملفات غير قابلة للقراءة';
        $recommendations[] = 'فحص وتصحيح صلاحيات الملفات';
    }
    
    // Check for missing essential files
    $essential_files = [
        'config.php',
        'includes/functions.php',
        'includes/header_simple.php',
        'assets/css/simple-theme.css'
    ];
    
    foreach ($essential_files as $file) {
        if (!file_exists($file)) {
            $issues[] = "ملف أساسي مفقود: $file";
            $recommendations[] = 'استعادة الملفات الأساسية المفقودة';
        }
    }
    
    display_audit_results($issues, $recommendations);
}

function audit_code_quality() {
    $issues = [];
    $recommendations = [];
    
    echo "<h4>📝 تدقيق جودة الكود</h4>";
    
    // Check for deprecated functions
    $php_files = glob('*.php');
    foreach ($php_files as $file) {
        $content = file_get_contents($file);
        
        // Check for deprecated functions
        $deprecated = ['mysql_', 'ereg', 'split', 'each'];
        foreach ($deprecated as $func) {
            if (strpos($content, $func) !== false) {
                $issues[] = "استخدام دوال قديمة في $file: $func";
                $recommendations[] = 'تحديث الدوال القديمة إلى البدائل الحديثة';
            }
        }
        
        // Check for SQL injection vulnerabilities
        if (strpos($content, '$_GET') !== false || strpos($content, '$_POST') !== false) {
            if (strpos($content, 'prepare') === false) {
                $issues[] = "احتمال ثغرة SQL injection في $file";
                $recommendations[] = 'استخدام prepared statements لجميع استعلامات SQL';
            }
        }
        
        // Check for XSS vulnerabilities
        if (strpos($content, 'echo') !== false) {
            if (strpos($content, 'htmlspecialchars') === false && strpos($content, 'filter_var') === false) {
                $issues[] = "احتمال ثغرة XSS في $file";
                $recommendations[] = 'استخدام htmlspecialchars لجميع المخرجات';
            }
        }
    }
    
    // Check for error reporting
    if (ini_get('display_errors') === '1') {
        $issues[] = 'عرض الأخطاء مفعّل في بيئة الإنتاج';
        $recommendations[] = 'إلغاء تفعيل display_errors في بيئة الإنتاج';
    }
    
    // Check for code comments
    $total_lines = 0;
    $commented_lines = 0;
    
    foreach ($php_files as $file) {
        $lines = file($file);
        $total_lines += count($lines);
        
        foreach ($lines as $line) {
            if (preg_match('/^\s*\/\//', $line) || preg_match('/^\s*\*/', $line)) {
                $commented_lines++;
            }
        }
    }
    
    $comment_ratio = $total_lines > 0 ? ($commented_lines / $total_lines) * 100 : 0;
    if ($comment_ratio < 10) {
        $issues[] = 'نقص في توثيق الكود';
        $recommendations[] = 'إضافة المزيد من التعليقات والتوثيق';
    }
    
    display_audit_results($issues, $recommendations);
}

function audit_user_experience() {
    $issues = [];
    $recommendations = [];
    
    echo "<h4>👤 تدقيق تجربة المستخدم</h4>";
    
    // Check for responsive design
    $css_files = glob('assets/css/*.css');
    $has_responsive = false;
    
    foreach ($css_files as $file) {
        $content = file_get_contents($file);
        if (strpos($content, '@media') !== false || strpos($content, 'responsive') !== false) {
            $has_responsive = true;
            break;
        }
    }
    
    if (!$has_responsive) {
        $issues[] = 'التصميم غير متجاوب';
        $recommendations[] = 'إضافة تصميم متجاوب للأجهزة المحمولة';
    }
    
    // Check for loading speed indicators
    $index_file = 'index.php';
    if (file_exists($index_file)) {
        $content = file_get_contents($index_file);
        
        // Check for minified CSS/JS
        if (strpos($content, '.min.') === false) {
            $issues[] = 'لا تستخدم ملفات CSS/JS مضغوطة';
            $recommendations[] = 'استخدام ملفات minified لتحسين سرعة التحميل';
        }
        
        // Check for lazy loading
        if (strpos($content, 'loading="lazy"') === false) {
            $issues[] = 'لا يستخدم تحميل بطيء للصور';
            $recommendations[] = 'إضافة loading="lazy" للصور';
        }
    }
    
    // Check for accessibility
    $php_files = glob('*.php');
    $has_alt_tags = false;
    
    foreach ($php_files as $file) {
        $content = file_get_contents($file);
        if (strpos($content, 'alt=') !== false) {
            $has_alt_tags = true;
            break;
        }
    }
    
    if (!$has_alt_tags) {
        $issues[] = 'نقص في وسائل الوصول';
        $recommendations[] = 'إضافة وسائل الوصول مثل alt tags للصور';
    }
    
    // Check for error handling
    $has_error_handling = false;
    foreach ($php_files as $file) {
        $content = file_get_contents($file);
        if (strpos($content, 'try') !== false && strpos($content, 'catch') !== false) {
            $has_error_handling = true;
            break;
        }
    }
    
    if (!$has_error_handling) {
        $issues[] = 'نقص في معالجة الأخطاء';
        $recommendations[] = 'إضافة معالجة الأخطاء لتحسين تجربة المستخدم';
    }
    
    display_audit_results($issues, $recommendations);
}

function audit_backup() {
    $issues = [];
    $recommendations = [];
    
    echo "<h4>💾 تدقيق النسخ الاحتياطي</h4>";
    
    // Check for backup files
    $backup_patterns = ['*.sql', '*.backup', '*.bak'];
    $has_backup = false;
    
    foreach ($backup_patterns as $pattern) {
        $files = glob($pattern);
        if (!empty($files)) {
            $has_backup = true;
            break;
        }
    }
    
    if (!$has_backup) {
        $issues[] = 'لا توجد نسخ احتياطية واضحة';
        $recommendations[] = 'إنشاء نظام نسخ احتياطي منتظم';
    }
    
    // Check for backup automation
    $cron_files = ['cron.php', 'backup.php', 'schedule.php'];
    $has_automation = false;
    
    foreach ($cron_files as $file) {
        if (file_exists($file)) {
            $has_automation = true;
            break;
        }
    }
    
    if (!$has_automation) {
        $issues[] = 'لا يوجد أتمتة للنسخ الاحتياطي';
        $recommendations[] = 'إنشاء نظام أتمتة للنسخ الاحتياطي';
    }
    
    // Check for backup retention policy
    if ($has_backup) {
        $backup_files = [];
        foreach ($backup_patterns as $pattern) {
            $backup_files = array_merge($backup_files, glob($pattern));
        }
        
        $old_backups = [];
        foreach ($backup_files as $file) {
            if (filemtime($file) < time() - (30 * 24 * 60 * 60)) { // Older than 30 days
                $old_backups[] = $file;
            }
        }
        
        if (count($old_backups) > 5) {
            $issues[] = 'نسخ احتياطية قديمة كثيرة';
            $recommendations[] = 'تنظيف النسخ الاحتياطية القديمة';
        }
    }
    
    display_audit_results($issues, $recommendations);
}

function audit_documentation() {
    $issues = [];
    $recommendations = [];
    
    echo "<h4>📚 تدقيق التوثيق</h4>";
    
    // Check for documentation files
    $doc_files = ['README.md', 'CHANGELOG.md', 'INSTALL.md', 'docs/'];
    $missing_docs = [];
    
    foreach ($doc_files as $doc) {
        if (!file_exists($doc)) {
            $missing_docs[] = $doc;
        }
    }
    
    if (!empty($missing_docs)) {
        $issues[] = 'نقص في التوثيق';
        $recommendations[] = 'إنشاء ملفات التوثيق الأساسية';
    }
    
    // Check for API documentation
    $api_files = glob('api/*.php');
    if (!empty($api_files)) {
        $has_api_docs = false;
        foreach ($api_files as $file) {
            $content = file_get_contents($file);
            if (strpos($content, '@param') !== false || strpos($content, '@return') !== false) {
                $has_api_docs = true;
                break;
            }
        }
        
        if (!$has_api_docs) {
            $issues[] = 'نقص في توثيق API';
            $recommendations[] = 'إضافة توثيق لـ API endpoints';
        }
    }
    
    // Check for database schema documentation
    if (!file_exists('sql/schema.sql') && !file_exists('docs/database.md')) {
        $issues[] = 'نقص في توثيق قاعدة البيانات';
        $recommendations[] = 'إنشاء توثيق لهيكل قاعدة البيانات';
    }
    
    display_audit_results($issues, $recommendations);
}

function run_complete_audit() {
    echo "<h3>🔍 التدقيق الشامل جاري التنفيذ...</h3>";
    
    $all_sections = array_keys($GLOBALS['audit_categories']);
    $total_issues = 0;
    $total_recommendations = 0;
    
    foreach ($all_sections as $section) {
        echo "<h4>فحص: " . $GLOBALS['audit_categories'][$section] . "</h4>";
        
        ob_start();
        run_audit_section($section);
        $output = ob_get_clean();
        
        // Count issues and recommendations
        $issue_count = substr_count($output, 'class="alert alert-warning"');
        $recommendation_count = substr_count($output, '<li>');
        
        $total_issues += $issue_count;
        $total_recommendations += $recommendation_count;
        
        echo $output;
        echo "<hr>";
    }
    
    echo "<div class='alert alert-info'>";
    echo "<h5>📊 ملخص التدقيق الشامل:</h5>";
    echo "<p>إجمالي المشاكل: $total_issues</p>";
    echo "<p>إجمالي التوصيات: $total_recommendations</p>";
    echo "</div>";
}

function display_audit_results($issues, $recommendations) {
    if (empty($issues)) {
        echo "<div class='alert alert-success'>";
        echo "<i class='fas fa-check-circle me-2'></i>";
        echo "لم يتم العثور على مشاكل في هذا القسم!";
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning'>";
        echo "<h5><i class='fas fa-exclamation-triangle me-2'></i>المشاكل المكتشفة:</h5>";
        echo "<ul>";
        foreach ($issues as $issue) {
            echo "<li>$issue</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    if (!empty($recommendations)) {
        echo "<div class='alert alert-info'>";
        echo "<h5><i class='fas fa-lightbulb me-2'></i>التوصيات:</h5>";
        echo "<ul>";
        foreach ($recommendations as $recommendation) {
            echo "<li>$recommendation</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
}

function find_large_files($dir, $min_size) {
    $large_files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getSize() > $min_size) {
            $large_files[] = $file->getPathname();
        }
    }
    
    return $large_files;
}

function show_quick_stats() {
    echo "<h3>📊 إحصائيات سريعة</h3>";
    
    $root_dir = __DIR__;
    $file_count = 0;
    $total_size = 0;
    $php_files = 0;
    $css_files = 0;
    $js_files = 0;
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root_dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $file_count++;
            $total_size += $file->getSize();
            
            $ext = $file->getExtension();
            if ($ext === 'php') $php_files++;
            elseif ($ext === 'css') $css_files++;
            elseif ($ext === 'js') $js_files++;
        }
    }
    
    echo "<div class='row'>";
    echo "<div class='col-md-3'>";
    echo "<div class='card text-center'>";
    echo "<div class='card-body'>";
    echo "<h4>$file_count</h4>";
    echo "<p class='text-muted'>إجمالي الملفات</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='col-md-3'>";
    echo "<div class='card text-center'>";
    echo "<div class='card-body'>";
    echo "<h4>" . format_bytes($total_size) . "</h4>";
    echo "<p class='text-muted'>حجم الموقع</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='col-md-3'>";
    echo "<div class='card text-center'>";
    echo "<div class='card-body'>";
    echo "<h4>$php_files</h4>";
    echo "<p class='text-muted'>ملفات PHP</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='col-md-3'>";
    echo "<div class='card text-center'>";
    echo "<div class='card-body'>";
    echo "<h4>" . ($css_files + $js_files) . "</h4>";
    echo "<p class='text-muted'>ملفات CSS/JS</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
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
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تدقيق شامل للموقع</title>
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
                <h1><i class="fas fa-clipboard-check me-2"></i>تدقيق شامل للموقع</h1>
                <p class="text-muted">فحص شامل للموقع لتحديد النقاط الضعف والنواقص</p>
            </div>
            
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle me-2"></i>معلومات التدقيق:</h5>
                <p>هذا التدقيق يقوم بفحص 8 فئات رئيسية:</p>
                <ul>
                    <li>🔐 الأمان والصلاحيات</li>
                    <li>⚡ الأداء والسرعة</li>
                    <li>🗄️ قاعدة البيانات</li>
                    <li>📁 الملفات والهيكل</li>
                    <li>📝 جودة الكود</li>
                    <li>👤 تجربة المستخدم</li>
                    <li>💾 النسخ الاحتياطي</li>
                    <li>📚 التوثيق</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
