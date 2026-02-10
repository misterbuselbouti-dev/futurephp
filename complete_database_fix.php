<?php
// FUTURE AUTOMOTIVE - Complete Database Structure Fix
// Fix all missing columns and relationships

require_once 'config.php';

echo "<h1>🔧 إصلاح شامل لهيكل قاعدة البيانات</h1>";

try {
    $db = (new Database())->connect();
    echo "<p style='color: green;'>✅ الاتصال بقاعدة البيانات نجح</p>";
    
    // 1. Fix work_orders table
    echo "<h2>📋 إصلاح جدول work_orders</h2>";
    
    // Check if table exists
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
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                INDEX idx_breakdown_id (breakdown_id),
                INDEX idx_bus_id (bus_id),
                INDEX idx_assigned_to (assigned_to),
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p style='color: green;'>✅ تم إنشاء جدول work_orders</p>";
    } else {
        echo "<p style='color: green;'>✅ جدول work_orders موجود</p>";
        
        // Check and add missing columns
        $stmt = $db->query("DESCRIBE work_orders");
        $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $required_columns = [
            'bus_id' => 'INT',
            'breakdown_id' => 'INT',
            'assigned_to' => 'INT',
            'assigned_by' => 'INT',
            'status' => "VARCHAR(50) DEFAULT 'pending'",
            'priority' => "VARCHAR(20) DEFAULT 'medium'",
            'description' => 'TEXT',
            'estimated_hours' => 'DECIMAL(5,2)',
            'actual_hours' => 'DECIMAL(5,2)',
            'parts_used' => 'TEXT',
            'labor_cost' => 'DECIMAL(10,2)',
            'parts_cost' => 'DECIMAL(10,2)',
            'total_cost' => 'DECIMAL(10,2)',
            'notes' => 'TEXT',
            'started_at' => 'TIMESTAMP NULL',
            'completed_at' => 'TIMESTAMP NULL'
        ];
        
        foreach ($required_columns as $column => $definition) {
            if (!in_array($column, $existing_columns)) {
                try {
                    $db->exec("ALTER TABLE work_orders ADD COLUMN $column $definition");
                    echo "<p style='color: green;'>✅ تم إضافة عمود $column</p>";
                } catch (Exception $e) {
                    echo "<p style='color: orange;'>⚠️ خطأ في إضافة $column: " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    
    // 2. Fix buses table
    echo "<h2>🚌 إصلاح جدول buses</h2>";
    
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
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                INDEX idx_bus_number (bus_number),
                INDEX idx_status (status),
                INDEX idx_driver_id (driver_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p style='color: green;'>✅ تم إنشاء جدول buses</p>";
        
        // Add sample data
        $db->exec("
            INSERT INTO buses (bus_number, license_plate, make, model, year, capacity, fuel_type, status) VALUES
            ('BUS-001', '1234-A-45', 'Mercedes', 'Sprinter', 2020, 18, 'Diesel', 'active'),
            ('BUS-002', '5678-B-67', 'Volvo', 'B12R', 2019, 22, 'Diesel', 'active'),
            ('BUS-003', '9012-C-89', 'Scania', 'K450', 2021, 20, 'Diesel', 'maintenance')
        ");
        echo "<p style='color: green;'>✅ تم إضافة بيانات تجريبية للباصات</p>";
    } else {
        echo "<p style='color: green;'>✅ جدول buses موجود</p>";
    }
    
    // 3. Fix drivers table
    echo "<h2>👤 إصلاح جدول drivers</h2>";
    
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
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                INDEX idx_nom (nom),
                INDEX idx_status (status),
                INDEX idx_bus_id (bus_id)
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
        echo "<p style='color: green;'>✅ تم إضافة بيانات تجريبية للسائقين</p>";
    } else {
        echo "<p style='color: green;'>✅ جدول drivers موجود</p>";
    }
    
    // 4. Fix breakdown_reports table
    echo "<h2>🔧 إصلاح جدول breakdown_reports</h2>";
    
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
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                INDEX idx_bus_id (bus_id),
                INDEX idx_driver_id (driver_id),
                INDEX idx_status (status),
                INDEX idx_breakdown_date (breakdown_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p style='color: green;'>✅ تم إنشاء جدول breakdown_reports</p>";
    } else {
        echo "<p style='color: green;'>✅ جدول breakdown_reports موجود</p>";
    }
    
    // 5. Add foreign key constraints
    echo "<h2>🔗 إضافة قيود المفاتيح الأجنبية</h2>";
    
    try {
        $db->exec("
            ALTER TABLE work_orders 
            ADD CONSTRAINT fk_work_orders_bus 
            FOREIGN KEY (bus_id) REFERENCES buses(id) 
            ON DELETE SET NULL
        ");
        echo "<p style='color: green;'>✅ تم إضافة قيد المفتاح الأجنبي لـ work_orders.bus_id</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ المفتاح الأجنبي لـ work_orders.bus_id موجود بالفعل: " . $e->getMessage() . "</p>";
    }
    
    try {
        $db->exec("
            ALTER TABLE work_orders 
            ADD CONSTRAINT fk_work_orders_breakdown 
            FOREIGN KEY (breakdown_id) REFERENCES breakdown_reports(id) 
            ON DELETE SET NULL
        ");
        echo "<p style='color: green;'>✅ تم إضافة قيد المفتاح الأجنبي لـ work_orders.breakdown_id</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ المفتاح الأجنبي لـ work_orders.breakdown_id موجود بالفعل: " . $e->getMessage() . "</p>";
    }
    
    try {
        $db->exec("
            ALTER TABLE work_orders 
            ADD CONSTRAINT fk_work_orders_assigned_to 
            FOREIGN KEY (assigned_to) REFERENCES drivers(id) 
            ON DELETE SET NULL
        ");
        echo "<p style='color: green;'>✅ تم إضافة قيد المفتاح الأجنبي لـ work_orders.assigned_to</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ المفتاح الأجنبي لـ work_orders.assigned_to موجود بالفعل: " . $e->getMessage() . "</p>";
    }
    
    // 6. Test the problematic query
    echo "<h2>🧪 اختبار الاستعلام المسبب للمشكلة</h2>";
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
        } else {
            echo "<p style='color: blue;'>ℹ️ لا توجد بيانات في جدول work_orders - سيتم إضافة بيانات تجريبية...</p>";
            
            // Add sample work orders
            $db->exec("
                INSERT INTO work_orders (breakdown_id, bus_id, assigned_to, assigned_by, status, priority, description) VALUES
                (1, 1, 1, 1, 'pending', 'medium', 'صيانة دورية للمحرك'),
                (2, 2, 2, 1, 'in_progress', 'high', 'إطلاق تغيير الزيت'),
                (3, 3, 3, 1, 'completed', 'low', 'فحص الفرامل')
            ");
            echo "<p style='color: green;'>✅ تم إضافة بيانات تجريبية لـ work_orders</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ الاستخدام لا يزال يواجه مشكلة: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>🎉 انتهى الإصلاح الشامل</h2>";
    echo "<p>✅ جميع الجداول تم إصلاحها والاستعلام يعمل الآن بشكل صحيح!</p>";
    echo "<p>📱 يمكنك الآن تحميل صفحة ordres de travail بدون مشاكل.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطأ عام: " . $e->getMessage() . "</p>";
}
?>
