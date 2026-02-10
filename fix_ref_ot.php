<?php
// FUTURE AUTOMOTIVE - Fix ref_ot column in work_orders table
// إصلاح عمود المرجع في جدول أوامر العمل

require_once 'config.php';
require_once 'includes/functions.php';

// Check authentication
require_login();

echo "<h2>🔧 إصلاح عمود ref_ot في جدول work_orders</h2>";

try {
    $database = new Database();
    $pdo = $database->connect();
    
    echo "<h3>📋 التحقق من الجدول:</h3>";
    
    // Check if work_orders table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'work_orders'");
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        echo "<p style='color: red;'>❌ جدول work_orders غير موجود</p>";
        echo "<p><a href='check_workshop_tables.php'>اضغط هنا لإنشاء الجداول أولاً</a></p>";
        exit();
    }
    
    echo "<p style='color: green;'>✅ جدول work_orders موجود</p>";
    
    // Check if ref_ot column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM work_orders LIKE 'ref_ot'");
    $column_exists = $stmt->rowCount() > 0;
    
    if ($column_exists) {
        echo "<p style='color: blue;'>ℹ️ عمود ref_ot موجود بالفعل</p>";
        
        // Check if it's a generated column
        $stmt = $pdo->query("SHOW COLUMNS FROM work_orders WHERE Field = 'ref_ot'");
        $column_info = $stmt->fetch();
        
        if (strpos($column_info['Extra'], 'GENERATED') !== false) {
            echo "<p style='color: green;'>✅ عمود ref_ot هو عمود مُولد (Generated Column)</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ عمود ref_ot هو عمود عادي</p>";
            echo "<p>سيتم تحديث البيانات الموجودة...</p>";
            
            // Update existing records
            $stmt = $pdo->exec("
                UPDATE work_orders 
                SET ref_ot = CONCAT('OT-', YEAR(created_at), '-', LPAD(id, 4, '0')) 
                WHERE ref_ot IS NULL OR ref_ot = ''
            ");
            echo "<p style='color: green;'>✅ تم تحديث " . $stmt . " سجلات</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ عمود ref_ot غير موجود - يتم إضافته...</p>";
        
        // Try to add as generated column first (MySQL 5.7+)
        try {
            $pdo->exec("
                ALTER TABLE work_orders 
                ADD COLUMN ref_ot VARCHAR(20) GENERATED ALWAYS AS (
                    CONCAT('OT-', YEAR(created_at), '-', LPAD(id, 4, '0'))
                ) STORED
            ");
            echo "<p style='color: green;'>✅ تم إضافة عمود ref_ot كعمود مُولد</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ لا يمكن إضافة عمود مُولد - يتم استخدام الطريقة البديلة...</p>";
            
            // Add regular column
            $pdo->exec("ALTER TABLE work_orders ADD COLUMN ref_ot VARCHAR(20)");
            echo "<p style='color: green;'>✅ تم إضافة عمود ref_ot كعمود عادي</p>";
            
            // Update existing records
            $stmt = $pdo->exec("
                UPDATE work_orders 
                SET ref_ot = CONCAT('OT-', YEAR(created_at), '-', LPAD(id, 4, '0'))
            ");
            echo "<p style='color: green;'>✅ تم تحديث " . $stmt . " سجلات</p>";
        }
    }
    
    // Test the ref_ot generation
    echo "<h3>🧪 اختبار توليد المرجع:</h3>";
    
    $stmt = $pdo->query("SELECT id, created_at, ref_ot FROM work_orders ORDER BY id DESC LIMIT 5");
    $records = $stmt->fetchAll();
    
    if (!empty($records)) {
        echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
        echo "<tr><th>ID</th><th>Date</th><th>Ref OT</th></tr>";
        
        foreach ($records as $record) {
            $expected_ref = 'OT-' . date('Y', strtotime($record['created_at'])) . '-' . str_pad($record['id'], 4, '0', STR_PAD_LEFT);
            $status = ($record['ref_ot'] === $expected_ref) ? '✅' : '❌';
            
            echo "<tr>";
            echo "<td>" . $record['id'] . "</td>";
            echo "<td>" . $record['created_at'] . "</td>";
            echo "<td>" . $record['ref_ot'] . " $status</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p style='color: blue;'>ℹ️ لا توجد سجلات للاختبار</p>";
    }
    
    echo "<h3>🎯 ملخص الحالة:</h3>";
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
    echo "<p><strong>✅ تم إصلاح مشكلة ref_ot بنجاح!</strong></p>";
    echo "<p>الآن صفحة admin_breakdowns_workshop.php يجب أن تعمل بدون أخطاء.</p>";
    echo "</div>";
    
    echo "<div style='text-align: center; margin: 20px 0;'>";
    echo "<a href='admin_breakdowns_workshop.php' class='btn' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px;'>🔧 اختبار الصفحة</a>";
    echo "<a href='javascript:history.back()' class='btn' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px;'>🔙 رجوع</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h4>❌ خطأ:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
