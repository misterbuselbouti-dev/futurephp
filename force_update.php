<?php
// FUTURE AUTOMOTIVE - Force File Update
// فرض تحديث الملفات

echo "<h2>🔄 فرض تحديث الملفات</h2>";

// قائمة الملفات الهامة للتحديث
$important_files = [
    'buses_complete.php',
    'dashboard.php',
    'fournisseurs.php',
    'archive_dashboard.php',
    'archive_monthly.php',
    'assets/css/simple-theme.css',
    'includes/header_simple.php',
    'quick_deploy.php',
    'auto_deploy_ftp_enhanced.php'
];

echo "<h3>📁 التحقق من الملفات المحدثة:</h3>";

foreach ($important_files as $file) {
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

echo "<h3>🔧 حلول مقترحة:</h3>";

echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h4>💡 إذا لم تظهر التحديثات:</h4>";
echo "<ol>";
echo "<li><strong>امسح الكاش:</strong> اضغط Ctrl+F5 أو Ctrl+Shift+R</li>";
echo "<li><strong>امسح كاش الخادم:</strong> انتظر 5-10 دقائق</li>";
echo "<li><strong>تحقق من الملفات:</strong> تأكد من رفع جميع الملفات</li>";
echo "<li><strong>أعد تشغيل التحديث:</strong> شغل complete_update.php</li>";
echo "<li><strong>تحقق من الصلاحيات:</strong> تأكد من صلاحيات القراءة للملفات</li>";
echo "</ol>";
echo "</div>";

echo "<div style='text-align: center; margin: 20px 0;'>";
echo "<a href='complete_update.php' class='btn' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px;'>🔄 تشغيل التحديث الشامل</a>";
echo "<a href='javascript:location.reload()' class='btn' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px;'>🔄 إعادة تحميل الصفحة</a>";
echo "</div>";

echo "<h3>📊 معلومات النظام:</h3>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>مسار المشروع:</strong> " . __DIR__ . "</p>";
echo "<p><strong>الوقت الحالي:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>إصدار PHP:</strong> " . phpversion() . "</p>";
echo "<p><strong>إعدادات الخادم:</strong> " . $_SERVER['SERVER_SOFTWARE'] ?? 'غير معروف' . "</p>";
echo "</div>";

echo "<h3>🔍 فحص الأخطاء الشائعة:</h3>";

// فحص ملف buses_complete.php
$buses_file = __DIR__ . '/buses_complete.php';
if (file_exists($buses_file)) {
    $content = file_get_contents($buses_file);
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>📄 تحليل buses_complete.php:</h4>";
    
    // التحقق من وجود التيم البسيط
    if (strpos($content, 'simple-theme.css') !== false) {
        echo "✅ يحتوي على simple-theme.css<br>";
    } else {
        echo "❌ لا يحتوي على simple-theme.css<br>";
    }
    
    // التحقق من وجود header_simple
    if (strpos($content, 'header_simple.php') !== false) {
        echo "✅ يحتوي على header_simple.php<br>";
    } else {
        echo "❌ لا يحتوي على header_simple.php<br>";
    }
    
    // التحقق من التصميم المبسط
    if (strpos($content, 'font-family: Arial') !== false) {
        echo "✅ يستخدم تصميم مبسط<br>";
    } else {
        echo "❌ لا يستخدم تصميم مبسط<br>";
    }
    
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h4>❌ ملف buses_complete.php غير موجود!</h4>";
    echo "</div>";
}

echo "<h3>🚀 خطوات الحل الفوري:</h3>";
echo "<div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;'>";
echo "<h4>📋 الخطة المقترحة:</h4>";
echo "<ol style='text-align: left; max-width: 600px; margin: 0 auto;'>";
echo "<li>1. اضغط على 'تشغيل التحديث الشامل' أدناه</li>";
echo "<li>2. انتظر حتى يكتمل التحديث</li>";
echo "<li>3. امسح الكاش (Ctrl+F5)</li>";
echo "<li>4. تحقق من صفحة buses_complete.php</li>";
echo "<li>5. إذا لم ينجح، أعد رفع الملفات مرة أخرى</li>";
echo "</ol>";
echo "</div>";
?>
