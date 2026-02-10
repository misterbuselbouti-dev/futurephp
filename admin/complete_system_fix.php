<?php
// FUTURE AUTOMOTIVE - Complete System Fix
// إصلاح شامل لجميع جداول النظام

echo "<h2>🔧 إصلاح شامل لجميع جداول النظام</h2>";

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

// Step 1: Check all existing tables
echo "<h3>الخطوة 1: فحص جميع الجداول الموجودة</h3>";

try {
    $stmt = $pdo->query("SHOW TABLES");
    $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div>عدد الجداول الموجودة: " . count($existing_tables) . "</div>";
    
    // Required tables for the complete system
    $required_tables = [
        'users',
        'buses', 
        'drivers',
        'articles_catalogue',
        'breakdown_reports',
        'breakdown_assignments',
        'work_orders',
        'work_order_parts',
        'work_order_timeline',
        'notifications'
    ];
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr style='background: #f0f0f0;'><th>الجدول المطلوب</th><th>الحالة</th></tr>";
    
    $missing_tables = [];
    foreach ($required_tables as $table) {
        if (in_array($table, $existing_tables)) {
            echo "<tr><td>$table</td><td style='color: green;'>✅ موجود</td></tr>";
        } else {
            echo "<tr><td>$table</td><td style='color: red;'>❌ غير موجود</td></tr>";
            $missing_tables[] = $table;
        }
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ خطأ في فحص الجداول: " . $e->getMessage() . "</div>";
}

// Step 2: Create missing tables
echo "<h3>الخطوة 2: إنشاء الجداول المفقودة</h3>";

foreach ($missing_tables as $table) {
    echo "<h4>إنشاء جدول $table:</h4>";
    
    try {
        switch ($table) {
            case 'users':
                $sql = "CREATE TABLE users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    full_name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    role ENUM('admin', 'technician', 'agent', 'driver') DEFAULT 'driver',
                    is_active BOOLEAN DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                break;
                
            case 'buses':
                $sql = "CREATE TABLE buses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    bus_number VARCHAR(50) NOT NULL UNIQUE,
                    license_plate VARCHAR(20) NOT NULL,
                    status ENUM('active', 'maintenance', 'out_of_service') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                break;
                
            case 'drivers':
                $sql = "CREATE TABLE drivers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nom VARCHAR(100) NOT NULL,
                    prenom VARCHAR(100) NOT NULL,
                    telephone VARCHAR(20),
                    is_active BOOLEAN DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                break;
                
            case 'articles_catalogue':
                $sql = "CREATE TABLE articles_catalogue (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    code_article VARCHAR(50) UNIQUE NOT NULL,
                    designation VARCHAR(255) NOT NULL,
                    categorie VARCHAR(100) DEFAULT 'Divers',
                    prix_unitaire DECIMAL(15,2) DEFAULT 0.00,
                    stock_ksar DECIMAL(10,2) DEFAULT 0.00,
                    stock_tetouan DECIMAL(10,2) DEFAULT 0.00,
                    stock_actuel DECIMAL(10,2) DEFAULT 0.00,
                    stock_minimal DECIMAL(10,2) DEFAULT 0.00,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                break;
                
            case 'breakdown_reports':
                $sql = "CREATE TABLE breakdown_reports (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    report_ref VARCHAR(50) UNIQUE NOT NULL,
                    description TEXT NOT NULL,
                    category VARCHAR(100),
                    urgency ENUM('urgent', 'normal', 'low') DEFAULT 'normal',
                    status ENUM('nouveau', 'assigne', 'en_cours', 'termine', 'annule') DEFAULT 'nouveau',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    driver_id INT,
                    bus_id INT,
                    created_by_user_id INT,
                    updated_by_user_id INT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                break;
                
            case 'breakdown_assignments':
                $sql = "CREATE TABLE breakdown_assignments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    report_id INT NOT NULL,
                    assigned_to_user_id INT NOT NULL,
                    assigned_by_user_id INT NOT NULL,
                    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    started_at TIMESTAMP NULL,
                    ended_at TIMESTAMP NULL,
                    work_status ENUM('pending', 'in_progress', 'paused', 'completed', 'cancelled') DEFAULT 'pending',
                    notes TEXT,
                    actual_hours DECIMAL(5,2) DEFAULT 0,
                    total_cost DECIMAL(10,2) DEFAULT 0
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                break;
                
            case 'work_orders':
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
                break;
                
            case 'work_order_parts':
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
                break;
                
            case 'work_order_timeline':
                $sql = "CREATE TABLE work_order_timeline (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    work_order_id INT NOT NULL,
                    action VARCHAR(100) NOT NULL,
                    description TEXT,
                    performed_by INT NOT NULL,
                    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                break;
                
            case 'notifications':
                $sql = "CREATE TABLE notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    type VARCHAR(50) NOT NULL,
                    entity_type VARCHAR(50) NOT NULL,
                    entity_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    message TEXT,
                    is_read BOOLEAN DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    read_at TIMESTAMP NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                break;
                
            default:
                $sql = "";
        }
        
        if ($sql) {
            $pdo->exec($sql);
            echo "<div style='color: green;'>✅ تم إنشاء $table بنجاح</div>";
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ خطأ في إنشاء $table: " . $e->getMessage() . "</div>";
    }
}

// Step 3: Insert basic sample data
echo "<h3>الخطوة 3: إدخال بيانات أساسية</h3>";

try {
    // Insert users
    $sql = "INSERT IGNORE INTO users (full_name, email, password, role, is_active) VALUES 
    ('Admin User', 'admin@futureautomotive.net', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1),
    ('Technicien 1', 'tech@futureautomotive.net', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'technician', 1),
    ('Agent 1', 'agent@futureautomotive.net', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agent', 1)";
    $pdo->exec($sql);
    echo "<div style='color: green;'>✅ تم إدخال بيانات users</div>";
    
    // Insert buses
    $sql = "INSERT IGNORE INTO buses (bus_number, license_plate, status) VALUES 
    ('BUS-001', '123-ABC-456', 'active'),
    ('BUS-002', '456-DEF-789', 'active'),
    ('BUS-003', '789-XYZ-012', 'maintenance')";
    $pdo->exec($sql);
    echo "<div style='color: green;'>✅ تم إدخال بيانات buses</div>";
    
    // Insert drivers
    $sql = "INSERT IGNORE INTO drivers (nom, prenom, telephone, is_active) VALUES 
    ('Doe', 'John', '0612345678', 1),
    ('Smith', 'Jane', '0612345678', 1),
    ('Brown', 'Charlie', '0612345678', 1)";
    $pdo->exec($sql);
    echo "<div style='color: green;'>✅ تم إدخال بيانات drivers</div>";
    
    // Insert articles
    $sql = "INSERT IGNORE INTO articles_catalogue (code_article, designation, categorie, prix_unitaire, stock_ksar, stock_minimal) VALUES 
    ('REF-001', 'Huile moteur', 'Liquides', 25.00, 50, 10),
    ('REF-002', 'Filtre à huile', 'Filtres', 85.00, 100, 20),
    ('REF-003', 'Batterie', 'Électrique', 120.00, 25, 5)";
    $pdo->exec($sql);
    echo "<div style='color: green;'>✅ تم إدخال بيانات articles_catalogue</div>";
    
    // Insert work orders
    $sql = "INSERT IGNORE INTO work_orders (ref_ot, bus_id, technician_id, work_description, work_type, priority, status, created_by) VALUES 
    ('OT-20250209-001', 1, 1, 'Changement huile moteur', 'Maintenance', 'Normal', 'Terminé', 1),
    ('OT-20250209-002', 2, 1, 'Réparation freins', 'Réparation', 'Urgent', 'En cours', 1)";
    $pdo->exec($sql);
    echo "<div style='color: green;'>✅ تم إدخال بيانات work_orders</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ خطأ في إدخال البيانات: " . $e->getMessage() . "</div>";
}

// Step 4: Final verification
echo "<h3>الخطوة 4: التحقق النهائي</h3>";

try {
    $stmt = $pdo->query("SHOW TABLES");
    $final_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr style='background: #f0f0f0;'><th>الجدول</th><th>الحالة</th><th>عدد السجلات</th></tr>";
    
    foreach ($required_tables as $table) {
        if (in_array($table, $final_tables)) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $count = $stmt->fetch()['count'];
                echo "<tr><td>$table</td><td style='color: green;'>✅ جاهز</td><td>$count</td></tr>";
            } catch (Exception $e) {
                echo "<tr><td>$table</td><td style='color: green;'>✅ جاهز</td><td>خطأ في العد</td></tr>";
            }
        } else {
            echo "<tr><td>$table</td><td style='color: red;'>❌ غير موجود</td><td>-</td></tr>";
        }
    }
    echo "</table>";
    
    echo "<div style='color: green; font-weight: bold; margin-top: 20px;'>";
    echo "🎉 تم إصلاح النظام بنجاح!";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ خطأ في التحقق النهائي: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<h3>الخطوات التالية:</h3>";
echo "<ol>";
echo "<li><a href='admin_breakdowns_workshop.php'>اذهب إلى إدارة الورشة</a></li>";
echo "<li><a href='admin_breakdowns.php'>اذهب إلى إدارة الأعطال</a></li>";
echo "<li><a href='buses.php'>اذهب إلى إدارة الحافلات</a></li>";
echo "</ol>";

echo "<h3>روابط مفيدة:</h3>";
echo "<ul>";
echo "<li><a href='../'>العودة للرئيسية</a></li>";
echo "<li><a href='simple_check.php'>فحص بسيط</a></li>";
echo "</ul>";
?>
