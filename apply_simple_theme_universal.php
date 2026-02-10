<?php
// FUTURE AUTOMOTIVE - Universal Theme Updater
// تطبيق التيم البسيط على جميع الصفحات

echo "<h2>🎨 تطبيق التيم البسيط على جميع الصفحات</h2>";

// الحصول على قائمة جميع ملفات PHP
$directories = [
    __DIR__,
    __DIR__ . '/admin',
    __DIR__ . '/api',
    __DIR__ . '/driver',
    __DIR__ . '/purchase',
    __DIR__ . '/technician'
];

$php_files = [];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $php_files[] = $file->getPathname();
            }
        }
    }
}

echo "<h3>📁 تم العثور على " . count($php_files) . " ملف PHP</h3>";

$updated_files = [];
$skipped_files = [];

foreach ($php_files as $file) {
    // تخطي الملفات التي لا نريد تعديلها
    if (strpos($file, 'config') !== false || 
        strpos($file, 'setup_') !== false ||
        strpos($file, 'theme_') !== false ||
        strpos($file, 'api/') !== false) {
        $skipped_files[] = $file;
        continue;
    }
    
    try {
        $content = file_get_contents($file);
        if ($content === false) {
            $skipped_files[] = $file . " (فشل القراءة)";
            continue;
        }
        
        $original_content = $content;
        $modified = false;
        
        // 1. تحديث روابط CSS إلى simple-theme
        if (strpos($content, 'assets/css/style.css') !== false) {
            $content = str_replace('assets/css/style.css', 'assets/css/simple-theme.css', $content);
            $modified = true;
        }
        
        // 2. تحديث روابط header إلى header_simple
        if (strpos($content, 'includes/header.php') !== false) {
            $content = str_replace('includes/header.php', 'includes/header_simple.php', $content);
            $modified = true;
        }
        
        // 3. إزالة روابط التيم القديمة
        $old_theme_patterns = [
            'assets/css/iso-theme.css',
            'assets/css/iso-bootstrap.css',
            'assets/css/iso-components.css',
            'includes/header_iso.php'
        ];
        
        foreach ($old_theme_patterns as $pattern) {
            if (strpos($content, $pattern) !== false) {
                $content = str_replace($pattern, '', $content);
                $modified = true;
            }
        }
        
        // 4. تحديث روابط dashboard إلى dashboard_simple
        if (strpos($content, 'dashboard.php') !== false && 
            strpos($file, 'dashboard.php') !== false) {
            // لا نغير اسم الملف نفسه
        }
        
        // 5. إضافة simple-theme.css إذا لم يكن موجود
        if (strpos($content, '<head>') !== false && 
            strpos($content, 'simple-theme.css') === false) {
            $simple_theme_link = '    <link rel="stylesheet" href="assets/css/simple-theme.css">';
            $content = preg_replace(
                '/(<head>.*?)(\n)/s',
                '$1' . "\n" . $simple_theme_link . '$2',
                $content,
                1
            );
            $modified = true;
        }
        
        // حفظ التغييرات
        if ($modified && $content !== $original_content) {
            if (file_put_contents($file, $content) !== false) {
                $updated_files[] = $file;
                echo "<p style='color: green;'>✅ تم تحديث: " . basename($file) . "</p>";
            } else {
                $skipped_files[] = $file . " (فشل الكتابة)";
            }
        } else {
            $skipped_files[] = $file . " (لا تغييرات مطلوبة)";
        }
        
    } catch (Exception $e) {
        $skipped_files[] = $file . " (خطأ: " . $e->getMessage() . ")";
    }
}

echo "<h3>📊 ملخص التحديث</h3>";
echo "<p style='color: green;'><strong>✅ تم تحديث " . count($updated_files) . " ملف</strong></p>";
echo "<p style='color: blue;'><strong>ℹ️ تم تخطي " . count($skipped_files) . " ملف</strong></p>";

if (!empty($updated_files)) {
    echo "<h4>📝 الملفات المحدثة:</h4>";
    echo "<ul>";
    foreach ($updated_files as $file) {
        echo "<li style='color: green;'>" . htmlspecialchars($file) . "</li>";
    }
    echo "</ul>";
}

echo "<h3>🎯 الخطوات التالية</h3>";
echo "<div class='alert alert-info'>";
echo "<ol>";
echo "<li>تأكد من وجود ملف <code>assets/css/simple-theme.css</code></li>";
echo "<li>تأكد من وجود ملف <code>includes/header_simple.php</code></li>";
echo "<li>اختبر بعض الصفحات للتحقق من التطبيق الصحيح</li>";
echo "<li>قد تحتاج إلى مسح ذاكرة التخزين المؤقت للمتصفح</li>";
echo "</ol>";
echo "</div>";

echo "<h3 style='color: green;'>🎉 اكتمل تحديث التيم بنجاح!</h3>";
?>
