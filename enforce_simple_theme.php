<?php
// FUTURE AUTOMOTIVE - Simple Theme Enforcer
// فرض التيم البسيط على جميع الصفحات تلقائياً

// قائمة الملفات التي يجب تحديثها
$files_to_update = [
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
    'purchase_performance.php'
];

$updated = 0;

foreach ($files_to_update as $file) {
    $filepath = __DIR__ . '/' . $file;
    
    if (!file_exists($filepath)) continue;
    
    $content = file_get_contents($filepath);
    $original = $content;
    
    // استبدال روابط CSS القديمة بالتيم البسيط
    $content = preg_replace('/assets\/css\/[^"\']+\.(css)/', 'assets/css/simple-theme.css', $content);
    
    // استبدال header القديم بالبسيط
    $content = str_replace("includes/header.php'", "includes/header_simple.php'", $content);
    
    // إزالة روابط التيم القديمة
    $content = preg_replace('/<link[^>]*iso-[^>]*>/', '', $content);
    $content = preg_replace('/<link[^>]*professional[^>]*>/', '', $content);
    
    if ($content !== $original) {
        file_put_contents($filepath, $content);
        $updated++;
    }
}

echo "Updated $updated files with simple theme";
?>
