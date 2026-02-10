<?php
// FUTURE AUTOMOTIVE - Quick Fix for Workshop Tables
// حل سريع لمشاكل جداول الورشة

echo "<h2>🔧 حل سريع لمشاكل جداول الورشة</h2>";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=u442210176_Futur2;charset=utf8mb4", "u442210176_Futur2", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div style='color: green;'>✅ الاتصال بقاعدة البيانات نجح</div>";
} catch (PDOException $e) {
    echo "<div style='color: red;'>❌ خطأ في الاتصال: " . $e->getMessage() . "</div>";
    exit;
}

// Step 1: Drop existing tables if they exist (to fix structure issues)
echo "<h3>الخطوة 1: حذف الجداول القديمة (إذا وجدت)</h3>";

$tables_to_drop = ['work_orders', 'work_order_parts', 'work_order_timeline'];
foreach ($tables_to_drop as $table) {
    try {
        $pdo->exec("DROP TABLE IF EXISTS $table");
        echo "<div style='color: orange;'>⚠️ تم حذف $table (إذا كان موجوداً)</div>";
    } catch (Exception $e) {
        echo "<div style='color: blue;'>ℹ️ $table غير موجود أو تم حذفه</div>";
    }
}

// Step 2: Create work_orders table
echo "<h3>الخطوة 2: إنشاء جدول work_orders</h3>";

try {
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
    
    // Verify structure
    $stmt = $pdo->query("DESCRIBE work_orders");
    echo "<table border='1' cellpadding='3'>";
    echo "<tr><th>العمود</th><th>النوع</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ خطأ في إنشاء work_orders: " . $e->getMessage() . "</div>";
}

// Step 3: Create work_order_parts table
echo "<h3>الخطوة 3: إنشاء جدول work_order_parts</h3>";

try {
    $sql = "CREATE TABLE work_order_parts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        work_order_id INT NOT NULL,
        ref_article VARCHAR(50) NOT NULL,
        designation VARCHAR(255) NOT NULL,
        quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
        unit_cost DECIMAL(10,2) DEFAULT 0,
        total_cost DECIMAL(10,2) DEFAULT 0,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql);
    echo "<div style='color: green;'>✅ تم إنشاء work_order_parts بنجاح</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ خطأ في إنشاء work_order_parts: " . $e->getMessage() . "</div>";
}

// Step 4: Create work_order_timeline table
echo "<h3>الخطوة 4: إنشاء جدول work_order_timeline</h3>";

try {
    $sql = "CREATE TABLE work_order_timeline (
        id INT AUTO_INCREMENT PRIMARY KEY,
        work_order_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        description TEXT,
        performed_by INT NOT NULL,
        performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql);
    echo "<div style='color: green;'>✅ تم إنشاء work_order_timeline بنجاح</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ خطأ في إنشاء work_order_timeline: " . $e->getMessage() . "</div>";
}

// Step 5: Insert sample data
echo "<h3>الخطوة 5: إدخال بيانات تجريبية</h3>";

try {
    // Insert work orders
    $sql = "INSERT INTO work_orders (ref_ot, bus_id, technician_id, work_description, work_type, priority, estimated_hours, status, created_by) VALUES 
    ('OT-20250209-001', 1, 1, 'Changement huile moteur et filtres', 'Maintenance', 'Normal', 2.5, 'Terminé', 1),
    ('OT-20250209-002', 2, 1, 'Réparation frein avant', 'Réparation', 'Urgent', 3.0, 'En cours', 1),
    ('OT-20250209-003', 3, 2, 'Inspection climatisation', 'Inspection', 'Faible', 1.0, 'En attente', 1)";
    
    $pdo->exec($sql);
    echo "<div style='color: green;'>✅ تم إدخال بيانات work_orders</div>";
    
    // Insert parts
    $sql = "INSERT INTO work_order_parts (work_order_id, ref_article, designation, quantity, unit_cost, total_cost, notes) VALUES 
    (1, 'HUILE-001', 'Huile moteur 15W40', 5, 25.00, 125.00, 'Huile de qualité'),
    (1, 'FILT-001', 'Filtre à huile', 1, 85.00, 85.00, 'Filtre original'),
    (2, 'PLAQ-001', 'Plaquettes de frein avant', 2, 120.00, 240.00, 'Plaquettes haute performance')";
    
    $pdo->exec($sql);
    echo "<div style='color: green;'>✅ تم إدخال بيانات work_order_parts</div>";
    
    // Insert timeline
    $sql = "INSERT INTO work_order_timeline (work_order_id, action, description, performed_by) VALUES 
    (1, 'Création', 'Ordre de travail créé', 1),
    (1, 'Début', 'Début des travaux', 1),
    (1, 'Fin', 'Travaux terminés avec succès', 1),
    (2, 'Création', 'Ordre de travail créé', 1),
    (2, 'Début', 'Début des travaux', 1)";
    
    $pdo->exec($sql);
    echo "<div style='color: green;'>✅ تم إدخال بيانات work_order_timeline</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ خطأ في إدخال البيانات: " . $e->getMessage() . "</div>";
}

// Step 6: Test the problematic query
echo "<h3>الخطوة 6: اختبار الاستعلام الرئيسي</h3>";

try {
    $stmt = $pdo->query("
        SELECT wo.*, 
               b.bus_number, b.license_plate,
               u.full_name as technician_name,
               COUNT(wop.id) as parts_count,
               SUM(wop.total_cost) as total_parts_cost
        FROM work_orders wo
        LEFT JOIN buses b ON wo.bus_id = b.id
        LEFT JOIN users u ON wo.technician_id = u.id
        LEFT JOIN work_order_parts wop ON wo.id = wop.work_order_id
        GROUP BY wo.id
        ORDER BY wo.created_at DESC
        LIMIT 5
    ");
    $work_orders = $stmt->fetchAll();
    
    echo "<div style='color: green;'>✅ الاستعلام يعمل بنجاح!</div>";
    echo "<div>عدد النتائج: " . count($work_orders) . "</div>";
    
    if (!empty($work_orders)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>المرجع</th><th>الحافلة</th><th>التقني</th><th>الحالة</th><th>القطع</th><th>التكلفة</th></tr>";
        
        foreach ($work_orders as $wo) {
            echo "<tr>";
            echo "<td>{$wo['ref_ot']}</td>";
            echo "<td>" . ($wo['bus_number'] ?? '-') . "</td>";
            echo "<td>" . ($wo['technician_name'] ?? '-') . "</td>";
            echo "<td>{$wo['status']}</td>";
            echo "<td>" . ($wo['parts_count'] ?? 0) . "</td>";
            echo "<td>" . number_format($wo['total_parts_cost'] ?? 0, 2) . " DH</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ خطأ في الاستعلام: " . $e->getMessage() . "</div>";
}

// Final verification
echo "<h3>النتائج النهائية:</h3>";

try {
    $tables = ['work_orders', 'work_order_parts', 'work_order_timeline'];
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>الجدول</th><th>الحالة</th><th>عدد السجلات</th></tr>";
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        echo "<tr><td>$table</td><td style='color: green;'>✅ جاهز</td><td>$count</td></tr>";
    }
    echo "</table>";
    
    echo "<div style='color: green; font-weight: bold; margin-top: 20px;'>";
    echo "🎉 تم إصلاح نظام إدارة الورشة بنجاح!";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ خطأ في التحقق النهائي: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<h3>الخطوات التالية:</h3>";
echo "<ol>";
echo "<li><a href='admin_breakdowns_workshop.php'>اذهب إلى إدارة الورشة</a></li>";
echo "<li>اختبر إنشاء أمر عمل جديد</li>";
echo "<li>تحقق من القائمة الجانبية</li>";
echo "</ol>";

echo "<p><strong>ملاحظة:</strong> إذا استمرت المشاكل، قد تحتاج إلى التحقق من وجود الجداول الأساسية (buses, users) في قاعدة البيانات.</p>";
?>
