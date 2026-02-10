<?php
// FUTURE AUTOMOTIVE - Quick FTP Deploy
// نشر سريع عبر FTP

echo "<h2>🚀 النشر السريع عبر FTP</h2>";

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
echo "<p><strong>Remote Path:</strong> " . htmlspecialchars($ftp_config['remote_path']) . "</p>";
echo "</div>";

// الملفات الرئيسية للنشر
$files_to_deploy = [
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
    'assets/css/simple-theme.css',
    'includes/header_simple.php',
    'complete_update.php',
    'add_supplier_ice_rc.php',
    'auto_deploy_ftp_enhanced.php'
];

echo "<h3>📁 سيتم نشر " . count($files_to_deploy) . " ملف</h3>";

// الاتصال بـ FTP
$connection = ftp_connect($ftp_config['host'], $ftp_config['port'], 30);
if (!$connection) {
    die("<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>❌ فشل الاتصال بـ FTP</div>");
}

$login = ftp_login($connection, $ftp_config['username'], $ftp_config['password']);
if (!$login) {
    die("<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>❌ فشل تسجيل الدخول إلى FTP</div>");
}

echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>✅ تم الاتصال بـ FTP بنجاح</div>";

ftp_pasv($connection, true);

// تغيير إلى المجلد البعيد
if (!ftp_chdir($connection, $ftp_config['remote_path'])) {
    die("<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>❌ فشل الوصول إلى المجلد البعيد</div>");
}

$uploaded = 0;
$failed = 0;

echo "<h3>📤 بدء رفع الملفات...</h3>";

foreach ($files_to_deploy as $file) {
    $local_file = __DIR__ . '/' . $file;
    
    if (!file_exists($local_file)) {
        echo "<div style='color: #856404; background: #fff3cd; padding: 5px; margin: 2px 0; border-radius: 3px;'>⚠️ الملف غير موجود: $file</div>";
        $failed++;
        continue;
    }
    
    // رفع الملف
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mode = ($extension === 'css' || $extension === 'js') ? FTP_ASCII : FTP_BINARY;
    
    if (ftp_put($connection, $file, $local_file, $mode)) {
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
echo "</div>";

echo "<div style='text-align: center; margin: 20px 0;'>";
echo "<a href='complete_update.php' class='btn' style='background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px;'>🔄 تشغيل التحديث</a>";
echo "<a href='javascript:location.reload()' class='btn' style='background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px;'>🔄 إعادة النشر</a>";
echo "</div>";
?>
