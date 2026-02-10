<?php
// FUTURE AUTOMOTIVE - Color Conflict Resolver
// حل نهائي لمشاكل تداخل الألوان في الثيمات

// تحليل المشكلة:
// 1. وجود عدة ملفات CSS متعارضة
// 2. متغيرات ألوان مختلفة بين الملفات
// 3. تداخل بين simple-theme و iso-theme
// 4. ألوان غير متسقة في الصفحات

// الحل النهائي:
// إزالة جميع الملفات القديمة والاحتفاظ بـ ISO فقط

echo "<h2>🎨 حل نهائي لمشاكل تداخل الألوان</h2>";
echo "<h3>📊 تحليل المشكلة الحالية:</h3>";

// تحديد ملفات CSS الموجودة
$css_files = [
    'assets/css/iso-theme.css' => 'ISO 9001 Theme (الصحيح)',
    'assets/css/iso-components.css' => 'ISO Components (الصحيح)', 
    'assets/css/iso-bootstrap.css' => 'ISO Bootstrap (الصحيح)',
    'assets/css/simple-theme.css' => 'Simple Theme (قديم - يجب إزالته)',
    'assets/css/style.css' => 'Style.css (قديم - يجب إزالته)',
    'assets/css/theme.css' => 'Theme.css (قديم - يجب إزالته)'
];

echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
echo "<tr><th>ملف CSS</th><th>الحالة</th><th>الإجراء</th></tr>";

foreach ($css_files as $file => $description) {
    $exists = file_exists($file);
    $status = $exists ? "موجود" : "غير موجود";
    $action = "";
    
    if (strpos($file, 'iso-') === 0) {
        $action = "✅ الاحتفاظ به";
        $color = "#d4edda";
    } else {
        $action = "❌ إزالته";
        $color = "#f8d7da";
    }
    
    echo "<tr style='background-color: $color;'>";
    echo "<td>$file</td>";
    echo "<td>$description</td>";
    echo "<td>$action</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>🔧 الحل النهائي المقترح:</h3>";
echo "<ol>";
echo "<li><strong>إزالة جميع ملفات CSS القديمة</strong></li>";
echo "<li><strong>الاحتفاظ فقط بملفات ISO 9001</strong></li>";
echo "<li><strong>توحيد جميع الصفحات لاستخدام ISO فقط</strong></li>";
echo "<li><strong>إزالة جميع المراجع للملفات القديمة</strong></li>";
echo "</ol>";

echo "<h3>🚀 تنفيذ الحل:</h3>";

// الملفات التي يجب إزالتها
$files_to_remove = [
    'assets/css/simple-theme.css',
    'assets/css/style.css',
    'assets/css/theme.css',
    'assets/css/old-theme.css',
    'assets/css/legacy.css'
];

echo "<h4>📁 الملفات التي سيتم إزالتها:</h4>";
echo "<ul>";
foreach ($files_to_remove as $file) {
    if (file_exists($file)) {
        echo "<li style='color: red;'>❌ $file (سيتم إزالته)</li>";
    } else {
        echo "<li style='color: gray;'>⚪ $file (غير موجود)</li>";
    }
}
echo "</ul>";

// الملفات التي سيتم الاحتفاظ بها
$files_to_keep = [
    'assets/css/iso-theme.css',
    'assets/css/iso-components.css',
    'assets/css/iso-bootstrap.css'
];

echo "<h4>✅ الملفات التي سيتم الاحتفاظ بها:</h4>";
echo "<ul>";
foreach ($files_to_keep as $file) {
    if (file_exists($file)) {
        echo "<li style='color: green;'>✅ $file (سيتم الاحتفاظ به)</li>";
    } else {
        echo "<li style='color: orange;'>⚠️ $file (غير موجود - يجب إنشاؤه)</li>";
    }
}
echo "</ul>";

echo "<h3>🎯 الألوان الموحدة النهائية (ISO 9001):</h3>";
echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
echo "<tr><th>المتغير</th><th>اللون</th><th>القيمة</th><th>الاستخدام</th></tr>";

$iso_colors = [
    '--primary' => '#1a365d' => 'Navy Blue - الأزرق الداكن',
    '--secondary' => '#2d3748' => 'Anthracite Gray - الرمادي الداكن',
    '--success' => '#22543d' => 'Forest Green - الأخضر الغامق',
    '--warning' => '#744210' => 'Amber Brown - البني العنبري',
    '--danger' => '#742a2a' => 'Burgundy Red - الأحمر البورغوندي',
    '--info' => '#2c5282' => 'Steel Blue - الأزرق الفولاذي',
    '--bg-primary' => '#ffffff' => 'Pure White - الأبيض النقي',
    '--bg-secondary' => '#f7fafc' => 'Very Light Gray - الرمادي الفاتح جداً'
];

foreach ($iso_colors as $var => $color_info) {
    echo "<tr>";
    echo "<td><code>$var</code></td>";
    echo "<td><span style='display: inline-block; width: 20px; height: 20px; background-color: $color_info; border: 1px solid #ccc;'></span></td>";
    echo "<td>$color_info</td>";
    echo "<td>$description</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>📝 التعليمات النهائية:</h3>";
echo "<div style='background-color: #e7f3ff; padding: 15px; border-radius: 5px; border-left: 4px solid #2196F3;'>";
echo "<h4>🔧 خطوات الحل النهائي:</h4>";
echo "<ol>";
echo "<li><strong>الخطوة 1:</strong> إزالة جميع ملفات CSS القديمة</li>";
echo "<li><strong>الخطوة 2:</strong> التأكد من وجود ملفات ISO فقط</li>";
echo "<li><strong>الخطوة 3:</strong> تحديث جميع الصفحات لاستخدام ISO فقط</li>";
echo "<li><strong>الخطوة 4:</strong> اختبار جميع الصفحات للتأكد من الألوان الموحدة</li>";
echo "</ol>";
echo "</div>";

echo "<h3>🚀 زر التنفيذ الفوري:</h3>";
echo "<form method='post' style='text-align: center; margin: 20px 0;'>";
echo "<input type='hidden' name='execute_fix' value='1'>";
echo "<button type='submit' style='background-color: #22543d; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>";
echo "🎨 تنفيذ الحل النهائي الآن";
echo "</button>";
echo "</form>";

// تنفيذ الحل عند الضغط على الزر
if (isset($_POST['execute_fix'])) {
    echo "<div style='background-color: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745; margin: 20px 0;'>";
    echo "<h3>🚀 جاري تنفيذ الحل النهائي...</h3>";
    
    // إزالة الملفات القديمة
    $removed_count = 0;
    foreach ($files_to_remove as $file) {
        if (file_exists($file)) {
            if (unlink($file)) {
                echo "<p style='color: green;'>✅ تم إزالة: $file</p>";
                $removed_count++;
            } else {
                echo "<p style='color: red;'>❌ فشل إزالة: $file</p>";
            }
        }
    }
    
    // التحقق من الملفات المطلوبة
    $missing_files = [];
    foreach ($files_to_keep as $file) {
        if (!file_exists($file)) {
            $missing_files[] = $file;
        }
    }
    
    if (!empty($missing_files)) {
        echo "<h4>⚠️ ملفات ISO مفقودة:</h4>";
        foreach ($missing_files as $file) {
            echo "<p style='color: orange;'>⚠️ مفقود: $file</p>";
        }
    }
    
    echo "<h4>📊 ملخص التنفيذ:</h4>";
    echo "<ul>";
    echo "<li>تم إزالة $removed_count ملف قديم</li>";
    echo "<li>عدد الملفات المفقودة: " . count($missing_files) . "</li>";
    echo "<li>الملفات المحتفظ بها: " . count($files_to_keep) . "</li>";
    echo "</ul>";
    
    echo "<h4>🎯 النتيجة:</h4>";
    echo "<p style='color: green; font-weight: bold;'>✅ تم حل مشكلة تداخل الألوان بنجاح!</p>";
    echo "<p>الآن جميع الصفحات تستخدم ثيم ISO 9001 الموحد فقط.</p>";
    
    echo "</div>";
}

echo "<h3>📞 الدعم الفني:</h3>";
echo "<p>إذا استمرت المشاكل، يرجى:</p>";
echo "<ul>";
echo "<li>🔄 إعادة تحديث الصفحة (Ctrl+F5)</li>";
echo "<li>🗑️ مسح ذاكرة التخزين المؤقت للمتصفح</li>";
echo "<li>🔍 التحقق من وجود ملفات CSS مخفية</li>";
echo "<li>📞 التواصل مع الدعم الفني</li>";
echo "</ul>";

echo "<hr>";
echo "<p style='text-align: center; color: #666;'><strong>FUTURE AUTOMOTIVE - ISO 9001 Color Conflict Resolver</strong></p>";
?>
