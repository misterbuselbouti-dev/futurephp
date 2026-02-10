<?php
// FUTURE AUTOMOTIVE - Auto FTP Deployment to Hostinger
// نظام النشر التلقائي عبر FTP

// إعدادات FTP Hostinger
$ftp_config = [
    'host' => 'ftp.your-domain.com', // غير هذا إلى عنوان FTP الخاص بك
    'username' => 'your_username',   // غير هذا إلى اسم المستخدم
    'password' => 'your_password',   // غير هذا إلى كلمة المرور
    'port' => 21,
    'timeout' => 30,
    'local_path' => __DIR__,
    'remote_path' => '/public_html/futureautomotive'
];

echo "<h2>🚀 نظام النشر التلقائي إلى Hostinger</h2>";

// التحقق من وجود إعدادات FTP
if ($ftp_config['host'] === 'ftp.your-domain.com') {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>⚙️ إعداد FTP</h3>";
    echo "<p>يجب تحديث إعدادات FTP في الملف أولاً:</p>";
    echo "<ul>";
    echo "<li><strong>Host:</strong> عنوان FTP الخاص بـ Hostinger</li>";
    echo "<li><strong>Username:</strong> اسم مستخدم FTP</li>";
    echo "<li><strong>Password:</strong> كلمة مرور FTP</li>";
    echo "</ul>";
    echo "<p>يمكنك العثور على هذه المعلومات في لوحة تحكم Hostinger → FTP Accounts</p>";
    echo "</div>";
    exit;
}

// قائمة الملفات التي يجب نشرها
$files_to_deploy = [
    // ملفات PHP الرئيسية
    'index.php',
    'dashboard.php',
    'buses_complete.php',
    'drivers.php',
    'articles_stockables.php',
    'stock_tetouan.php',
    'stock_ksar.php',
    'export_data.php',
    'users_management.php',
    'notifications.php',
    'fournisseurs.php',
    'achat_da.php',
    'achat_dp.php',
    'achat_bc.php',
    'achat_be.php',
    'work_orders.php',
    'employees.php',
    'garage_workers.php',
    'archive_dashboard.php',
    'archive_monthly.php',
    'purchase_performance.php',
    'admin_breakdowns.php',
    
    // ملفات التيم
    'assets/css/simple-theme.css',
    'includes/header_simple.php',
    
    // ملفات التحديث
    'batch_theme_update.php',
    'enforce_simple_theme.php',
    'apply_simple_theme_universal.php',
    'complete_update.php',
    'add_supplier_ice_rc.php'
];

echo "<h3>📁 الملفات المحددة للنشر: " . count($files_to_deploy) . " ملف</h3>";

// الاتصال بـ FTP
$connection = ftp_connect($ftp_config['host'], $ftp_config['port'], $ftp_config['timeout']);
if (!$connection) {
    die("<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>❌ فشل الاتصال بـ FTP</div>");
}

$login = ftp_login($connection, $ftp_config['username'], $ftp_config['password']);
if (!$login) {
    die("<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>❌ فشل تسجيل الدخول إلى FTP</div>");
}

echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>✅ تم الاتصال بـ FTP بنجاح</div>";

// تغيير إلى المجلد البعيد
if (!ftp_chdir($connection, $ftp_config['remote_path'])) {
    die("<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>❌ فشل الوصول إلى المجلد البعيد: {$ftp_config['remote_path']}</div>");
}

echo "<div style='background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px;'>📁 المجلد البعيد: {$ftp_config['remote_path']}</div>";

$uploaded = 0;
$failed = 0;

echo "<h3>📤 بدء رفع الملفات...</h3>";

foreach ($files_to_deploy as $file) {
    $local_file = $ftp_config['local_path'] . '/' . $file;
    
    if (!file_exists($local_file)) {
        echo "<div style='color: #856404; background: #fff3cd; padding: 5px; margin: 2px 0; border-radius: 3px;'>⚠️ الملف غير موجود: $file</div>";
        $failed++;
        continue;
    }
    
    // إنشاء المجلد البعيد إذا لم يكن موجوداً
    $remote_dir = dirname($ftp_config['remote_path'] . '/' . $file);
    $dirs = explode('/', $remote_dir);
    $current_path = '';
    
    foreach ($dirs as $dir) {
        if ($dir === '') continue;
        $current_path .= '/' . $dir;
        @ftp_mkdir($connection, $current_path);
    }
    
    // رفع الملف
    if (ftp_put($connection, $file, $local_file, FTP_ASCII)) {
        echo "<div style='color: #155724; background: #d4edda; padding: 5px; margin: 2px 0; border-radius: 3px;'>✅ تم رفع: $file</div>";
        $uploaded++;
    } else {
        echo "<div style='color: #721c24; background: #f8d7da; padding: 5px; margin: 2px 0; border-radius: 3px;'>❌ فشل رفع: $file</div>";
        $failed++;
    }
}

ftp_close($connection);

echo "<h3>📊 ملخص النشر</h3>";
echo "<div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;'>";
echo "<h2>🎉 اكتمل النشر!</h2>";
echo "<p>✅ تم رفع $uploaded ملف بنجاح</p>";
if ($failed > 0) {
    echo "<p>❌ فشل رفع $failed ملف</p>";
}
echo "<p>📁 المجلد الهدف: {$ftp_config['remote_path']}</p>";
echo "</div>";

echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>🔗 الخطوات التالية</h3>";
echo "<ol>";
echo "<li>انتظر بضع دقائق حتى يتم تحديث الخادم</li>";
echo "<li>افتح موقعك الإلكتروني</li>";
echo "<li>تحقق من التغييرات</li>";
echo "<li>إذا لزم الأمر، امسح الكاش (Ctrl+F5)</li>";
echo "</ol>";
echo "</div>";

echo "<div style='text-align: center; margin: 20px 0;'>";
echo "<a href='complete_update.php' class='btn' style='background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px;'>🔄 تشغيل التحديث</a>";
echo "<a href='javascript:history.back()' class='btn' style='background: #6c757d; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px;'>🔙 رجوع</a>";
echo "</div>";
?>
