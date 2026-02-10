<?php
// FUTURE AUTOMOTIVE - Check and Create Workshop Tables (Fixed)
// فحص وإنشاء جداول الورشة - نسخة مصححة

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 فحص وإنشاء جداول الورشة</h2>";

try {
    // Try to load config files
    if (!file_exists('../config.php')) {
        throw new Exception("ملف config.php غير موجود");
    }
    
    require_once '../config.php';
    
    if (!file_exists('../includes/functions.php')) {
        throw new Exception("ملف functions.php غير موجود");
    }
    
    require_once '../includes/functions.php';
    
    echo "<div style='color: green;'>✅ تم تحميل ملفات الإعداد بنجاح</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ خطأ في تحميل الملفات: " . $e->getMessage() . "</div>";
    echo "<p>تأكد من وجود الملفات في المسار الصحيح</p>";
    exit;
}

// Vérifier si l'utilisateur est connecté
if (!function_exists('is_logged_in') || !is_logged_in()) {
    echo "<div style='color: orange;'>⚠️ المستخدم غير مسجل للدخول</div>";
    echo "<p><a href='../login.php'>اضغط هنا لتسجيل الدخول</a></p>";
    exit;
}

// Vérifier les autorisations
$user = get_logged_in_user();
$role = $user['role'] ?? '';
if (!in_array($role, ['admin', 'maintenance_manager'], true)) {
    echo "<div style='color: red;'>❌ صلاحيات غير كافية</div>";
    echo "<p>هذه الصفحة تتطلب صلاحيات admin أو maintenance_manager</p>";
    exit;
}

echo "<div style='color: green;'>✅ المستخدم لديه الصلاحيات الكافية</div>";

try {
    $database = new Database();
    $pdo = $database->connect();
    
    echo "<div style='color: green;'>✅ الاتصال بقاعدة البيانات نجح</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage() . "</div>";
    echo "<h3>الحلول المقترح:</h3>";
    echo "<ol>";
    echo "<li>تحقق من بيانات الاتصال في config.php</li>";
    echo "<li>تأكد من أن قاعدة البيانات موجودة</li>";
    echo "<li>تأكد من أن المستخدم لديه صلاحيات كافية</li>";
    echo "</ol>";
    exit;
}

echo "<h3>1. فحص الجداول الموجودة:</h3>";

try {
    // Check existing tables
    $stmt = $pdo->query("SHOW TABLES");
    $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_tables = ['work_orders', 'work_order_parts', 'work_order_timeline'];
    $missing_tables = [];
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>الجدول</th><th>الحالة</th></tr>";
    
    foreach ($required_tables as $table) {
        if (in_array($table, $existing_tables)) {
            echo "<tr><td>$table</td><td style='color: green;'>✅ موجود</td></tr>";
        } else {
            echo "<tr><td>$table</td><td style='color: red;'>❌ غير موجود</td></tr>";
            $missing_tables[] = $table;
        }
    }
    echo "</table>";
    
    if (!empty($missing_tables)) {
        echo "<h3>2. إنشاء الجداول الناقصة:</h3>";
        
        // Create tables one by one
        foreach ($missing_tables as $table) {
            echo "<h4>إنشاء جدول $table:</h4>";
            
            try {
                if ($table === 'work_orders') {
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
                } elseif ($table === 'work_order_parts') {
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
                } elseif ($table === 'work_order_timeline') {
                    $sql = "CREATE TABLE IF NOT EXISTS work_order_timeline (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        work_order_id INT NOT NULL,
                        action VARCHAR(100) NOT NULL,
                        description TEXT,
                        performed_by INT NOT NULL,
                        performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                }
                
                $pdo->exec($sql);
                echo "<div style='color: green;'>✅ تم إنشاء $table بنجاح</div>";
                
                // Verify table structure
                $stmt = $pdo->query("DESCRIBE $table");
                $columns = $stmt->fetchAll();
                
                echo "<table border='1' cellpadding='3' style='border-collapse: collapse; margin: 10px 0;'>";
                echo "<tr style='background: #f0f0f0;'><th>العمود</th><th>النوع</th></tr>";
                foreach ($columns as $column) {
                    echo "<tr><td>{$column['Field']}</td><td>{$column['Type']}</td></tr>";
                }
                echo "</table>";
                
            } catch (Exception $e) {
                echo "<div style='color: red;'>❌ خطأ في إنشاء $table: " . $e->getMessage() . "</div>";
            }
        }
        
        echo "<h3>3. التحقق من الإنشاء النهائي:</h3>";
        
        // Check tables again
        $stmt = $pdo->query("SHOW TABLES");
        $new_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'><th>الجدول</th><th>الحالة</th><th>عدد السجلات</th></tr>";
        
        $all_created = true;
        foreach ($required_tables as $table) {
            if (in_array($table, $new_tables)) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                    $count = $stmt->fetch()['count'];
                    echo "<tr><td>$table</td><td style='color: green;'>✅ تم الإنشاء</td><td>$count</td></tr>";
                } catch (Exception $e) {
                    echo "<tr><td>$table</td><td style='color: green;'>✅ تم الإنشاء</td><td>خطأ في العد</td></tr>";
                }
            } else {
                echo "<tr><td>$table</td><td style='color: red;'>❌ فشل الإنشاء</td><td>-</td></tr>";
                $all_created = false;
            }
        }
        echo "</table>";
        
        if ($all_created) {
            echo "<h3>4. إدخال البيانات التجريبية:</h3>";
            
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
                
            } catch (Exception $e) {
                echo "<div style='color: red;'>❌ خطأ في إدخال البيانات: " . $e->getMessage() . "</div>";
            }
        }
    }
    
    echo "<h3>5. اختبار الاستعلام الرئيسي:</h3>";
    
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
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr style='background: #f0f0f0;'><th>المرجع</th><th>الحافلة</th><th>التقني</th><th>الحالة</th></tr>";
            
            foreach ($work_orders as $wo) {
                echo "<tr>";
                echo "<td>{$wo['ref_ot']}</td>";
                echo "<td>" . ($wo['bus_number'] ?? '-') . "</td>";
                echo "<td>" . ($wo['technician_name'] ?? '-') . "</td>";
                echo "<td>{$wo['status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "<div style='color: green; font-weight: bold; margin-top: 20px;'>";
        echo "🎉 كل شيء يعمل بشكل مثالي!";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ خطأ في الاستعلام: " . $e->getMessage() . "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ خطأ عام: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<h3>الخطوات التالية:</h3>";
echo "<ol>";
echo "<li><a href='admin_breakdowns_workshop.php'>اذهب إلى إدارة الورشة</a></li>";
echo "<li><a href='../admin/admin_breakdowns_workshop.php'>اذهب إلى إدارة الورشة (مسار بديل)</a></li>";
echo "<li><a href='../setup_workshop_hostinger.php'>إعداد قاعدة البيانات</a></li>";
echo "</ol>";

echo "<h3>روابط سريعة:</h3>";
echo "<ul>";
echo "<li><a href='create_workshop_step_by_step.php'>إنشاء خطوة بخطوة</a></li>";
echo "<li><a href='../'>العودة للرئيسية</a></li>";
echo "</ul>";
?>
