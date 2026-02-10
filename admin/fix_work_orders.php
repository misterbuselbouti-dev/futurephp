<?php
// FUTURE AUTOMOTIVE - Fix Work Orders Table
// إصلاح جدول work_orders

echo "<h2>🔧 إصلاح جدول work_orders</h2>";

// Database connection (using the same credentials that worked)
try {
    $pdo = new PDO("mysql:host=localhost;dbname=u442210176_Futur2;charset=utf8mb4", "u442210176_Futur2", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div style='color: green;'>✅ الاتصال بقاعدة البيانات نجح</div>";
} catch (PDOException $e) {
    echo "<div style='color: red;'>❌ خطأ في الاتصال: " . $e->getMessage() . "</div>";
    exit;
}

// Step 1: Check current structure of work_orders table
echo "<h3>الخطوة 1: فحص بنية work_orders الحالية</h3>";

try {
    $stmt = $pdo->query("DESCRIBE work_orders");
    $current_columns = $stmt->fetchAll();
    
    echo "<h4>الأعمدة الموجودة حالياً:</h4>";
    echo "<table border='1' cellpadding='3'>";
    echo "<tr><th>العمود</th><th>النوع</th><th>الحالة</th></tr>";
    
    $required_columns = [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'ref_ot' => 'VARCHAR(50) UNIQUE NOT NULL',
        'bus_id' => 'INT NOT NULL',
        'technician_id' => 'INT NOT NULL',
        'work_description' => 'TEXT NOT NULL',
        'work_type' => 'VARCHAR(100) DEFAULT \'Maintenance\'',
        'priority' => 'ENUM(\'Faible\', \'Normal\', \'Urgent\', \'Très Urgent\') DEFAULT \'Normal\'',
        'estimated_hours' => 'DECIMAL(5,2) DEFAULT 0',
        'actual_hours' => 'DECIMAL(5,2) DEFAULT 0',
        'status' => 'ENUM(\'En attente\', \'En cours\', \'En pause\', \'Terminé\', \'Annulé\') DEFAULT \'En attente\'',
        'created_by' => 'INT NOT NULL',
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];
    
    $missing_columns = [];
    foreach ($current_columns as $column) {
        $column_name = $column['Field'];
        if (isset($required_columns[$column_name])) {
            echo "<tr><td><strong>$column_name</strong></td><td>{$column['Type']}</td><td style='color: green;'>✅ موجود</td></tr>";
            unset($required_columns[$column_name]);
        } else {
            echo "<tr><td><strong>$column_name</strong></td><td>{$column['Type']}</td><td style='color: orange;'>⚠️ غير ضروري</td></tr>";
        }
    }
    
    // Show missing columns
    foreach ($required_columns as $col_name => $col_def) {
        echo "<tr><td><strong>$col_name</strong></td><td>$col_def</td><td style='color: red;'>❌ مفقود</td></tr>";
        $missing_columns[$col_name] = $col_def;
    }
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ خطأ في فحص البنية: " . $e->getMessage() . "</div>";
    exit;
}

// Step 2: Fix the table
echo "<h3>الخطوة 2: إصلاح الجدول</h3>";

if (!empty($missing_columns)) {
    echo "<h4>إضافة الأعمدة المفقودة:</h4>";
    
    foreach ($missing_columns as $column_name => $column_def) {
        try {
            $sql = "ALTER TABLE work_orders ADD COLUMN $column_name $column_def";
            $pdo->exec($sql);
            echo "<div style='color: green;'>✅ تم إضافة عمود $column_name</div>";
        } catch (Exception $e) {
            echo "<div style='color: red;'>❌ خطأ في إضافة $column_name: " . $e->getMessage() . "</div>";
        }
    }
} else {
    echo "<div style='color: green;'>✅ جميع الأعمدة المطلوبة موجودة</div>";
}

// Step 3: Check if we have the critical columns
echo "<h3>الخطوة 3: التحقق من الأعمدة الحرجة</h3>";

$critical_columns = ['bus_id', 'technician_id', 'ref_ot'];
$all_critical_exist = true;

foreach ($critical_columns as $col) {
    try {
        $stmt = $pdo->query("SELECT $col FROM work_orders LIMIT 1");
        echo "<div style='color: green;'>✅ العمود $col موجود ويعمل</div>";
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ العمود $col غير موجود أو به مشكلة: " . $e->getMessage() . "</div>";
        $all_critical_exist = false;
    }
}

if (!$all_critical_exist) {
    echo "<h3>الخطوة 4: إعادة بناء الجدول (الحل النهائي)</h3>";
    
    try {
        // Backup data if possible
        $backup_data = [];
        try {
            $stmt = $pdo->query("SELECT * FROM work_orders");
            $backup_data = $stmt->fetchAll();
            echo "<div style='color: blue;'>ℹ️ تم نسخ " . count($backup_data) . " سجل احتياطياً</div>";
        } catch (Exception $e) {
            echo "<div style='color: orange;'>⚠️ لا يمكن نسخ البيانات الاحتياطية</div>";
        }
        
        // Drop and recreate table
        $pdo->exec("DROP TABLE work_orders");
        echo "<div style='color: orange;'>⚠️ تم حذف الجدول القديم</div>";
        
        $sql = "CREATE TABLE work_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ref_ot VARCHAR(50) UNIQUE NOT NULL,
            bus_id INT NOT NULL,
            technician_id INT NOT NULL,
            work_description TEXT NOT NULL,
            work_type VARCHAR(100) DEFAULT 'Maintenance',
            priority ENUM('Faible', 'Normal', 'Urgent', 'Très Urgent') DEFAULT 'Normal',
            estimated_hours DECIMAL(5,2) DEFAULT 0,
            actual_hours DECIMAL(5,2) DEFAULT 0,
            status ENUM('En attente', 'En cours', 'En pause', 'Terminé', 'Annulé') DEFAULT 'En attente',
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($sql);
        echo "<div style='color: green;'>✅ تم إعادة إنشاء work_orders بالبنية الصحيحة</div>";
        
        // Restore backup data if possible
        if (!empty($backup_data)) {
            foreach ($backup_data as $row) {
                try {
                    $sql = "INSERT INTO work_orders (ref_ot, bus_id, technician_id, work_description, work_type, priority, estimated_hours, actual_hours, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $row['ref_ot'] ?? 'OT-' . date('Ymd') . '-' . rand(100, 999),
                        $row['bus_id'] ?? 1,
                        $row['technician_id'] ?? 1,
                        $row['work_description'] ?? 'Description par défaut',
                        $row['work_type'] ?? 'Maintenance',
                        $row['priority'] ?? 'Normal',
                        $row['estimated_hours'] ?? 0,
                        $row['actual_hours'] ?? 0,
                        $row['status'] ?? 'En attente',
                        $row['created_by'] ?? 1,
                        $row['created_at'] ?? date('Y-m-d H:i:s')
                    ]);
                } catch (Exception $e) {
                    echo "<div style='color: orange;'>⚠️ خطأ في استعادة سجل: " . $e->getMessage() . "</div>";
                }
            }
            echo "<div style='color: green;'>✅ تم استعادة البيانات الاحتياطية</div>";
        }
        
        // Insert sample data if table is empty
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM work_orders");
        $count = $stmt->fetch()['count'];
        
        if ($count == 0) {
            $sql = "INSERT INTO work_orders (ref_ot, bus_id, technician_id, work_description, work_type, priority, status, created_by) VALUES 
            ('OT-20250209-001', 1, 1, 'Changement huile moteur', 'Maintenance', 'Normal', 'Terminé', 1),
            ('OT-20250209-002', 2, 1, 'Réparation freins', 'Réparation', 'Urgent', 'En cours', 1)";
            
            $pdo->exec($sql);
            echo "<div style='color: green;'>✅ تم إدخال بيانات تجريبية</div>";
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ خطأ في إعادة البناء: " . $e->getMessage() . "</div>";
    }
}

// Step 5: Final test
echo "<h3>الخطوة 5: الاختبار النهائي</h3>";

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
    
    echo "<div style='color: green; font-weight: bold;'>✅ الاستعلام يعمل بنجاح!</div>";
    echo "<div>عدد النتائج: " . count($results) . "</div>";
    
    if (!empty($results)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>المرجع</th><th>الحافلة</th><th>التقني</th><th>الحالة</th></tr>";
        
        foreach ($results as $row) {
            echo "<tr>";
            echo "<td>{$row['ref_ot']}</td>";
            echo "<td>" . ($row['bus_number'] ?? 'N/A') . "</td>";
            echo "<td>" . ($row['technician_name'] ?? 'N/A') . "</td>";
            echo "<td>{$row['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ خطأ في الاستعلام: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<div style='color: green; font-weight: bold; font-size: 18px;'>";
echo "🎉 تم إصلاح جدول work_orders بنجاح!";
echo "</div>";

echo "<h3>الخطوات التالية:</h3>";
echo "<ol>";
echo "<li><a href='admin_breakdowns_workshop.php'>اذهب إلى إدارة الورشة</a></li>";
echo "<li>اختبر إنشاء أمر عمل جديد</li>";
echo "</ol>";
?>
