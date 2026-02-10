<?php
// FUTURE AUTOMOTIVE - Check and Create Workshop Tables
// فحص وإنشاء جداول الورشة

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

echo "<h2>🔧 فحص وإنشاء جداول الورشة</h2>";

try {
    $database = new Database();
    $pdo = $database->connect();
    
    echo "<h3>1. فحص الجداول الموجودة:</h3>";
    
    // Check existing tables
    $stmt = $pdo->query("SHOW TABLES");
    $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_tables = ['work_orders', 'work_order_parts', 'work_order_timeline'];
    $missing_tables = [];
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>الجدول</th><th>الحالة</th></tr>";
    
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
        
        // Read and execute the SQL file
        $sqlFile = __DIR__ . '/../sql/hostinger_workshop_final.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            
            // Split SQL into individual statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    try {
                        $pdo->exec($statement);
                        echo "✅ تم: " . substr($statement, 0, 50) . "...<br>";
                    } catch (PDOException $e) {
                        echo "⚠️ خطأ: " . $e->getMessage() . "<br>";
                    }
                }
            }
            
            echo "<h3>3. التحقق من الإنشاء:</h3>";
            
            // Check tables again
            $stmt = $pdo->query("SHOW TABLES");
            $new_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>الجدول</th><th>الحالة</th><th>عدد السجلات</th></tr>";
            
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
                }
            }
            echo "</table>";
            
        } else {
            echo "❌ ملف SQL غير موجود: $sqlFile";
        }
    }
    
    echo "<h3>4. فحص بنية الجداول:</h3>";
    
    // Check work_orders structure
    if (in_array('work_orders', $existing_tables)) {
        echo "<h4>بنية جدول work_orders:</h4>";
        $stmt = $pdo->query("DESCRIBE work_orders");
        $columns = $stmt->fetchAll();
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>العمود</th><th>النوع</th><th>يسمح بـ NULL</th><th>المفتاح</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "</tr>";
        }
        echo "</table>";
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
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>المرجع</th><th>الحافلة</th><th>التقني</th><th>الحالة</th></tr>";
            
            foreach ($work_orders as $wo) {
                echo "<tr>";
                echo "<td>{$wo['ref_ot']}</td>";
                echo "<td>{$wo['bus_number'] ?? '-'}</td>";
                echo "<td>{$wo['technician_name'] ?? '-'}</td>";
                echo "<td>{$wo['status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ خطأ في الاستعلام: " . $e->getMessage() . "</div>";
    }
    
    echo "<hr>";
    echo "<h3>الخطوات التالية:</h3>";
    echo "<ol>";
    echo "<li><a href='admin_breakdowns_workshop.php'>اذهب إلى إدارة الورشة</a></li>";
    echo "<li><a href='../setup_workshop_hostinger.php'>إعداد قاعدة البيانات</a></li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ خطأ عام: " . $e->getMessage() . "</div>";
}
?>
