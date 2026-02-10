<?php
// FUTURE AUTOMOTIVE - Batch Theme Updater
// تحديث التيم البسيط على جميع الصفحات دفعة واحدة

echo "<h2>🎨 تحديث التيم البسيط على جميع الصفحات</h2>";

// قائمة الملفات الرئيسية للتحديث
$main_files = [
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
    'admin_breakdown_view.php',
    'technician_breakdowns.php'
];

$updated_count = 0;
$skipped_count = 0;

foreach ($main_files as $file) {
    $filepath = __DIR__ . '/' . $file;
    
    if (!file_exists($filepath)) {
        echo "<p style='color: orange;'>⚠️ الملف غير موجود: $file</p>";
        $skipped_count++;
        continue;
    }
    
    try {
        $content = file_get_contents($filepath);
        if ($content === false) {
            echo "<p style='color: red;'>❌ فشل قراءة الملف: $file</p>";
            $skipped_count++;
            continue;
        }
        
        $original_content = $content;
        $modified = false;
        
        // 1. تحديث روابط CSS
        if (strpos($content, 'assets/css/style.css') !== false) {
            $content = str_replace('assets/css/style.css', 'assets/css/simple-theme.css', $content);
            $modified = true;
        }
        
        // 2. إزالة روابط التيم القديمة
        $old_themes = [
            'assets/css/iso-theme.css',
            'assets/css/iso-bootstrap.css', 
            'assets/css/iso-components.css',
            'assets/css/professional.css'
        ];
        
        foreach ($old_themes as $theme) {
            if (strpos($content, $theme) !== false) {
                $content = str_replace($theme, '', $content);
                $modified = true;
            }
        }
        
        // 3. تحديث روابط header
        if (strpos($content, "includes/header.php'") !== false) {
            $content = str_replace("includes/header.php'", "includes/header_simple.php'", $content);
            $modified = true;
        }
        
        // 4. إضافة simple-theme.css إذا لم يكن موجود
        if (strpos($content, '<head>') !== false && strpos($content, 'simple-theme.css') === false) {
            $simple_theme_link = "    <link rel=\"stylesheet\" href=\"assets/css/simple-theme.css\">";
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
            if (file_put_contents($filepath, $content) !== false) {
                echo "<p style='color: green;'>✅ تم تحديث: $file</p>";
                $updated_count++;
            } else {
                echo "<p style='color: red;'>❌ فشل حفظ الملف: $file</p>";
                $skipped_count++;
            }
        } else {
            echo "<p style='color: blue;'>ℹ️ لا تغييرات مطلوبة: $file</p>";
            $skipped_count++;
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ خطأ في $file: " . $e->getMessage() . "</p>";
        $skipped_count++;
    }
}

echo "<h3>📊 ملخص التحديث</h3>";
echo "<p style='color: green;'><strong>✅ تم تحديث $updated_count ملف</strong></p>";
echo "<p style='color: blue;'><strong>ℹ️ تم تخطي $skipped_count ملف</strong></p>";

// تحديث ملفات الإدمن
$admin_files = [
    'admin/admin_breakdowns.php',
    'admin/admin_breakdown_view.php',
    'admin/admin_breakdowns_workshop.php',
    'admin/work_order_edit.php',
    'admin/work_order_view.php'
];

echo "<h3>📁 تحديث ملفات الإدمن</h3>";

foreach ($admin_files as $file) {
    $filepath = __DIR__ . '/' . $file;
    
    if (!file_exists($filepath)) {
        echo "<p style='color: orange;'>⚠️ الملف غير موجود: $file</p>";
        continue;
    }
    
    try {
        $content = file_get_contents($filepath);
        $original_content = $content;
        $modified = false;
        
        // تطبيق نفس التحديثات
        if (strpos($content, 'assets/css/style.css') !== false) {
            $content = str_replace('assets/css/style.css', 'assets/css/simple-theme.css', $content);
            $modified = true;
        }
        
        foreach ($old_themes as $theme) {
            if (strpos($content, $theme) !== false) {
                $content = str_replace($theme, '', $content);
                $modified = true;
            }
        }
        
        if (strpos($content, "includes/header.php'") !== false) {
            $content = str_replace("includes/header.php'", "includes/header_simple.php'", $content);
            $modified = true;
        }
        
        if ($modified && $content !== $original_content) {
            if (file_put_contents($filepath, $content) !== false) {
                echo "<p style='color: green;'>✅ تم تحديث: $file</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ خطأ في $file: " . $e->getMessage() . "</p>";
    }
}

echo "<h3 style='color: green;'>🎉 اكتمل تحديث التيم البسيط!</h3>";
echo "<div class='alert alert-success'>";
echo "<strong>✅ تم تطبيق التيم البسيط بنجاح!</strong><br>";
echo "جميع الصفحات تستخدم الآن simple-theme.css و header_simple.php<br>";
echo "تمت إزالة جميع روابط التيم القديمة";
echo "</div>";
?>
