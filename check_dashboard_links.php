<?php
// FUTURE AUTOMOTIVE - Dashboard Links Checker
// فاحص روابط لوحة التحكم

require_once 'config.php';
require_once 'includes/functions.php';

// Check authentication
require_login();

echo "<h2>🔍 فاحص روابط لوحة التحكم</h2>";

// Define all dashboard buttons and their targets
$dashboard_links = [
    'header' => [
        'Audit' => 'site_audit.php',
        'Nettoyer' => 'remove_unnecessary_files.php',
        'Nouveau' => 'admin/admin_breakdowns_workshop.php'
    ],
    'quick_actions' => [
        'Nouvelle DA' => 'achat_da.php',
        'Performance Achats' => 'purchase_performance.php',
        'Ordre Travail' => 'admin/admin_breakdowns_workshop.php',
        'Inventaire' => 'articles_stockables.php',
        'Ajouter Bus' => 'buses_complete.php',
        'Fournisseurs' => 'fournisseurs.php',
        'Archives' => 'archive_dashboard.php',
        'Nettoyage' => 'site_cleanup.php'
    ]
];

echo "<h3>📋 فحص الروابط:</h3>";

$working_links = 0;
$total_links = 0;

foreach ($dashboard_links as $section => $links) {
    echo "<h4>$section:</h4>";
    echo "<div class='row'>";
    
    foreach ($links as $name => $target) {
        $total_links++;
        $filepath = __DIR__ . '/' . $target;
        $exists = file_exists($filepath);
        
        if ($exists) {
            $working_links++;
            echo "<div class='col-md-3 mb-2'>";
            echo "<div class='alert alert-success' style='padding: 10px; margin: 5px;'>";
            echo "<strong>✅ $name</strong><br>";
            echo "<small>$target</small>";
            echo "</div>";
            echo "</div>";
        } else {
            echo "<div class='col-md-3 mb-2'>";
            echo "<div class='alert alert-danger' style='padding: 10px; margin: 5px;'>";
            echo "<strong>❌ $name</strong><br>";
            echo "<small>$target (غير موجود)</small>";
            echo "</div>";
            echo "</div>";
        }
    }
    
    echo "</div>";
}

echo "<div class='alert alert-info'>";
echo "<h5>📊 ملخص:</h5>";
echo "<p>الروابط العاملة: $working_links / $total_links</p>";
echo "<p>النسبة: " . round(($working_links / $total_links) * 100, 1) . "%</p>";
echo "</div>";

echo "<h3>🔧 إصلاح الروابط المعطلة:</h3>";

foreach ($dashboard_links as $section => $links) {
    foreach ($links as $name => $target) {
        $filepath = __DIR__ . '/' . $target;
        if (!file_exists($filepath)) {
            echo "<div class='alert alert-warning'>";
            echo "<strong>$name - $target</strong><br>";
            echo "<em>الملف غير موجود. قد تحتاج إلى:</em><br>";
            echo "1. التحقق من اسم الملف الصحيح<br>";
            echo "2. إنشاء الملف إذا كان مفقوداً<br>";
            echo "3. تحديث الرابط في dashboard_simple.php";
            echo "</div>";
        }
    }
}

echo "<h3>📁 الملفات المتاحة:</h3>";

$available_files = glob('*.php');
echo "<div class='row'>";
foreach ($available_files as $file) {
    echo "<div class='col-md-3 mb-2'>";
    echo "<div class='alert alert-info' style='padding: 8px; margin: 3px;'>";
    echo "<small>$file</small>";
    echo "</div>";
    echo "</div>";
}
echo "</div>";

echo "<div class='mt-3'>";
echo "<a href='dashboard_simple.php' class='btn btn-primary'>🔙 رجوع إلى لوحة التحكم</a>";
echo "</div>";
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاحص روابط لوحة التحكم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/simple-theme.css">
</head>
<body>
    <?php include 'includes/header_simple.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="mb-4">
                <h1><i class="fas fa-link me-2"></i>فاحص روابط لوحة التحكم</h1>
                <p class="text-muted">فحص جميع الروابط في لوحة التحكم للتأكد من عملها</p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
