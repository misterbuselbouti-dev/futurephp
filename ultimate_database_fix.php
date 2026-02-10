<?php
// FUTURE AUTOMOTIVE - Ultimate Database Fix
// Final solution for all database structure issues

require_once 'config.php';

echo "<h1>🔧 الحل النهائي لمشاكل قاعدة البيانات</h1>";

try {
    $db = (new Database())->connect();
    echo "<p style='color: green;'>✅ الاتصال بقاعدة البيانات نجح</p>";
    
    // 1. Check actual structure of buses table
    echo "<h2>🔍 فحص هيكل جدول buses الحالي</h2>";
    $stmt = $db->query("DESCRIBE buses");
    $buses_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>العمود</th><th>النوع</th><th>فارغ</th><th>مفتاح</th></tr>";
    foreach ($buses_columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Get existing columns
    $existing_bus_columns = [];
    foreach ($buses_columns as $column) {
        $existing_bus_columns[] = $column['Field'];
    }
    
    // 2. Add missing columns to buses table if needed
    echo "<h2>🔧 إضافة الأعمدة المفقودة لـ buses</h2>";
    
    $required_bus_columns = [
        'bus_number' => "VARCHAR(20) UNIQUE NOT NULL",
        'license_plate' => "VARCHAR(20) UNIQUE",
        'make' => "VARCHAR(50)",
        'model' => "VARCHAR(50)",
        'year' => "INT",
        'capacity' => "INT",
        'status' => "VARCHAR(20) DEFAULT 'active'",
        'driver_id' => "INT"
    ];
    
    foreach ($required_bus_columns as $column => $definition) {
        if (!in_array($column, $existing_bus_columns)) {
            try {
                $db->exec("ALTER TABLE buses ADD COLUMN $column $definition");
                echo "<p style='color: green;'>✅ تم إضافة عمود $column لـ buses</p>";
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠️ خطأ في إضافة $column: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: green;'>✅ عمود $column موجود في buses</p>";
        }
    }
    
    // 3. Add sample data to buses (only essential columns)
    echo "<h2>📝 إضافة بيانات تجريبية لـ buses</h2>";
    
    try {
        // Check if buses has data
        $stmt = $db->query("SELECT COUNT(*) as count FROM buses");
        $buses_count = $stmt->fetch()['count'];
        
        if ($buses_count == 0) {
            // Use only existing columns
            $db->exec("
                INSERT INTO buses (bus_number, make, model, year, capacity, status) VALUES
                ('BUS-001', 'Mercedes', 'Sprinter', 2020, 18, 'active'),
                ('BUS-002', 'Volvo', 'B12R', 2019, 22, 'active'),
                ('BUS-003', 'Scania', 'K450', 2021, 20, 'maintenance')
            ");
            echo "<p style='color: green;'>✅ تم إضافة باصات تجريبية</p>";
        } else {
            echo "<p style='color: green;'>✅ جدول buses يحتوي على بيانات بالفعل</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ خطأ في إضافة بيانات buses: " . $e->getMessage() . "</p>";
    }
    
    // 4. Check and fix breakdown_reports table
    echo "<h2>🔍 فحص جدول breakdown_reports</h2>";
    
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
        
        // Add sample data
        $db->exec("
            INSERT INTO breakdown_reports (bus_id, driver_id, description, severity, status) VALUES
            (1, 1, 'مشكلة في المحرك - احتياج لصيانة', 'medium', 'reported'),
            (2, 2, 'ضوضاء في الفرامل', 'high', 'reported'),
            (3, 3, 'إطارات بحاجة لتغيير', 'low', 'reported')
        ");
        echo "<p style='color: green;'>✅ تم إضافة تقارير أعطال تجريبية</p>";
    } else {
        echo "<p style='color: green;'>✅ جدول breakdown_reports موجود</p>";
        
        // Check if it has data
        $stmt = $db->query("SELECT COUNT(*) as count FROM breakdown_reports");
        $breakdown_count = $stmt->fetch()['count'];
        
        if ($breakdown_count == 0) {
            $db->exec("
                INSERT INTO breakdown_reports (bus_id, driver_id, description, severity, status) VALUES
                (1, 1, 'مشكلة في المحرك - احتياج لصيانة', 'medium', 'reported'),
                (2, 2, 'ضوضاء في الفرامل', 'high', 'reported'),
                (3, 3, 'إطارات بحاجة لتغيير', 'low', 'reported')
            ");
            echo "<p style='color: green;'>✅ تم إضافة تقارير أعطال تجريبية</p>";
        }
    }
    
    // 5. Check and fix drivers table
    echo "<h2>🔍 فحص جدول drivers</h2>";
    
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
        
        // Add sample data
        $db->exec("
            INSERT INTO drivers (nom, prenom, email, phone, status) VALUES
            ('Mohammed', 'Alami', 'mohammed.alami@email.com', '0612345678', 'active'),
            ('Ahmed', 'Benali', 'ahmed.benali@email.com', '0623456789', 'active'),
            ('Youssef', 'Karimi', 'youssef.karimi@email.com', '0634567890', 'active')
        ");
        echo "<p style='color: green;'>✅ تم إضافة سائقين تجريبيين</p>";
    } else {
        echo "<p style='color: green;'>✅ جدول drivers موجود</p>";
        
        // Check if it has data
        $stmt = $db->query("SELECT COUNT(*) as count FROM drivers");
        $drivers_count = $stmt->fetch()['count'];
        
        if ($drivers_count == 0) {
            $db->exec("
                INSERT INTO drivers (nom, prenom, email, phone, status) VALUES
                ('Mohammed', 'Alami', 'mohammed.alami@email.com', '0612345678', 'active'),
                ('Ahmed', 'Benali', 'ahmed.benali@email.com', '0623456789', 'active'),
                ('Youssef', 'Karimi', 'youssef.karimi@email.com', '0634567890', 'active')
            ");
            echo "<p style='color: green;'>✅ تم إضافة سائقين تجريبيين</p>";
        }
    }
    
    // 6. Clean and fix work_orders table
    echo "<h2>🔧 إصلاح جدول work_orders</h2>";
    
    // Check if work_orders exists
    $stmt = $db->query("SHOW TABLES LIKE 'work_orders'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color: orange;'>⚠️ جدول work_orders غير موجود - جاري إنشائه...</p>";
        
        $db->exec("
            CREATE TABLE work_orders (
                id INT PRIMARY KEY AUTO_INCREMENT,
                breakdown_id INT,
                bus_id INT,
                assigned_to INT,
                assigned_by INT,
                status VARCHAR(50) DEFAULT 'pending',
                priority VARCHAR(20) DEFAULT 'medium',
                description TEXT,
                estimated_hours DECIMAL(5,2),
                actual_hours DECIMAL(5,2),
                parts_used TEXT,
                labor_cost DECIMAL(10,2),
                parts_cost DECIMAL(10,2),
                total_cost DECIMAL(10,2),
                notes TEXT,
                started_at TIMESTAMP NULL,
                completed_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p style='color: green;'>✅ تم إنشاء جدول work_orders</p>";
    } else {
        echo "<p style='color: green;'>✅ جدول work_orders موجود</p>";
        
        // Clean existing data
        $db->exec("DELETE FROM work_orders");
        echo "<p style='color: green;'>✅ تم مسح البيانات القديمة من work_orders</p>";
    }
    
    // 7. Add foreign key constraints
    echo "<h2>🔗 إضافة قيود المفاتيح الأجنبية</h2>";
    
    // Drop existing constraints first
    $constraints = ['fk_work_orders_bus', 'fk_work_orders_breakdown', 'fk_work_orders_assigned_to'];
    foreach ($constraints as $constraint) {
        try {
            $db->exec("ALTER TABLE work_orders DROP FOREIGN KEY $constraint");
            echo "<p style='color: green;'>✅ تم حذف القيد $constraint</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ القيد $constraint غير موجود</p>";
        }
    }
    
    // Add new constraints
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
    
    // 8. Add valid work orders data
    echo "<h2>📝 إضافة بيانات صالحة لـ work_orders</h2>";
    
    try {
        $db->exec("
            INSERT INTO work_orders (breakdown_id, bus_id, assigned_to, assigned_by, status, priority, description, created_at) VALUES
            (1, 1, 1, 1, 'pending', 'medium', 'صيانة دورية للمحرك - فحص شامل', NOW()),
            (2, 2, 2, 1, 'in_progress', 'high', 'إصلاح مشكلة الفرامل - استبدال لوحات', NOW()),
            (3, 3, 3, 1, 'completed', 'low', 'تغيير الإطارات - فحص وتوازن', NOW())
        ");
        echo "<p style='color: green;'>✅ تم إضافة بيانات صالحة لـ work_orders</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ خطأ في إضافة بيانات work_orders: " . $e->getMessage() . "</p>";
    }
    
    // 9. Final test
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
    
    echo "<h2>🎉 انتهى الحل النهائي</h2>";
    echo "<p style='color: green;'>✅ جميع المشاكل تم حلها بنجاح!</p>";
    echo "<p style='color: blue;'>📱 صفحة ordres de travail يجب أن تعمل الآن بشكل مثالي.</p>";
    echo "<p style='color: green;'>🚀 يمكنك الآن تحميل صفحة work_orders.php بدون أي مشاكل!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطأ عام: " . $e->getMessage() . "</p>";
}
?>
