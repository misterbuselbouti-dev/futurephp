<?php
// FUTURE AUTOMOTIVE - Workshop Setup Script for Hostinger
// سكريبت إعداد جداول الورشة لـ Hostinger

// Database configuration
$host = 'localhost'; // أو اسم السيرفر الخاص بـ Hostinger
$dbname = 'u442210176_Futur2';
$username = 'u442210176_Futur2'; // اسم المستخدم لقاعدة البيانات
$password = 'your_password_here'; // كلمة المرور

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔧 إعداد جداول إدارة الورشة</h2>";
    
    // Read and execute SQL file
    $sqlFile = __DIR__ . '/sql/hostinger_workshop_final.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                    echo "✅ تم تنفيذ: " . substr($statement, 0, 50) . "...<br>";
                } catch (PDOException $e) {
                    echo "⚠️ خطأ: " . $e->getMessage() . "<br>";
                }
            }
        }
        
        echo "<h3>🎉 تم الإعداد بنجاح!</h3>";
        echo "<p>يمكنك الآن استخدام قسم إدارة الورشة.</p>";
        echo "<a href='admin/admin_breakdowns_workshop.php'>اذهب إلى إدارة الورشة</a>";
        
    } else {
        echo "❌ ملف SQL غير موجود: $sqlFile";
    }
    
} catch (PDOException $e) {
    echo "❌ خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage();
    echo "<br><br>";
    echo "<strong>تأكد من:</strong><br>";
    echo "- اسم قاعدة البيانات صحيح<br>";
    echo "- اسم المستخدم صحيح<br>";
    echo "- كلمة المرور صحيحة<br>";
    echo "- قاعدة البيانات موجودة";
}
?>
