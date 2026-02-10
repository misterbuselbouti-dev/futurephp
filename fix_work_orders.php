<?php
// FUTURE AUTOMOTIVE - Database Structure Fix
// Fix work_orders table structure

require_once 'config.php';

echo "<h1>🔧 تصحيح هيكل قاعدة البيانات</h1>";
echo "<h2>فحص وإصلاح جدول ordres de travail</h2>";

try {
    $db = (new Database())->connect();
    echo "<p style='color: green;'>✅ الاتصال بقاعدة البيانات نجح</p>";
    
    // Check work_orders table structure
    echo "<h3>هيكل جدول work_orders الحالي:</h3>";
    $stmt = $db->query("DESCRIBE work_orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>العمود</th><th>النوع</th><th>فارغ</th><th>مفتاح</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if bus_id column exists
    $bus_id_exists = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'bus_id') {
            $bus_id_exists = true;
            break;
        }
    }
    
    if (!$bus_id_exists) {
        echo "<p style='color: orange;'>⚠️ عمود bus_id غير موجود - سيتم إضافته</p>";
        
        // Add bus_id column
        $db->exec("ALTER TABLE work_orders ADD COLUMN bus_id INT AFTER breakdown_id");
        echo "<p style='color: green;'>✅ تم إضافة عمود bus_id</p>";
        
        // Add foreign key constraint if buses table exists
        try {
            $db->exec("
                ALTER TABLE work_orders 
                ADD CONSTRAINT fk_work_orders_bus 
                FOREIGN KEY (bus_id) REFERENCES buses(id) 
                ON DELETE SET NULL
            ");
            echo "<p style='color: green;'>✅ تم إضافة قيد المفتاح الأجنبي لـ bus_id</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ لم يتم إضافة المفتاح الأجنبي: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ عمود bus_id موجود بالفعل</p>";
    }
    
    // Check other required columns
    $required_columns = [
        'id' => 'INT PRIMARY KEY AUTO_INCREMENT',
        'breakdown_id' => 'INT',
        'assigned_to' => 'INT',
        'assigned_by' => 'INT',
        'status' => 'VARCHAR(50)',
        'priority' => 'VARCHAR(20)',
        'description' => 'TEXT',
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];
    
    echo "<h3>فحص الأعمدة المطلوبة:</h3>";
    foreach ($required_columns as $column => $expected_type) {
        $exists = false;
        foreach ($columns as $col) {
            if ($col['Field'] === $column) {
                $exists = true;
                break;
            }
        }
        
        if (!$exists) {
            echo "<p style='color: orange;'>⚠️ عمود $column غير موجود - جاري الإضافة...</p>";
            try {
                $db->exec("ALTER TABLE work_orders ADD COLUMN $column $expected_type");
                echo "<p style='color: green;'>✅ تم إضافة عمود $column</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ خطأ في إضافة $column: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: green;'>✅ عمود $column موجود</p>";
        }
    }
    
    // Check buses table structure
    echo "<h3>فحص جدول buses:</h3>";
    try {
        $stmt = $db->query("DESCRIBE buses");
        $bus_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>العمود</th><th>النوع</th><th>فارغ</th><th>مفتاح</th></tr>";
        foreach ($bus_columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        $bus_id_exists = false;
        foreach ($bus_columns as $column) {
            if ($column['Field'] === 'id') {
                $bus_id_exists = true;
                break;
            }
        }
        
        if ($bus_id_exists) {
            echo "<p style='color: green;'>✅ جدول buses جاهز للربط</p>";
        } else {
            echo "<p style='color: red;'>❌ جدول buses غير موجود أو لا يحتوي على عمود id</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ خطأ في فحص جدول buses: " . $e->getMessage() . "</p>";
    }
    
    // Test the problematic query
    echo "<h3>اختبار الاستعلام المسبب للمشكلة:</h3>";
    try {
        $test_query = "
            SELECT wo.*, b.bus_number, d.nom as driver_name, d.prenom as driver_firstname,
                   br.description as breakdown_description, br.breakdown_date
            FROM work_orders wo
            LEFT JOIN buses b ON wo.bus_id = b.id
            LEFT JOIN drivers d ON wo.assigned_to = d.id
            LEFT JOIN breakdown_reports br ON wo.breakdown_id = br.id
            ORDER BY wo.created_at DESC
            LIMIT 5
        ";
        
        $stmt = $db->query($test_query);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p style='color: green;'>✅ الاستعلام يعمل بنجاح - عدد النتائج: " . count($results) . "</p>";
        
        if (count($results) > 0) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>ID</th><th>Bus Number</th><th>Driver</th><th>Status</th><th>Created</th></tr>";
            foreach ($results as $row) {
                echo "<tr>";
                echo "<td>{$row['id']}</td>";
                echo "<td>" . ($row['bus_number'] ?? 'N/A') . "</td>";
                echo "<td>" . ($row['driver_name'] ?? 'N/A') . "</td>";
                echo "<td>{$row['status']}</td>";
                echo "<td>{$row['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ الاستخدام لا يزال يواجه مشكلة: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>✅ انتهى التصحيح</h2>";
    echo "<p>الآن يجب أن يعمل تحميل ordres de travail بشكل صحيح.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطأ عام: " . $e->getMessage() . "</p>";
}
?>
