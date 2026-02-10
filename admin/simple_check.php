<?php
// FUTURE AUTOMOTIVE - Simple Check Only
// فحص بسيط فقط

echo "<h2>🔍 فحص بسيط لجداول الورشة</h2>";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Basic database connection test
try {
    // Database configuration - adjust these values
    $host = 'localhost';
    $dbname = 'u442210176_Futur2';
    $username = 'u442210176_Futur2';
    $password = ''; // Change this
    
    echo "<h3>1. اختبار الاتصال بقاعدة البيانات</h3>";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div style='color: green;'>✅ الاتصال بقاعدة البيانات نجح</div>";
    
} catch (PDOException $e) {
    echo "<div style='color: red;'>❌ خطأ في الاتصال: " . $e->getMessage() . "</div>";
    echo "<h3>الحلول:</h3>";
    echo "<ol>";
    echo "<li>تأكد من اسم قاعدة البيانات: $dbname</li>";
    echo "<li>تأكد من اسم المستخدم: $username</li>";
    echo "<li>تأكد من كلمة المرور</li>";
    echo "<li>تأكد من أن قاعدة البيانات موجودة</li>";
    echo "</ol>";
    exit;
}

// Check existing tables
echo "<h3>2. فحص الجداول الموجودة</h3>";

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div>عدد الجداول الإجمالي: " . count($tables) . "</div>";
    
    $workshop_tables = ['work_orders', 'work_order_parts', 'work_order_timeline'];
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>الجدول المطلوب</th><th>الحالة</th></tr>";
    
    foreach ($workshop_tables as $table) {
        if (in_array($table, $tables)) {
            echo "<tr><td>$table</td><td style='color: green;'>✅ موجود</td></tr>";
            
            // Show record count
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $count = $stmt->fetch()['count'];
                echo "<tr><td colspan='2'>عدد السجلات: $count</td></tr>";
            } catch (Exception $e) {
                echo "<tr><td colspan='2' style='color: orange;'>خطأ في عد السجلات</td></tr>";
            }
        } else {
            echo "<tr><td>$table</td><td style='color: red;'>❌ غير موجود</td></tr>";
        }
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ خطأ في فحص الجداول: " . $e->getMessage() . "</div>";
}

// Test the problematic query
echo "<h3>3. اختبار الاستعلام الرئيسي</h3>";

try {
    $stmt = $pdo->query("
        SELECT wo.*, 
               b.bus_number, b.license_plate,
               u.full_name as technician_name
        FROM work_orders wo
        LEFT JOIN buses b ON wo.bus_id = b.id
        LEFT JOIN users u ON wo.technician_id = u.id
        LIMIT 3
    ");
    $results = $stmt->fetchAll();
    
    echo "<div style='color: green;'>✅ الاستعلام يعمل!</div>";
    echo "<div>عدد النتائج: " . count($results) . "</div>";
    
    if (!empty($results)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>المرجع</th><th>الحافلة</th><th>التقني</th></tr>";
        
        foreach ($results as $row) {
            echo "<tr>";
            echo "<td>" . ($row['ref_ot'] ?? 'N/A') . "</td>";
            echo "<td>" . ($row['bus_number'] ?? 'N/A') . "</td>";
            echo "<td>" . ($row['technician_name'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ خطأ في الاستعلام: " . $e->getMessage() . "</div>";
    
    if (strpos($e->getMessage(), 'Column') !== false) {
        echo "<h3>المشكلة:</h3>";
        echo "<p>الجدول work_orders غير موجود أو به أعمدة ناقصة</p>";
        echo "<p><a href='create_workshop_step_by_step.php'>اضغط هنا لإنشاء الجداول</a></p>";
    }
}

echo "<hr>";
echo "<h3>روابط مفيدة:</h3>";
echo "<ul>";
echo "<li><a href='create_workshop_step_by_step.php'>إنشاء الجداول خطوة بخطوة</a></li>";
echo "<li><a href='check_workshop_fixed.php'>فحص وإنشاء متقدم</a></li>";
echo "<li><a href='admin_breakdowns_workshop.php'>إدارة الورشة</a></li>";
echo "</ul>";
?>
