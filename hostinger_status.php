<?php
// FUTURE AUTOMOTIVE - Hostinger Deployment Status
// فحص حالة الموقع على Hostinger

echo "<h1>🌐 فحص حالة الموقع على Hostinger</h1>";

// معلومات الموقع من config.php
echo "<h2>📊 معلومات الموقع:</h2>";
echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
echo "<tr><th>الخاصية</th><th>القيمة</th><th>الحالة</th></tr>";

$site_info = [
    'APP_NAME' => 'FUTURE AUTOMOTIVE',
    'APP_URL' => 'https://www.futureautomotive.net',
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'u442210176_Futur2',
    'DB_USER' => 'u442210176_Futur2',
    'APP_VERSION' => '1.0.0'
];

foreach ($site_info as $key => $value) {
    echo "<tr>";
    echo "<td><code>$key</code></td>";
    echo "<td>$value</td>";
    echo "<td>✅ معرف</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>🔍 فحص الاتصال بالموقع:</h2>";

// فحص رابط الموقع
$site_url = 'https://www.futureautomotive.net';
echo "<h3>🌐 الاتصال بالموقع:</h3>";

// استخدام curl للفحص
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $site_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($response !== false) {
        echo "<p style='color: green;'>✅ الموقع متاح على الرابط: $site_url</p>";
        echo "<p>📊 رمز الحالة: HTTP $http_code</p>";
        
        if ($http_code == 200) {
            echo "<p style='color: green;'>🎉 الموقع يعمل بشكل صحيح!</p>";
        } elseif ($http_code == 404) {
            echo "<p style='color: orange;'>⚠️ الصفحة الرئيسية غير موجودة (404)</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ الموقع يستجيب بحالة: $http_code</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ خطأ في الاتصال: $error</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ curl غير متاح</p>";
}

echo "<h2>📁 فحص الملفات المحدثة:</h2>";

// الملفات التي تم تحديثها مؤخراً
$updated_files = [
    'login.php' => 'صفحة تسجيل الدخول بثيم ISO',
    'dashboard.php' => 'لوحة التحكم بثيم ISO',
    'buses.php' => 'إدارة الأسطول بثيم ISO',
    'achat_bc.php' => 'مشتريات BC بثيم ISO',
    'achat_da.php' => 'مشتريات DA بثيم ISO',
    'admin/audit.php' => 'التدقيق بثيم ISO',
    'audit_report.php' => 'تقارير التدقيق بثيم ISO',
    'audit_system.php' => 'نظام التدقيق بثيم ISO'
];

echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
echo "<tr><th>الملف</th><th>الوصف</th><th>الحالة</th></tr>";

foreach ($updated_files as $file => $description) {
    if (file_exists($file)) {
        $modified = date('Y-m-d H:i:s', filemtime($file));
        echo "<tr style='background-color: #d4edda;'>";
        echo "<td>$file</td>";
        echo "<td>$description</td>";
        echo "<td>✅ موجود (آخر تعديل: $modified)</td>";
        echo "</tr>";
    } else {
        echo "<tr style='background-color: #f8d7da;'>";
        echo "<td>$file</td>";
        echo "<td>$description</td>";
        echo "<td>❌ غير موجود</td>";
        echo "</tr>";
    }
}
echo "</table>";

echo "<h2>🎨 فحص ملفات CSS:</h2>";

$css_files = [
    'assets/css/iso-theme.css' => 'ثيم ISO الرئيسي',
    'assets/css/iso-components.css' => 'مكونات ISO',
    'assets/css/iso-bootstrap.css' => 'ISO Bootstrap'
];

echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
echo "<tr><th>ملف CSS</th><th>الوصف</th><th>الحالة</th></tr>";

foreach ($css_files as $file => $description) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "<tr style='background-color: #d4edda;'>";
        echo "<td>$file</td>";
        echo "<td>$description</td>";
        echo "<td>✅ موجود ($size bytes)</td>";
        echo "</tr>";
    } else {
        echo "<tr style='background-color: #f8d7da;'>";
        echo "<td>$file</td>";
        echo "<td>$description</td>";
        echo "<td>❌ غير موجود</td>";
        echo "</tr>";
    }
}
echo "</table>";

echo "<h2>🔄 حالة المزامنة مع GitHub:</h2>";

// فحص حالة Git
if (function_exists('shell_exec')) {
    $git_status = shell_exec('git status --porcelain 2>&1');
    $git_log = shell_exec('git log --oneline -3 2>&1');
    
    if ($git_status !== null) {
        echo "<h3>📊 حالة Git:</h3>";
        echo "<pre style='background-color: #f8f9fa; padding: 10px; border-radius: 5px;'>";
        echo htmlspecialchars($git_status);
        echo "</pre>";
        
        echo "<h3>📝 آخر التغييرات:</h3>";
        echo "<pre style='background-color: #f8f9fa; padding: 10px; border-radius: 5px;'>";
        echo htmlspecialchars($git_log);
        echo "</pre>";
    }
}

echo "<h2>🚀 خطوات التحقق النهائية:</h2>";
echo "<div style='background-color: #e7f3ff; padding: 15px; border-radius: 5px; border-left: 4px solid #2196F3;'>";
echo "<h3>✅ قائمة التحقق:</h3>";
echo "<ol>";
echo "<li><strong>فحص الموقع:</strong> <a href='$site_url' target='_blank'>$site_url</a></li>";
echo "<li><strong>فحص تسجيل الدخول:</strong> <a href='$site_url/login.php' target='_blank'>$site_url/login.php</a></li>";
echo "<li><strong>فحص لوحة التحكم:</strong> <a href='$site_url/dashboard.php' target='_blank'>$site_url/dashboard.php</a></li>";
echo "<li><strong>فحص إدارة الأسطول:</strong> <a href='$site_url/buses.php' target='_blank'>$site_url/buses.php</a></li>";
echo "<li><strong>فحص المشتريات:</strong> <a href='$site_url/achat_da.php' target='_blank'>$site_url/achat_da.php</a></li>";
echo "<li><strong>فحص التدقيق:</strong> <a href='$site_url/admin/audit.php' target='_blank'>$site_url/admin/audit.php</a></li>";
echo "</ol>";
echo "</div>";

echo "<h2>🎯 التوصيات:</h2>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>";
echo "<h3>💡 ما يجب التحقق منه:</h3>";
echo "<ul>";
echo "<li>🔄 <strong>مزامنة الملفات:</strong> تأكد من رفع آخر التغييرات إلى Hostinger</li>";
echo "<li>🎨 <strong>الألوان:</strong> تحقق من ظهور ثيم ISO 9001 بشكل صحيح</li>";
echo "<li>📱 <strong>الاستجابة:</strong> تأكد من عمل الموقع على جميع الأجهزة</li>";
echo "<li>🔐 <strong>تسجيل الدخول:</strong> تأكد من عمل نظام المصادقة</li>";
echo "<li>📊 <strong>قاعدة البيانات:</strong> تأكد من الاتصال بقاعدة البيانات</li>";
echo "</ul>";
echo "</div>";

echo "<h2>📞 الدعم الفني:</h2>";
echo "<p>إذا واجهت أي مشاكل:</p>";
echo "<ul>";
echo "<li>🔄 <strong>مزامنة الملفات:</strong> استخدم FTP أو File Manager في Hostinger</li>";
echo "<li>🗑️ <strong>مسح التخزين المؤقت:</strong> امسح ذاكرة التخزين المؤقت للمتصفح</li>";
echo "<li>🔍 <strong>فحص الأخطاء:</strong> تحقق من سجلات الأخطاء في Hostinger</li>";
echo "<li>📧 <strong>التواصل:</strong> تواصل مع دعم Hostinger إذا لزم الأمر</li>";
echo "</ul>";

echo "<hr>";
echo "<p style='text-align: center; color: #666;'><strong>FUTURE AUTOMOTIVE - Hostinger Deployment Status</strong></p>";
?>
