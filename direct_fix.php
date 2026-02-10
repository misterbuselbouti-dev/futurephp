<?php
// FUTURE AUTOMOTIVE - Direct File Fix
// إصلاح مباشر للملفات

echo "<h2>🔧 إصلاح مباشر للملفات</h2>";

// إصلاح ملف buses_complete.php مباشرة
$buses_file = __DIR__ . '/buses_complete.php';

if (file_exists($buses_file)) {
    echo "<h3>📄 إصلاح buses_complete.php:</h3>";
    
    $content = file_get_contents($buses_file);
    $original_content = $content;
    
    // التحقق من وجود التيم البسيط
    if (strpos($content, 'simple-theme.css') === false) {
        echo "<p style='color: orange;'>⚠️ لم يتم العثور على simple-theme.css</p>";
        
        // إضافة التيم البسيط
        if (strpos($content, '<head>') !== false) {
            $content = str_replace(
                '<head>',
                '<head>
    <link rel="stylesheet" href="assets/css/simple-theme.css">',
                $content
            );
            echo "<p style='color: green;'>✅ تم إضافة simple-theme.css</p>";
        }
    }
    
    // التحقق من header_simple
    if (strpos($content, 'header_simple.php') === false) {
        echo "<p style='color: orange;'>⚠️ لم يتم العثور على header_simple.php</p>";
        
        // استبدال header بـ header_simple
        $content = str_replace("includes/header.php'", "includes/header_simple.php'", $content);
        echo "<p style='color: green;'>✅ تم تحديث header إلى header_simple.php</p>";
    }
    
    // إزالة التصميم المعقد
    if (strpos($content, 'linear-gradient') !== false) {
        echo "<p style='color: orange;'>⚠️ تم العثور على تصميم معقد - يتم إزالته</p>";
        
        // إزالة التدرجات اللونية
        $content = preg_replace('/background:\s*linear-gradient[^;]*;/', 'background: #f8f9fa;', $content);
        echo "<p style='color: green;'>✅ تم إزالة التدرجات اللونية</p>";
    }
    
    // حفظ التغييرات
    if ($content !== $original_content) {
        if (file_put_contents($buses_file, $content)) {
            echo "<p style='color: green; font-weight: bold;'>✅ تم حفظ التغييرات بنجاح</p>";
        } else {
            echo "<p style='color: red;'>❌ فشل حفظ التغييرات</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ الملف محدث بالفعل</p>";
    }
    
    // التحقق من النتيجة النهائية
    $updated_content = file_get_contents($buses_file);
    echo "<h4>🔍 التحقق النهائي:</h4>";
    
    if (strpos($updated_content, 'simple-theme.css') !== false) {
        echo "<p style='color: green;'>✅ يحتوي على simple-theme.css</p>";
    } else {
        echo "<p style='color: red;'>❌ لا يحتوي على simple-theme.css</p>";
    }
    
    if (strpos($updated_content, 'header_simple.php') !== false) {
        echo "<p style='color: green;'>✅ يستخدم header_simple.php</p>";
    } else {
        echo "<p style='color: red;'>❌ لا يستخدم header_simple.php</p>";
    }
    
    if (strpos($updated_content, 'linear-gradient') === false) {
        echo "<p style='color: green;'>✅ لا يحتوي على تدرجات لونية</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ لا يزال يحتوي على تدرجات لونية</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ ملف buses_complete.php غير موجود</p>";
}

echo "<h3>🔄 فرض تحديث الكاش:</h3>";

// إنشاء ملف بمعلمات تحديث الكاش
$cache_buster = "<?php\n";
$cache_buster .= "// Cache buster for buses_complete.php\n";
$cache_buster .= "\$cache_version = date('Y-m-d-H-i-s');\n";
$cache_buster .= "?>\n";

file_put_contents(__DIR__ . '/cache_buster.php', $cache_buster);

echo "<p style='color: green;'>✅ تم إنشاء cache_buster.php</p>";

echo "<h3>🔗 روابط الاختبار:</h3>";
echo "<div style='text-align: center; margin: 20px 0;'>";
echo "<a href='buses_complete.php?v=" . date('YmdHis') . "' class='btn' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px;'>🚌 فتح buses_complete.php (مع تحديث الكاش)</a>";
echo "<a href='force_update.php' class='btn' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px;'>🔧 تشخيص المشاكل</a>";
echo "</div>";

echo "<h3>📋 خطوات إضافية:</h3>";
echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 10px;'>";
echo "<ol>";
echo "<li><strong>افتح الرابط أعلاه</strong> مع معلمات تحديث الكاش</li>";
echo "<li><strong>اضغط Ctrl+F5</strong> لفرض تحديث الصفحة</li>";
echo "<li><strong>افتح وحدة التحكم</strong> (F12) وتحقق من عدم وجود أخطاء</li>";
echo "<li><strong>تحقق من الشبكة</strong> في وحدة التحكم للتأكد من تحميل الملفات</li>";
echo "<li><strong>إذا لم ينجح</strong>، أعد تسمية الملف القديم وارفع الجديد</li>";
echo "</ol>";
echo "</div>";

echo "<h3>🔍 فحص الملفات الحالية:</h3>";

$files_to_check = [
    'buses_complete.php',
    'assets/css/simple-theme.css',
    'includes/header_simple.php'
];

foreach ($files_to_check as $file) {
    $filepath = __DIR__ . '/' . $file;
    if (file_exists($filepath)) {
        $size = filesize($filepath);
        $modified = date('Y-m-d H:i:s', filemtime($filepath));
        echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0; border-radius: 5px;'>";
        echo "<strong>✅ $file</strong><br>";
        echo "الحجم: " . number_format($size) . " bytes | آخر تعديل: $modified";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>";
        echo "<strong>❌ $file</strong><br>";
        echo "الملف غير موجود!";
        echo "</div>";
    }
}
?>
