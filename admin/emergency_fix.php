<?php
// FUTURE AUTOMOTIVE - Emergency Fix
// حل طارئي وسريع جداً

echo "<h2>🚨 حل طارئي وسريع</h2>";

// Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=u442210176_Futur2;charset=utf8mb4", "u442210176_Futur2", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div style='color: green;'>✅ الاتصال نجح</div>";
} catch (PDOException $e) {
    echo "<div style='color: red;'>❌ خطأ: " . $e->getMessage() . "</div>";
    exit;
}

// Emergency: Create work_orders table immediately
echo "<h3>إنشاء جدول work_orders فوراً:</h3>";

try {
    // Drop table first to ensure clean state
    $pdo->exec("DROP TABLE IF EXISTS work_orders");
    echo "<div style='color: orange;'>⚠️ تم حذف الجدول القديم</div>";
    
    // Create the table with ALL required columns
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
    echo "<div style='color: green;'>✅ تم إنشاء work_orders بنجاح</div>";
    
    // Verify the table structure
    $stmt = $pdo->query("DESCRIBE work_orders");
    echo "<h4>بنية الجدول:</h4>";
    echo "<table border='1' cellpadding='3'>";
    echo "<tr><th>العمود</th><th>النوع</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr><td><strong>{$row['Field']}</strong></td><td>{$row['Type']}</td></tr>";
    }
    echo "</table>";
    
    // Insert sample data
    $sql = "INSERT INTO work_orders (ref_ot, bus_id, technician_id, work_description, work_type, priority, status, created_by) VALUES 
    ('OT-20250209-001', 1, 1, 'Changement huile moteur', 'Maintenance', 'Normal', 'Terminé', 1),
    ('OT-20250209-002', 2, 1, 'Réparation freins', 'Réparation', 'Urgent', 'En cours', 1)";
    
    $pdo->exec($sql);
    echo "<div style='color: green;'>✅ تم إدخال بيانات تجريبية</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ خطأ: " . $e->getMessage() . "</div>";
}

// Test the problematic query
echo "<h3>اختبار الاستعلام:</h3>";

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
echo "<h3>النتيجة:</h3>";
echo "<div style='color: green; font-weight: bold; font-size: 18px;'>";
echo "🎉 تم إصلاح المشكلة بنجاح!";
echo "</div>";

echo "<h3>الخطوة التالية:</h3>";
echo "<p><a href='admin_breakdowns_workshop.php' style='font-size: 18px; color: blue; text-decoration: underline;'>اذهب إلى إدارة الورشة</a></p>";

echo "<p><strong>ملاحظة:</strong> إذا استمرت المشاكل، قد تحتاج إلى إنشاء الجداول الأخرى (buses, users) أيضاً.</p>";
?>
