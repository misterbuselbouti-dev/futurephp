<?php
// FUTURE AUTOMOTIVE - Database Setup with Connection Form
// إعداد قاعدة البيانات مع نموذج اتصال

echo "<h2>🔧 إعداد قاعدة البيانات</h2>";

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_database'])) {
    $host = $_POST['host'] ?? 'localhost';
    $dbname = $_POST['dbname'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<h3>محاولة الاتصال بقاعدة البيانات...</h3>";
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<div style='color: green; font-weight: bold;'>✅ الاتصال بقاعدة البيانات نجح!</div>";
        echo "<div>Host: $host</div>";
        echo "<div>Database: $dbname</div>";
        echo "<div>Username: $username</div>";
        
        // Now create the tables
        echo "<h3>إنشاء جداول الورشة...</h3>";
        
        // Drop existing tables first
        $tables_to_drop = ['work_orders', 'work_order_parts', 'work_order_timeline'];
        foreach ($tables_to_drop as $table) {
            try {
                $pdo->exec("DROP TABLE IF EXISTS $table");
                echo "<div style='color: orange;'>⚠️ تم حذف $table</div>";
            } catch (Exception $e) {
                echo "<div style='color: blue;'>ℹ️ $table غير موجود</div>";
            }
        }
        
        // Create work_orders table
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
            echo "<div style='color: green;'>✅ تم إنشاء work_orders</div>";
            
            // Insert sample data
            $sql = "INSERT INTO work_orders (ref_ot, bus_id, technician_id, work_description, work_type, priority, status, created_by) VALUES 
            ('OT-20250209-001', 1, 1, 'Changement huile moteur', 'Maintenance', 'Normal', 'Terminé', 1),
            ('OT-20250209-002', 2, 1, 'Réparation freins', 'Réparation', 'Urgent', 'En cours', 1)";
            
            $pdo->exec($sql);
            echo "<div style='color: green;'>✅ تم إدخال بيانات work_orders</div>";
            
        } catch (Exception $e) {
            echo "<div style='color: red;'>❌ خطأ في work_orders: " . $e->getMessage() . "</div>";
        }
        
        // Create work_order_parts table
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
            echo "<div style='color: green;'>✅ تم إنشاء work_order_parts</div>";
            
        } catch (Exception $e) {
            echo "<div style='color: red;'>❌ خطأ في work_order_parts: " . $e->getMessage() . "</div>";
        }
        
        // Create work_order_timeline table
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
            echo "<div style='color: green;'>✅ تم إنشاء work_order_timeline</div>";
            
        } catch (Exception $e) {
            echo "<div style='color: red;'>❌ خطأ في work_order_timeline: " . $e->getMessage() . "</div>";
        }
        
        // Test the query
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
            
        } catch (Exception $e) {
            echo "<div style='color: red;'>❌ خطأ في الاستعلام: " . $e->getMessage() . "</div>";
        }
        
        echo "<hr>";
        echo "<div style='color: green; font-weight: bold; font-size: 18px;'>";
        echo "🎉 تم الإعداد بنجاح!";
        echo "</div>";
        
        echo "<h3>الخطوات التالية:</h3>";
        echo "<ol>";
        echo "<li><a href='admin_breakdowns_workshop.php'>اذهب إلى إدارة الورشة</a></li>";
        echo "<li>اختبر إنشاء أمر عمل جديد</li>";
        echo "</ol>";
        
        // Store connection info in session for future use
        session_start();
        $_SESSION['db_config'] = [
            'host' => $host,
            'dbname' => $dbname,
            'username' => $username,
            'password' => $password
        ];
        
    } catch (PDOException $e) {
        echo "<div style='color: red; font-weight: bold;'>❌ خطأ في الاتصال: " . $e->getMessage() . "</div>";
        echo "<h3>الحلول المقترحة:</h3>";
        echo "<ol>";
        echo "<li>تأكد من اسم قاعدة البيانات</li>";
        echo "<li>تأكد من اسم المستخدم</li>";
        echo "<li>تأكد من كلمة المرور</li>";
        echo "<li>تأكد من أن قاعدة البيانات موجودة</li>";
        echo "<li>جرب استخدام 127.0.0.1 بدلاً من localhost</li>";
        echo "</ol>";
        echo "<p><a href='database_setup.php'>عد وحاول مرة أخرى</a></p>";
    }
    
} else {
    // Show the connection form
    echo "<h3>أدخل بيانات الاتصال بقاعدة البيانات:</h3>";
    
    echo "<form method='post' style='max-width: 500px;'>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><td><strong>Host:</strong></td><td><input type='text' name='host' value='localhost' size='30'></td></tr>";
    echo "<tr><td><strong>Database Name:</strong></td><td><input type='text' name='dbname' value='u442210176_Futur2' size='30'></td></tr>";
    echo "<tr><td><strong>Username:</strong></td><td><input type='text' name='username' value='u442210176_Futur2' size='30'></td></tr>";
    echo "<tr><td><strong>Password:</strong></td><td><input type='password' name='password' size='30' placeholder='أدخل كلمة المرور'></td></tr>";
    echo "</table>";
    
    echo "<br>";
    echo "<input type='submit' name='setup_database' value='اتصل وأنشئ الجداول' style='background: green; color: white; padding: 10px 20px; font-size: 16px;'>";
    echo "</form>";
    
    echo "<hr>";
    echo "<h3>معلومات مساعدة:</h3>";
    echo "<h4>للحصول على بيانات الاتصال الصحيحة:</h4>";
    echo "<ol>";
    echo "<li>سجل دخول إلى لوحة تحكم Hostinger</li>";
    echo "<li>اذهب إلى <strong>Databases</strong></li>";
    echo "<li>اختر قاعدة البيانات الخاصة بك</li>";
    echo "<li>ابحث عن <strong>Connection Details</strong> أو <strong>Database Details</strong></li>";
    echo "<li>ستجد هناك Host, Database Name, Username, Password</li>";
    echo "</ol>";
    
    echo "<h4>بيانات الاتصال الشائعة في Hostinger:</h4>";
    echo "<ul>";
    echo "<li><strong>Host:</strong> localhost أو 127.0.0.1</li>";
    echo "<li><strong>Database Name:</strong> u442210176_Futur2</li>";
    echo "<li><strong>Username:</strong> u442210176_Futur2</li>";
    echo "<li><strong>Password:</strong> كلمة المرور التي قمت بإنشائها</li>";
    echo "</ul>";
    
    echo "<h4>إذا نسيت كلمة المرور:</h4>";
    echo "<ol>";
    echo "<li>اذهب إلى لوحة تحكم Hostinger</li>";
    echo "<li>اذهب إلى Databases</li>";
    echo "<li>اختر قاعدة البيانات</li>";
    echo "<li>اضغط على <strong>Change Password</strong> أو <strong>Reset Password</strong></li>";
    echo "</ol>";
}
?>
