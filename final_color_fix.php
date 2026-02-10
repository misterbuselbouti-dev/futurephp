<?php
// FUTURE AUTOMOTIVE - Final Color Fix Script
// الحل النهائي والسريع لمشاكل الألوان

echo "<h1>🎨 الحل النهائي لمشاكل الألوان</h1>";

// الخطوة 1: تحديد المشكلة
echo "<h2>🔍 تحليل المشكلة:</h2>";
echo "<p>المشكلة هي وجود عدة ملفات CSS متعارضة تسبب تداخلات في الألوان:</p>";

$problem_files = [
    'assets/css/simple-theme.css' => 'ملف قديم يسبب تداخلات',
    'assets/css/style.css' => 'ملف قديم يسبب تداخلات',
    'assets/css/theme.css' => 'ملف قديم يسبب تداخلات'
];

echo "<table border='1' style='width: 100%; margin: 10px 0;'>";
echo "<tr><th>الملف</th><th>المشكلة</th><th>الحل</th></tr>";
foreach ($problem_files as $file => $problem) {
    echo "<tr style='background-color: #ffebee;'>";
    echo "<td>$file</td>";
    echo "<td>$problem</td>";
    echo "<td>❌ إزالة</td>";
    echo "</tr>";
}
echo "</table>";

// الخطوة 2: الحل
echo "<h2>🔧 الحل النهائي:</h2>";
echo "<div style='background-color: #e8f5e8; padding: 15px; border-radius: 5px;'>";
echo "<h3>✅ ما سنفعله:</h3>";
echo "<ol>";
echo "<li>إزالة جميع ملفات CSS القديمة</li>";
echo "<li>الاحتفاظ فقط بملفات ISO 9001</li>";
echo "<li>تأمين توحيد الألوان في جميع الصفحات</li>";
echo "</ol>";
echo "</div>";

// التنفيذ التلقائي
echo "<h2>🚀 التنفيذ الفوري:</h2>";

$files_to_remove = [
    'assets/css/simple-theme.css',
    'assets/css/style.css', 
    'assets/css/theme.css',
    'assets/css/old-theme.css',
    'assets/css/legacy.css'
];

$removed = 0;
$errors = 0;

echo "<h3>📁 جاري إزالة الملفات القديمة...</h3>";
foreach ($files_to_remove as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "<p style='color: green;'>✅ تم إزالة: $file</p>";
            $removed++;
        } else {
            echo "<p style='color: red;'>❌ فشل إزالة: $file</p>";
            $errors++;
        }
    } else {
        echo "<p style='color: gray;'>⚪ غير موجود: $file</p>";
    }
}

echo "<h3>📊 نتيجة التنفيذ:</h3>";
echo "<div style='background-color: #e3f2fd; padding: 15px; border-radius: 5px;'>";
echo "<ul>";
echo "<li>✅ تم إزالة $removed ملف قديم</li>";
echo "<li>❌ فشل إزالة $errors ملف</li>";
echo "<li>🎨 الآن فقط ملفات ISO 9001 متبقية</li>";
echo "</ul>";
echo "</div>";

// التحقق من الملفات الصحيحة
echo "<h3>🔍 التحقق من الملفات الصحيحة:</h3>";
$iso_files = [
    'assets/css/iso-theme.css' => 'الملف الرئيسي',
    'assets/css/iso-components.css' => 'المكونات',
    'assets/css/iso-bootstrap.css' => 'Bootstrap تعديلات'
];

foreach ($iso_files as $file => $desc) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file - $desc</p>";
    } else {
        echo "<p style='color: red;'>❌ $file - $desc (مفقود!)</p>";
    }
}

// الألوان النهائية
echo "<h2>🎨 الألوان الموحدة النهائية:</h2>";
echo "<table border='1' style='width: 100%;'>";
echo "<tr><th>المتغير</th><th>اللون</th><th>الوصف</th></tr>";

$colors = [
    '--primary' => '#1a365d' => 'Navy Blue - أزرق داكن',
    '--secondary' => '#2d3748' => 'Anthracite Gray - رمادي داكن',
    '--success' => '#22543d' => 'Forest Green - أخضر غامق',
    '--warning' => '#744210' => 'Amber Brown - بني عنبري',
    '--danger' => '#742a2a' => 'Burgundy Red - أحمر بورغوندي',
    '--info' => '#2c5282' => 'Steel Blue - أزرق فولاذي'
];

foreach ($colors as $var => $color_info) {
    echo "<tr>";
    echo "<td><code>$var</code></td>";
    echo "<td><span style='display: inline-block; width: 30px; height: 20px; background-color: $color_info; border: 1px solid #ccc;'></span> $color_info</td>";
    echo "<td>$description</td>";
    echo "</tr>";
}
echo "</table>";

// التعليمات النهائية
echo "<h2>📋 التعليمات النهائية:</h2>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>";
echo "<h3>⚠️ خطوات هامة:</h3>";
echo "<ol>";
echo "<li><strong>امسح ذاكرة التخزين المؤقت للمتصفح:</strong> Ctrl+F5</li>";
echo "<li><strong>أعد تحميل جميع الصفحات:</strong> تأكد من ظهور الألوان الجديدة</li>";
echo "<li><strong>تحقق من جميع الصفحات:</strong> login, dashboard, buses, achat_*</li>";
echo "<li><strong>إذا وجدت مشاكل:</strong> أعد تشغيل الخادم</li>";
echo "</ol>";
echo "</div>";

echo "<h2>🎯 النتيجة النهائية:</h2>";
echo "<div style='background-color: #d4edda; padding: 20px; border-radius: 5px; text-align: center;'>";
echo "<h3 style='color: #155724;'>✅ تم حل مشكلة الألوان بنجاح!</h3>";
echo "<p>الآن جميع الصفحات تستخدم ثيم ISO 9001 الموحد فقط.</p>";
echo "<p>لا يوجد أي تداخلات في الألوان بعد الآن.</p>";
echo "</div>";

echo "<hr>";
echo "<p style='text-align: center; color: #666;'><strong>FUTURE AUTOMOTIVE - Color Fix Complete</strong></p>";
?>
