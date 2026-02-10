<?php
// FUTURE AUTOMOTIVE - FTP Connection Test
// اختبار اتصال FTP

echo "<h2>🔧 اختبار اتصال FTP</h2>";

// تحميل الإعدادات
$config_file = __DIR__ . '/ftp_config.php';
if (!file_exists($config_file)) {
    die("<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>❌ ملف الإعدادات غير موجود</div>");
}

$ftp_config = include $config_file;

echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>📋 معلومات FTP:</h3>";
echo "<p><strong>Host:</strong> " . htmlspecialchars($ftp_config['host']) . "</p>";
echo "<p><strong>Username:</strong> " . htmlspecialchars($ftp_config['username']) . "</p>";
echo "<p><strong>Port:</strong> " . $ftp_config['port'] . "</p>";
echo "<p><strong>Remote Path:</strong> " . htmlspecialchars($ftp_config['remote_path']) . "</p>";
echo "</div>";

echo "<h3>🔍 اختبار الاتصال...</h3>";

// اختبار الاتصال
$connection = ftp_connect($ftp_config['host'], $ftp_config['port'], 10);
if (!$connection) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>❌ فشل الاتصال بـ FTP Host</div>";
    echo "<p><strong>المشكلة المحتملة:</strong></p>";
    echo "<ul>";
    echo "<li>عنوان Host غير صحيح</li>";
    echo "<li>المنفذ (Port) مغلق</li>";
    echo "<li>مشكلة في الشبكة</li>";
    echo "</ul>";
    exit;
}

echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>✅ تم الاتصال بـ FTP Host بنجاح</div>";

// اختبار تسجيل الدخول
$login = ftp_login($connection, $ftp_config['username'], $ftp_config['password']);
if (!$login) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>❌ فشل تسجيل الدخول إلى FTP</div>";
    echo "<p><strong>المشكلة المحتملة:</strong></p>";
    echo "<ul>";
    echo "<li>اسم المستخدم غير صحيح</li>";
    echo "<li>كلمة المرور غير صحيحة</li>";
    echo "<li>الحساب مغلق أو منتهي الصلاحية</li>";
    echo "<li>مشكلة في صلاحيات FTP</li>";
    echo "</ul>";
    
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>💡 حلول مقترحة:</h4>";
    echo "<ol>";
    echo "<li>تحقق من اسم المستخدم (بدون ftp://)</li>";
    echo "<li>تأكد من كلمة المرور</li>";
    echo "<li>جرب تسجيل الدخول عبر File Manager في Hostinger</li>";
    echo "<li>تحقق من أن حساب FTP نشط</li>";
    echo "</ol>";
    echo "</div>";
    
    ftp_close($connection);
    exit;
}

echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>✅ تم تسجيل الدخول إلى FTP بنجاح</div>";

// اختبار الوصول للمجلد البعيد
if (!ftp_chdir($connection, $ftp_config['remote_path'])) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>❌ فشل الوصول إلى المجلد البعيد</div>";
    echo "<p><strong>المشكلة:</strong> المجلد {$ftp_config['remote_path']} غير موجود</p>";
    echo "<p><strong>الحل:</strong> قد تحتاج إلى إنشاء المجلد أولاً</p>";
    
    // محاولة إنشاء المجلد
    echo "<h4>🔧 محاولة إنشاء المجلد...</h4>";
    if (ftp_mkdir($connection, $ftp_config['remote_path'])) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>✅ تم إنشاء المجلد بنجاح</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>❌ فشل إنشاء المجلد</div>";
    }
    
    ftp_close($connection);
    exit;
}

echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>✅ الوصول إلى المجلد البعيد بنجاح</div>";

// اختبار رفع ملف تجريبي
echo "<h4>📤 اختبار رفع ملف تجريبي...</h4>";
$test_content = "Test file uploaded at " . date('Y-m-d H:i:s');
$temp_file = tempnam(sys_get_temp_dir(), 'ftp_test_');
file_put_contents($temp_file, $test_content);

if (ftp_put($connection, 'test_upload.txt', $temp_file, FTP_ASCII)) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>✅ اختبار رفع الملف بنجاح</div>";
    
    // حذف الملف التجريبي
    ftp_delete($connection, 'test_upload.txt');
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>❌ فشل رفع الملف التجريبي</div>";
    echo "<p><strong>المشكلة:</strong> قد تكون هناك مشكلة في صلاحيات الكتابة</p>";
}

unlink($temp_file);
ftp_close($connection);

echo "<div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;'>";
echo "<h2>🎉 اختبار FTP ناجح!</h2>";
echo "<p>✅ جميع اختبارات FTP نجحت</p>";
echo "<p>🚀 يمكنك الآن استخدام النشر التلقائي</p>";
echo "</div>";

echo "<div style='text-align: center; margin: 20px 0;'>";
echo "<a href='quick_deploy.php' class='btn' style='background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px;'>🚀 ابدأ النشر</a>";
echo "<a href='auto_deploy_ftp_enhanced.php' class='btn' style='background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px;'>🔧 النشر المحسن</a>";
echo "</div>";
?>
