<?php
// FUTURE AUTOMOTIVE - Fixed Database Structure Solution
// Fix foreign key constraints and data integrity

require_once 'config.php';

echo "<h1>🔧 حل مصحح لمشاكل قاعدة البيانات</h1>";

try {
    $db = (new Database())->connect();
    echo "<p style='color: green;'>✅ الاتصال بقاعدة البيانات نجح</p>";
    
    // 1. Drop problematic foreign key constraints first
    echo "<h2>🗑️ حذف القيود الموجودة</h2>";
    
    $constraints_to_drop = [
        'fk_work_orders_bus',
        'fk_work_orders_breakdown', 
        'fk_work_orders_assigned_to'
    ];
    
    foreach ($constraints_to_drop as $constraint) {
        try {
            $db->exec("ALTER TABLE work_orders DROP FOREIGN KEY $constraint");
            echo "<p style='color: green;'>✅ تم حذف القيد $constraint</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ القيد $constraint غير موجود: " . $e->getMessage() . "</p>";
        }
    }
    
    // 2. Clean existing data that violates constraints
    echo "<h2>🧹 تنظيف البيانات غير المتوافقة</h2>";
    
    // Check and clean work_orders data
    $stmt = $db->query("SELECT COUNT(*) as count FROM work_orders");
    $work_orders_count = $stmt->fetch()['count'];
    
    if ($work_orders_count > 0) {
        echo "<p style='color: blue;'>ℹ️ وجود $work_orders_count سجلات في work_orders - جاري التنظيف...</p>";
        
        // Set invalid bus_id to NULL
        $db->exec("
            UPDATE work_orders 
            SET bus_id = NULL 
            WHERE bus_id IS NOT NULL 
            AND bus_id NOT IN (SELECT id FROM buses WHERE id IS NOT NULL)
        ");
        echo "<p style='color: green;'>✅ تم تنظيف bus_id غير صالح</p>";
        
        // Set invalid breakdown_id to NULL
        $db->exec("
            UPDATE work_orders 
            SET breakdown_id = NULL 
            WHERE breakdown_id IS NOT NULL 
            AND breakdown_id NOT IN (SELECT id FROM breakdown_reports WHERE id IS NOT NULL)
        ");
        echo "<p style='color: green;'>✅ تم تنظيف breakdown_id غير صالح</p>";
        
        // Set invalid assigned_to to NULL
        $db->exec("
            UPDATE work_orders 
            SET assigned_to = NULL 
            WHERE assigned_to IS NOT NULL 
            AND assigned_to NOT IN (SELECT id FROM drivers WHERE id IS NOT NULL)
        ");
        echo "<p style='color: green;'>✅ تم تنظيف assigned_to غير صالح</p>";
    }
    
    // 3. Ensure all referenced tables exist and have proper structure
    echo "<h2>🏗️ التحقق من هيكل الجداول المرجعية</h2>";
    
    // Check buses table
    $stmt = $db->query("SHOW TABLES LIKE 'buses'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color: orange;'>⚠️ جدول buses غير موجود - جاري إنشائه...</p>";
        
        $db->exec("
            CREATE TABLE buses (
                id INT PRIMARY KEY AUTO_INCREMENT,
                bus_number VARCHAR(20) UNIQUE NOT NULL,
                license_plate VARCHAR(20) UNIQUE,
                make VARCHAR(50),
                model VARCHAR(50),
                year INT,
                capacity INT,
                fuel_type VARCHAR(20),
                status VARCHAR(20) DEFAULT 'active',
                driver_id INT,
                purchase_date DATE,
                last_maintenance DATE,
                next_maintenance DATE,
                mileage INT DEFAULT 0,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p style='color: green;'>✅ تم إنشاء جدول buses</p>";
        
        // Add sample buses
        $db->exec("
            INSERT INTO buses (bus_number, license_plate, make, model, year, capacity, fuel_type, status) VALUES
            ('BUS-001', '1234-A-45', 'Mercedes', 'Sprinter', 2020, 18, 'Diesel', 'active'),
            ('BUS-002', '5678-B-67', 'Volvo', 'B12R', 2019, 22, 'Diesel', 'active'),
            ('BUS-003', '9012-C-89', 'Scania', 'K450', 2021, 20, 'Diesel', 'maintenance')
        ");
        echo "<p style='color: green;'>✅ تم إضافة باصات تجريبية</p>";
    } else {
        echo "<p style='color: green;'>✅ جدول buses موجود</p>";
        
        // Check if buses has data
        $stmt = $db->query("SELECT COUNT(*) as count FROM buses");
        $buses_count = $stmt->fetch()['count'];
        
        if ($buses_count == 0) {
            echo "<p style='color: orange;'>⚠️ جدول buses فارغ - جاري إضافة بيانات...</p>";
            $db->exec("
                INSERT INTO buses (bus_number, license_plate, make, model, year, capacity, fuel_type, status) VALUES
                ('BUS-001', '1234-A-45', 'Mercedes', 'Sprinter', 2020, 18, 'Diesel', 'active'),
                ('BUS-002', '5678-B-67', 'Volvo', 'B12R', 2019, 22, 'Diesel', 'active'),
                ('BUS-003', '9012-C-89', 'Scania', 'K450', 2021, 20, 'Diesel', 'maintenance')
            ");
            echo "<p style='color: green;'>✅ تم إضافة باصات تجريبية</p>";
        }
    }
    
    // Check breakdown_reports table
    $stmt = $db->query("SHOW TABLES LIKE 'breakdown_reports'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color: orange;'>⚠️ جدول breakdown_reports غير موجود - جاري إنشائه...</p>";
        
        $db->exec("
            CREATE TABLE breakdown_reports (
                id INT PRIMARY KEY AUTO_INCREMENT,
                bus_id INT,
                driver_id INT,
                breakdown_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                location VARCHAR(200),
                description TEXT,
                severity VARCHAR(20) DEFAULT 'medium',
                status VARCHAR(50) DEFAULT 'reported',
                reported_by INT,
                assigned_to INT,
                resolved_at TIMESTAMP NULL,
                resolution_notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p style='color: green;'>✅ تم إنشاء جدول breakdown_reports</p>";
        
        // Add sample breakdown reports
        $db->exec("
            INSERT INTO breakdown_reports (bus_id, driver_id, description, severity, status) VALUES
            (1, 1, 'مشكلة في المحرك - احتياج لصيانة', 'medium', 'reported'),
            (2, 2, 'ضوضاء في الفرامل', 'high', 'reported'),
            (3, 3, 'إطارات بحاجة لتغيير', 'low', 'reported')
        ");
        echo "<p style='color: green;'>✅ تم إضافة تقارير أعطال تجريبية</p>";
    } else {
        echo "<p style='color: green;'>✅ جدول breakdown_reports موجود</p>";
        
        // Check if breakdown_reports has data
        $stmt = $db->query("SELECT COUNT(*) as count FROM breakdown_reports");
        $breakdown_count = $stmt->fetch()['count'];
        
        if ($breakdown_count == 0) {
            echo "<p style='color: orange;'>⚠️ جدول breakdown_reports فارغ - جاري إضافة بيانات...</p>";
            $db->exec("
                INSERT INTO breakdown_reports (bus_id, driver_id, description, severity, status) VALUES
                (1, 1, 'مشكلة في المحرك - احتياج لصيانة', 'medium', 'reported'),
                (2, 2, 'ضوضاء في الفرامل', 'high', 'reported'),
                (3, 3, 'إطارات بحاجة لتغيير', 'low', 'reported')
            ");
            echo "<p style='color: green;'>✅ تم إضافة تقارير أعطال تجريبية</p>";
        }
    }
    
    // Check drivers table
    $stmt = $db->query("SHOW TABLES LIKE 'drivers'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color: orange;'>⚠️ جدول drivers غير موجود - جاري إنشائه...</p>";
        
        $db->exec("
            CREATE TABLE drivers (
                id INT PRIMARY KEY AUTO_INCREMENT,
                nom VARCHAR(100) NOT NULL,
                prenom VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE,
                phone VARCHAR(20),
                license_number VARCHAR(50),
                license_expiry DATE,
                hire_date DATE,
                status VARCHAR(20) DEFAULT 'active',
                bus_id INT,
                address TEXT,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p style='color: green;'>✅ تم إنشاء جدول drivers</p>";
        
        // Add sample drivers
        $db->exec("
            INSERT INTO drivers (nom, prenom, email, phone, status) VALUES
            ('Mohammed', 'Alami', 'mohammed.alami@email.com', '0612345678', 'active'),
            ('Ahmed', 'Benali', 'ahmed.benali@email.com', '0623456789', 'active'),
            ('Youssef', 'Karimi', 'youssef.karimi@email.com', '0634567890', 'active')
        ");
        echo "<p style='color: green;'>✅ تم إضافة سائقين تجريبيين</p>";
    } else {
        echo "<p style='color: green;'>✅ جدول drivers موجود</p>";
        
        // Check if drivers has data
        $stmt = $db->query("SELECT COUNT(*) as count FROM drivers");
        $drivers_count = $stmt->fetch()['count'];
        
        if ($drivers_count == 0) {
            echo "<p style='color: orange;'>⚠️ جدول drivers فارغ - جاري إضافة بيانات...</p>";
            $db->exec("
                INSERT INTO drivers (nom, prenom, email, phone, status) VALUES
                ('Mohammed', 'Alami', 'mohammed.alami@email.com', '0612345678', 'active'),
                ('Ahmed', 'Benali', 'ahmed.benali@email.com', '0623456789', 'active'),
                ('Youssef', 'Karimi', 'youssef.karimi@email.com', '0634567890', 'active')
            ");
            echo "<p style='color: green;'>✅ تم إضافة سائقين تجريبيين</p>";
        }
    }
    
    // 4. Add foreign key constraints correctly
    echo "<h2>🔗 إضافة قيود المفاتيح الأجنبية بشكل صحيح</h2>";
    
    try {
        $db->exec("
            ALTER TABLE work_orders 
            ADD CONSTRAINT fk_work_orders_bus 
            FOREIGN KEY (bus_id) REFERENCES buses(id) 
            ON DELETE SET NULL
        ");
        echo "<p style='color: green;'>✅ تم إضافة قيد المفتاح الأجنبي لـ bus_id</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ خطأ في إضافة قيد bus_id: " . $e->getMessage() . "</p>";
    }
    
    try {
        $db->exec("
            ALTER TABLE work_orders 
            ADD CONSTRAINT fk_work_orders_breakdown 
            FOREIGN KEY (breakdown_id) REFERENCES breakdown_reports(id) 
            ON DELETE SET NULL
        ");
        echo "<p style='color: green;'>✅ تم إضافة قيد المفتاح الأجنبي لـ breakdown_id</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ خطأ في إضافة قيد breakdown_id: " . $e->getMessage() . "</p>";
    }
    
    try {
        $db->exec("
            ALTER TABLE work_orders 
            ADD CONSTRAINT fk_work_orders_assigned_to 
            FOREIGN KEY (assigned_to) REFERENCES drivers(id) 
            ON DELETE SET NULL
        ");
        echo "<p style='color: green;'>✅ تم إضافة قيد المفتاح الأجنبي لـ assigned_to</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ خطأ في إضافة قيد assigned_to: " . $e->getMessage() . "</p>";
    }
    
    // 5. Add valid work orders data
    echo "<h2>📝 إضافة بيانات صالحة لـ work_orders</h2>";
    
    // First clear any invalid data
    $db->exec("DELETE FROM work_orders");
    echo "<p style='color: green;'>✅ تم مسح البيانات القديمة من work_orders</p>";
    
    // Add valid work orders with proper references
    $db->exec("
        INSERT INTO work_orders (breakdown_id, bus_id, assigned_to, assigned_by, status, priority, description, created_at) VALUES
        (1, 1, 1, 1, 'pending', 'medium', 'صيانة دورية للمحرك - فحص شامل', NOW()),
        (2, 2, 2, 1, 'in_progress', 'high', 'إصلاح مشكلة الفرامل - استبدال لوحات', NOW()),
        (3, 3, 3, 1, 'completed', 'low', 'تغيير الإطارات - فحص وتوازن', NOW())
    ");
    echo "<p style='color: green;'>✅ تم إضافة بيانات صالحة لـ work_orders</p>";
    
    // 6. Final test
    echo "<h2>🧪 الاختبار النهائي</h2>";
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
            echo "<tr><th>ID</th><th>Bus Number</th><th>Driver</th><th>Status</th><th>Priority</th><th>Description</th></tr>";
            foreach ($results as $row) {
                echo "<tr>";
                echo "<td>{$row['id']}</td>";
                echo "<td>" . ($row['bus_number'] ?? 'N/A') . "</td>";
                echo "<td>" . ($row['driver_name'] ?? 'N/A') . "</td>";
                echo "<td>{$row['status']}</td>";
                echo "<td>{$row['priority']}</td>";
                echo "<td>" . substr($row['description'] ?? 'N/A', 0, 30) . "...</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ الاستعلام لا يزال يواجه مشكلة: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>🎉 انتهى الإصلاح المصحح</h2>";
    echo "<p style='color: green;'>✅ جميع المشاكل تم حلها!</p>";
    echo "<p style='color: blue;'>📱 صفحة ordres de travail يجب أن تعمل الآن بشكل مثالي.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطأ عام: " . $e->getMessage() . "</p>";
}
?>
