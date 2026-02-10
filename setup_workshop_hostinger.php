<?php
// FUTURE AUTOMOTIVE - Workshop Setup Script for Hostinger
// سكريبت إعداد جداول الورشة لـ Hostinger - إعدادات صحيحة

echo "<h2>🔧 إعداد جداول إدارة الورشة - Hostinger</h2>";
echo "<h3>الخطوة 1: إعدادات الاتصال بقاعدة البيانات</h3>";

// Hostinger Database Configuration
// استبدل هذه القيم بالقيم الصحيحة من لوحة تحكم Hostinger
$db_configs = [
    [
        'host' => 'localhost',
        'dbname' => 'u442210176_Futur2',
        'username' => 'u442210176_Futur2',
        'password' => '', // ضع كلمة المرور هنا
        'name' => 'الإعداد الافتراضي'
    ],
    [
        'host' => '127.0.0.1',
        'dbname' => 'u442210176_Futur2', 
        'username' => 'u442210176_Futur2',
        'password' => '', // ضع كلمة المرور هنا
        'name' => 'الإعداد البديل'
    ]
];

$connected = false;
$pdo = null;

echo "<form method='post'>";
echo "<h4>أدخل بيانات الاتصال الصحيحة:</h4>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><td>Host:</td><td><input type='text' name='host' value='localhost' size='30'></td></tr>";
echo "<tr><td>Database Name:</td><td><input type='text' name='dbname' value='u442210176_Futur2' size='30'></td></tr>";
echo "<tr><td>Username:</td><td><input type='text' name='username' value='u442210176_Futur2' size='30'></td></tr>";
echo "<tr><td>Password:</td><td><input type='password' name='password' size='30'></td></tr>";
echo "</table>";
echo "<input type='submit' value='اتصل واختبار' name='test_connection'>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_connection'])) {
    $host = $_POST['host'] ?? 'localhost';
    $dbname = $_POST['dbname'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<h3>نتائج الاختبار:</h3>";
    
    try {
        // Test connection
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<div style='color: green; font-weight: bold;'>✅ الاتصال بنجاح بقاعدة البيانات!</div>";
        echo "<div>Host: $host</div>";
        echo "<div>Database: $dbname</div>";
        echo "<div>Username: $username</div>";
        
        $connected = true;
        
        // Show existing tables
        echo "<h4>الجداول الموجودة حالياً:</h4>";
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            echo "<div style='color: orange;'>لا توجد جداول في قاعدة البيانات</div>";
        } else {
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>$table</li>";
            }
            echo "</ul>";
        }
        
        // Check if workshop tables exist
        $workshop_tables = ['work_orders', 'work_order_parts', 'work_order_timeline'];
        $existing_workshop = array_intersect($workshop_tables, $tables);
        
        if (!empty($existing_workshop)) {
            echo "<div style='color: orange;'>⚠️ جداول الورشة موجودة بالفعل: " . implode(', ', $existing_workshop) . "</div>";
        }
        
        echo "<form method='post'>";
        echo "<input type='hidden' name='host' value='$host'>";
        echo "<input type='hidden' name='dbname' value='$dbname'>";
        echo "<input type='hidden' name='username' value='$username'>";
        echo "<input type='hidden' name='password' value='$password'>";
        echo "<input type='submit' value='إنشاء جداول الورشة' name='create_tables' style='background: green; color: white; padding: 10px;'>";
        echo "</form>";
        
    } catch (PDOException $e) {
        echo "<div style='color: red; font-weight: bold;'>❌ خطأ في الاتصال: " . $e->getMessage() . "</div>";
        echo "<h4>الحلول الممكنة:</h4>";
        echo "<ul>";
        echo "<li>تأكد من اسم قاعدة البيانات الصحيح</li>";
        echo "<li>تأكد من اسم المستخدم الصحيح</li>";
        echo "<li>تأكد من كلمة المرور الصحيحة</li>";
        echo "<li>تأكد من أن قاعدة البيانات موجودة</li>";
        echo "<li>جرب استخدام 127.0.0.1 بدلاً من localhost</li>";
        echo "</ul>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_tables']) && $connected) {
    $host = $_POST['host'];
    $dbname = $_POST['dbname'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    echo "<h3>إنشاء جداول الورشة...</h3>";
    
    try {
        // Reconnect
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Read and execute SQL file
        $sqlFile = __DIR__ . '/sql/hostinger_workshop_final.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            
            // Split SQL into individual statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            $success_count = 0;
            $error_count = 0;
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    try {
                        $pdo->exec($statement);
                        echo "✅ تم تنفيذ: " . substr($statement, 0, 50) . "...<br>";
                        $success_count++;
                    } catch (PDOException $e) {
                        echo "⚠️ خطأ: " . $e->getMessage() . "<br>";
                        $error_count++;
                    }
                }
            }
            
            echo "<h3>🎉 نتائج الإعداد:</h3>";
            echo "<div>✅ أوامر ناجحة: $success_count</div>";
            echo "<div>⚠️ أخطاء: $error_count</div>";
            
            // Verify tables were created
            $stmt = $pdo->query("SHOW TABLES LIKE 'work_%'");
            $created_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($created_tables)) {
                echo "<h4>الجداول التي تم إنشاؤها:</h4>";
                echo "<ul>";
                foreach ($created_tables as $table) {
                    echo "<li style='color: green;'>✅ $table</li>";
                }
                echo "</ul>";
                
                // Show record counts
                echo "<h4>عدد السجلات في كل جدول:</h4>";
                foreach ($created_tables as $table) {
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                    $count = $stmt->fetch()['count'];
                    echo "<div>$table: $count سجل</div>";
                }
                
                echo "<div style='color: green; font-weight: bold; margin-top: 20px;'>";
                echo "🎉 تم إعداد نظام إدارة الورشة بنجاح!";
                echo "</div>";
                
                echo "<h4>الخطوات التالية:</h4>";
                echo "<ol>";
                echo "<li>اذهب إلى <a href='admin/admin_breakdowns_workshop.php'>إدارة الورشة</a></li>";
                echo "<li>اختبر إنشاء أمر عمل جديد</li>";
                echo "<li>تحقق من القائمة الجانبية (Maintenance → Gestion Atelier)</li>";
                echo "</ol>";
                
            } else {
                echo "<div style='color: red;'>لم يتم إنشاء أي جداول!</div>";
            }
            
        } else {
            echo "❌ ملف SQL غير موجود: $sqlFile";
        }
        
    } catch (PDOException $e) {
        echo "❌ خطأ: " . $e->getMessage();
    }
}

echo "<hr>";
echo "<h3>معلومات مساعدة:</h3>";
echo "<div><strong>للحصول على بيانات الاتصال الصحيحة:</strong></div>";
echo "<ol>";
echo "<li>سجل دخول إلى لوحة تحكم Hostinger</li>";
echo "<li>اذهب إلى <strong>Databases</strong></li>";
echo "<li>اختر قاعدة البيانات الخاصة بك</li>";
echo "<li>ستجد بيانات الاتصال في <strong>Details</strong></li>";
echo "</ol>";
?>
