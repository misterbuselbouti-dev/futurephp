<?php
// FUTURE AUTOMOTIVE - Enhanced Auto FTP Deployment
// نظام النشر التلقائي المحسن مع إعدادات محفوظة

echo "<h2>🚀 نظام النشر التلقائي المحسن</h2>";

// التحقق من وجود ملف الإعدادات
$config_file = __DIR__ . '/ftp_config.php';
if (!file_exists($config_file)) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>⚙️ إعداد FTP مطلوب</h3>";
    echo "<p>يجب إعداد FTP أولاً:</p>";
    echo "<a href='ftp_setup.php' class='btn' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>⚙️ إعداد FTP</a>";
    echo "</div>";
    exit;
}

// تحميل الإعدادات
$ftp_config = include $config_file;

echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>📋 معلومات FTP:</h3>";
echo "<p><strong>Host:</strong> " . htmlspecialchars($ftp_config['host']) . "</p>";
echo "<p><strong>Username:</strong> " . htmlspecialchars($ftp_config['username']) . "</p>";
echo "<p><strong>Port:</strong> " . $ftp_config['port'] . "</p>";
echo "<p><strong>Remote Path:</strong> " . htmlspecialchars($ftp_config['remote_path']) . "</p>";
echo "</div>";

// قائمة الملفات المحدثة (آخر 24 ساعة)
$files_to_deploy = [];
$local_path = __DIR__;

// فحص جميع ملفات PHP المحدثة
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($local_path));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $relative_path = str_replace($local_path . '/', '', $file->getPathname());
        
        // تخطي الملفات غير المهمة
        if (strpos($relative_path, 'vendor/') !== false || 
            strpos($relative_path, 'node_modules/') !== false ||
            strpos($relative_path, '.git/') !== false) {
            continue;
        }
        
        // إضافة الملفات الرئيسية فقط
        $important_files = [
            'index.php', 'dashboard.php', 'buses_complete.php', 'drivers.php',
            'articles_stockables.php', 'stock_tetouan.php', 'stock_ksar.php',
            'export_data.php', 'users_management.php', 'notifications.php',
            'fournisseurs.php', 'achat_da.php', 'achat_dp.php', 'achat_bc.php',
            'achat_be.php', 'work_orders.php', 'employees.php', 'garage_workers.php',
            'archive_dashboard.php', 'archive_monthly.php', 'purchase_performance.php',
            'admin_breakdowns.php', 'admin_breakdown_view.php', 'technician_breakdowns.php'
        ];
        
        if (in_array(basename($relative_path), $important_files) || 
            strpos($relative_path, 'includes/') === 0 || 
            strpos($relative_path, 'assets/css/') === 0) {
            $files_to_deploy[] = $relative_path;
        }
    }
}

echo "<h3>📁 الملفات المحددة للنشر: " . count($files_to_deploy) . " ملف</h3>";

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

// تفعيل الوضع السلبي
ftp_pasv($connection, true);

// تغيير إلى المجلد البعيد
if (!ftp_chdir($connection, $ftp_config['remote_path'])) {
    die("<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>❌ فشل الوصول إلى المجلد البعيد</div>");
}

$uploaded = 0;
$failed = 0;
$skipped = 0;

echo "<h3>📤 بدء رفع الملفات...</h3>";

foreach ($files_to_deploy as $file) {
    $local_file = $local_path . '/' . $file;
    
    if (!file_exists($local_file)) {
        echo "<div style='color: #856404; background: #fff3cd; padding: 5px; margin: 2px 0; border-radius: 3px;'>⚠️ الملف غير موجود: $file</div>";
        $skipped++;
        continue;
    }
    
    // التحقق من وجود الملف البعيد ومقارنة التاريخ
    $remote_file_info = ftp_mdtm($connection, $file);
    $local_file_time = filemtime($local_file);
    
    if ($remote_file_info && $remote_file_info >= $local_file_time) {
        echo "<div style='color: #6c757d; background: #e2e3e5; padding: 5px; margin: 2px 0; border-radius: 3px;'>⏭️ متجاوز (محديث): $file</div>";
        $skipped++;
        continue;
    }
    
    // إنشاء المجلد البعيد إذا لم يكن موجوداً
    $remote_dir = dirname($file);
    if ($remote_dir !== '.') {
        $dirs = explode('/', $remote_dir);
        $current_path = '';
        
        foreach ($dirs as $dir) {
            if ($dir === '') continue;
            $current_path .= '/' . $dir;
            @ftp_mkdir($connection, $current_path);
        }
    }
    
    // رفع الملف
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mode = ($extension === 'css' || $extension === 'js' || $extension === 'html') ? FTP_ASCII : FTP_BINARY;
    
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
echo "<p>✅ تم رفع $uploaded ملف جديد</p>";
echo "<p>⏭️ تم تجاوز $skipped ملف (محديثة بالفعل)</p>";
if ($failed > 0) {
    echo "<p>❌ فشل رفع $failed ملف</p>";
}
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
echo "<a href='ftp_setup.php' class='btn' style='background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px;'>⚙️ تعديل FTP</a>";
echo "<a href='javascript:location.reload()' class='btn' style='background: #6c757d; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px;'>🔄 إعادة النشر</a>";
echo "</div>";
?>
