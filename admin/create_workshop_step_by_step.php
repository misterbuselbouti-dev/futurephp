<?php
// FUTURE AUTOMOTIVE - Step by Step Workshop Table Creation
// إنشاء جداول الورشة خطوة بخطوة

require_once '../config.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    header('Location: ../login.php');
    exit();
}

// Vérifier les autorisations
$user = get_logged_in_user();
$role = $user['role'] ?? '';
if (!in_array($role, ['admin', 'maintenance_manager'], true)) {
    http_response_code(403);
    echo 'Accès refusé.';
    exit();
}

echo "<h2>🔧 إنشاء جداول الورشة خطوة بخطوة</h2>";

try {
    $database = new Database();
    $pdo = $database->connect();
    
    echo "<h3>الخطوة 1: إنشاء جدول work_orders</h3>";
    
    try {
        $sql = "CREATE TABLE IF NOT EXISTS work_orders (
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
        echo "<div style='color: green;'>✅ تم إنشاء جدول work_orders بنجاح</div>";
        
        // Verify table structure
        $stmt = $pdo->query("DESCRIBE work_orders");
        echo "<h4>بنية الجدول:</h4>";
        echo "<table border='1' cellpadding='3'>";
        echo "<tr><th>العمود</th><th>النوع</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
        }
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ خطأ في إنشاء work_orders: " . $e->getMessage() . "</div>";
    }
    
    echo "<hr>";
    echo "<h3>الخطوة 2: إنشاء جدول work_order_parts</h3>";
    
    try {
        $sql = "CREATE TABLE IF NOT EXISTS work_order_parts (
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
        echo "<div style='color: green;'>✅ تم إنشاء جدول work_order_parts بنجاح</div>";
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ خطأ في إنشاء work_order_parts: " . $e->getMessage() . "</div>";
    }
    
    echo "<hr>";
    echo "<h3>الخطوة 3: إنشاء جدول work_order_timeline</h3>";
    
    try {
        $sql = "CREATE TABLE IF NOT EXISTS work_order_timeline (
            id INT AUTO_INCREMENT PRIMARY KEY,
            work_order_id INT NOT NULL,
            action VARCHAR(100) NOT NULL,
            description TEXT,
            performed_by INT NOT NULL,
            performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($sql);
        echo "<div style='color: green;'>✅ تم إنشاء جدول work_order_timeline بنجاح</div>";
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ خطأ في إنشاء work_order_timeline: " . $e->getMessage() . "</div>";
    }
    
    echo "<hr>";
    echo "<h3>الخطوة 4: التحقق من جميع الجداول</h3>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'work_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>الجدول</th><th>الحالة</th></tr>";
    
    $required_tables = ['work_orders', 'work_order_parts', 'work_order_timeline'];
    $all_created = true;
    
    foreach ($required_tables as $table) {
        if (in_array($table, $tables)) {
            echo "<tr><td>$table</td><td style='color: green;'>✅ موجود</td></tr>";
        } else {
            echo "<tr><td>$table</td><td style='color: red;'>❌ غير موجود</td></tr>";
            $all_created = false;
        }
    }
    echo "</table>";
    
    if ($all_created) {
        echo "<hr>";
        echo "<h3>الخطوة 5: إدخال بيانات تجريبية</h3>";
        
        try {
            // Insert work orders
            $sql = "INSERT IGNORE INTO work_orders (
                ref_ot, bus_id, technician_id, work_description, work_type, priority, estimated_hours, status, created_by
            ) VALUES 
            ('OT-20250209-001', 1, 1, 'Changement huile moteur et filtres', 'Maintenance', 'Normal', 2.5, 'Terminé', 1),
            ('OT-20250209-002', 2, 1, 'Réparation frein avant', 'Réparation', 'Urgent', 3.0, 'En cours', 1),
            ('OT-20250209-003', 3, 2, 'Inspection climatisation', 'Inspection', 'Faible', 1.0, 'En attente', 1)";
            
            $pdo->exec($sql);
            echo "<div style='color: green;'>✅ تم إدخال بيانات work_orders</div>";
            
            // Insert parts
            $sql = "INSERT IGNORE INTO work_order_parts (
                work_order_id, ref_article, designation, quantity, unit_cost, total_cost, notes
            ) VALUES 
            (1, 'HUILE-001', 'Huile moteur 15W40', 5, 25.00, 125.00, 'Huile de qualité'),
            (1, 'FILT-001', 'Filtre à huile', 1, 85.00, 85.00, 'Filtre original'),
            (2, 'PLAQ-001', 'Plaquettes de frein avant', 2, 120.00, 240.00, 'Plaquettes haute performance')";
            
            $pdo->exec($sql);
            echo "<div style='color: green;'>✅ تم إدخال بيانات work_order_parts</div>";
            
            // Insert timeline
            $sql = "INSERT IGNORE INTO work_order_timeline (
                work_order_id, action, description, performed_by
            ) VALUES 
            (1, 'Création', 'Ordre de travail créé', 1),
            (1, 'Début', 'Début des travaux', 1),
            (1, 'Fin', 'Travaux terminés avec succès', 1),
            (2, 'Création', 'Ordre de travail créé', 1),
            (2, 'Début', 'Début des travaux', 1)";
            
            $pdo->exec($sql);
            echo "<div style='color: green;'>✅ تم إدخال بيانات work_order_timeline</div>";
            
            // Show final counts
            echo "<h3>النتائج النهائية:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>الجدول</th><th>عدد السجلات</th></tr>";
            
            foreach ($required_tables as $table) {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $count = $stmt->fetch()['count'];
                echo "<tr><td>$table</td><td>$count</td></tr>";
            }
            echo "</table>";
            
            echo "<div style='color: green; font-weight: bold; margin-top: 20px;'>";
            echo "🎉 تم إنشاء نظام إدارة الورشة بنجاح!";
            echo "</div>";
            
            echo "<h3>الخطوات التالية:</h3>";
            echo "<ol>";
            echo "<li><a href='admin_breakdowns_workshop.php'>اذهب إلى إدارة الورشة</a></li>";
            echo "<li>اختبر إنشاء أمر عمل جديد</li>";
            echo "</ol>";
            
        } catch (Exception $e) {
            echo "<div style='color: red;'>❌ خطأ في إدخال البيانات: " . $e->getMessage() . "</div>";
        }
        
    } else {
        echo "<div style='color: red;'>❌ لم يتم إنشاء جميع الجداول بنجاح</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ خطأ عام: " . $e->getMessage() . "</div>";
}
?>
